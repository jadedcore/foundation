# Extending Foundation

Foundation owns authentication identity only. Keep product data in an application table with a
one-to-one association:

```php
$users = $this->fetchTable('Foundation.Users');
$users->hasOne('UserProfiles', [
    'className' => 'Afterlife.UserProfiles',
    'foreignKey' => 'user_id',
]);
```

Create the profile from a listener attached to `Foundation.User.registered`. Other lifecycle events
currently emitted are `Foundation.User.emailVerified`, `Foundation.User.passwordResetRequested`,
and `Foundation.User.passwordReset`.

Applications own roles, permissions, entity policies, and onboarding. Do not add those columns or
associations to Foundation itself.

## Auditing

Attach the behavior only to tables that have audit columns:

```php
$this->addBehavior('Foundation.WhoDidIt', [
    'actorResolver' => fn () => $actorContext->actorId(),
    'onMissingActor' => 'skip',
]);
```

Add `ActorContextMiddleware` after authentication middleware. CLI jobs should call
`ActorContextInterface::runAs()` or pass an explicit `actor_id` save option.

## Authentication

The host application remains the `AuthenticationServiceProviderInterface`. Delegate its factory
method to `Foundation\Authentication\AuthenticationServiceFactory` when Foundation owns login:

```php
return (new AuthenticationServiceFactory())->create($request);
```

If the product owns login, configure CakePHP Authentication directly against `Foundation.Users`
and the `forAuthentication` finder.
