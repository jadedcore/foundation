# Extending Foundation

Foundation owns authentication identity only. Keep product data in an application table with a
one-to-one association:

```php
$accounts = $this->fetchTable('Foundation.Accounts');
$accounts->hasOne('UserProfiles', [
    'className' => 'Afterlife.UserProfiles',
    'foreignKey' => 'account_id',
]);
```

Create the profile from a listener attached to `Foundation.Account.registered`. Other lifecycle events
currently emitted are `Foundation.Account.emailVerified`, `Foundation.Account.passwordResetRequested`,
and `Foundation.Account.passwordReset`.

Applications own roles, permissions, entity policies, and onboarding. Do not add those columns or
associations to Foundation itself.

## Auditing

Foundation registers `ActorContextInterface` as a shared service. Resolve that one instance during
application bootstrap, publish it for table setup, and pass the same instance to middleware after
authentication. In the host `Application` class:

```php
namespace App;

use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Foundation\Middleware\ActorContextMiddleware;
use Foundation\Utility\ActorContextInterface;

class Application extends BaseApplication {
    public function bootstrap(): void {
        parent::bootstrap();
        Configure::write(
            'Foundation.actorContext',
            $this->getContainer()->get(ActorContextInterface::class),
        );
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
        $actorContext = $this->getContainer()->get(ActorContextInterface::class);

        return $middlewareQueue
            ->add(new AuthenticationMiddleware($this))
            ->add(new ActorContextMiddleware($actorContext));
    }
}
```

Use a shared application base table to attach auditing once. Tables with audit columns extend it:

```php
namespace App\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Table;
use Foundation\Exception\WhoDidItException;
use Foundation\Utility\ActorContextInterface;

class AppTable extends Table {
    public function initialize(array $config): void {
        parent::initialize($config);
        $actorContext = Configure::read('Foundation.actorContext');
        if (!$actorContext instanceof ActorContextInterface) {
            throw new WhoDidItException('Foundation actor context has not been configured.');
        }
        $this->addBehavior('Foundation.WhoDidIt', [
            'actorContext' => $actorContext,
            'onMissingActor' => 'skip',
        ]);
    }
}
```

The service container returns the same shared object to `AppTable` setup and
`ActorContextMiddleware`; the context is the bridge between request identity and persistence.
Only extend `AppTable` for tables that actually have `created_by` and/or `modified_by` columns.
Explicit `actor_id` save options override the request actor. CLI jobs should use the shared
`ActorContextInterface::runAs()` or pass an explicit `actor_id` save option.

When no save option, resolver, or request actor is available, `onMissingActor` supports `skip`,
`error`, `entityPrimaryKey`, `value`, and `callback`. The default remains `skip`. Set
`primaryKeyField` for `entityPrimaryKey` when the entity uses a non-`id` key. For `value`, provide
`fallbackValue`; for `callback`, provide a callable receiving the entity and table:

```php
$this->addBehavior('Foundation.WhoDidIt', [
    'onMissingActor' => 'callback',
    'fallbackValue' => static fn($entity, $table) => $entity->get('owner_id'),
]);
```

## Authentication

The host application remains the `AuthenticationServiceProviderInterface`. Delegate its factory
method to `Foundation\Authentication\AuthenticationServiceFactory` when Foundation owns login:

```php
return (new AuthenticationServiceFactory())->create($request);
```

If the product owns login, configure CakePHP Authentication directly against `Foundation.Accounts`
and the `forAuthentication` finder.
