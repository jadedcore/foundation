<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Foundation\Exception\WhoDidItException;
use Foundation\Model\Table\AccountsTable;
use Foundation\Utility\ActorContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class WhoDidItBehaviorTest extends TestCase {
	private AccountsTable $accounts;
	private ActorContext $actorContext;

	protected function setUp(): void {
		parent::setUp();
		$locator = TableRegistry::getTableLocator();
		$locator->clear();
		/** @var \Foundation\Model\Table\AccountsTable $accounts */
		$accounts = $locator->get('Foundation.Accounts');
		$this->accounts = $accounts;
		$actorContext = Configure::read('Foundation.actorContext');
		$this->assertInstanceOf(ActorContext::class, $actorContext);
		$this->actorContext = $actorContext;
		$this->actorContext->clear();
		$this->accounts->deleteAll([]);
	}

	protected function tearDown(): void {
		$this->actorContext->clear();
		$this->accounts->deleteAll([]);
		TableRegistry::getTableLocator()->clear();
		parent::tearDown();
	}

	public function testExplicitActorOptionTakesPrecedenceOverContext(): void {
		$this->configureBehavior(['onMissingActor' => 'skip']);
		$this->actorContext->setActorId('context-actor');

		$account = $this->saveAccount('explicit@example.com', ['actor_id' => 'option-actor']);

		$this->assertSame('option-actor', $account->created_by);
		$this->assertSame('option-actor', $account->modified_by);
	}

	public function testConfiguredResolverRemainsSupported(): void {
		$this->configureBehavior([
			'actorContext' => $this->actorContext,
			'actorResolver' => static fn() => 'resolver-actor',
		]);
		$this->actorContext->setActorId('context-actor');

		$account = $this->saveAccount('resolver@example.com');

		$this->assertSame('resolver-actor', $account->created_by);
		$this->assertSame('resolver-actor', $account->modified_by);
	}

	public function testMissingActorDefaultsToSkippingAuditFields(): void {
		$this->configureBehavior(['onMissingActor' => 'skip']);

		$account = $this->saveAccount('missing@example.com');

		$this->assertNull($account->created_by);
		$this->assertNull($account->modified_by);
	}

	public function testMissingActorCanUseConfiguredFallbackValue(): void {
		$this->configureBehavior([
			'onMissingActor' => 'value',
			'fallbackValue' => 'system-actor',
		]);

		$account = $this->saveAccount('fallback@example.com');

		$this->assertSame('system-actor', $account->created_by);
		$this->assertSame('system-actor', $account->modified_by);
	}

	public function testMissingActorCanRaiseAnException(): void {
		$this->configureBehavior(['onMissingActor' => 'error']);
		$this->expectException(WhoDidItException::class);

		$this->saveAccount('error@example.com');
	}

	public function testMissingActorCanUseEntityPrimaryKey(): void {
		$this->configureBehavior(['onMissingActor' => 'entityPrimaryKey']);
		$account = $this->accounts->newEntity(['email' => 'primary-key@example.com', 'status' => 'pending']);
		$account->set('id', (string)new Ulid());

		$this->accounts->saveOrFail($account);

		$this->assertSame((string)$account->id, $account->created_by);
		$this->assertSame((string)$account->id, $account->modified_by);
	}

	public function testMissingActorCanUseCallbackFallback(): void {
		$this->configureBehavior([
			'onMissingActor' => 'callback',
			'fallbackValue' => static fn($entity): string => 'actor-for-' . $entity->email,
		]);

		$account = $this->saveAccount('callback@example.com');

		$this->assertSame('actor-for-callback@example.com', $account->created_by);
		$this->assertSame('actor-for-callback@example.com', $account->modified_by);
	}

	private function configureBehavior(array $config): void {
		$this->accounts->behaviors()->get('WhoDidIt')->setConfig($config);
	}

	/** @param array<string, mixed> $options */
	private function saveAccount(string $email, array $options = []): mixed {
		$account = $this->accounts->newEntity(['email' => $email, 'status' => 'pending']);

		return $this->accounts->saveOrFail($account, $options);
	}
}
