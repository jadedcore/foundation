<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Model\Behavior;

use Cake\Database\Driver\Mysql;
use Cake\Database\TypeFactory;
use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class UlidBehaviorTest extends TestCase {
	public function testAssignsAValidUlid(): void {
		$table = TableRegistry::getTableLocator()->get('Foundation.Accounts');
		$entity = $table->newEntity(['email' => 'person@example.com', 'status' => 'pending']);
		$table->saveOrFail($entity);
		$this->assertTrue(Ulid::isValid((string)$entity->id));
	}

	public function testMapsConfiguredFieldsToFoundationUlidType(): void {
		$tables = TableRegistry::getTableLocator();
		$accounts = $tables->get('Foundation.Accounts');
		$tokens = $tables->get('Foundation.AccountTokens');
		$logins = $tables->get('Foundation.PersistentLogins');

		$this->assertSame('foundation_ulid', $accounts->getSchema()->getColumnType('id'));
		$this->assertSame('foundation_ulid', $tokens->getSchema()->getColumnType('id'));
		$this->assertSame('foundation_ulid', $tokens->getSchema()->getColumnType('account_id'));
		$this->assertSame('foundation_ulid', $logins->getSchema()->getColumnType('id'));
		$this->assertSame('foundation_ulid', $logins->getSchema()->getColumnType('account_id'));
	}

	public function testDatabaseTypeRoundTripsUlid(): void {
		$driver = new Mysql();
		$type = TypeFactory::build('foundation_ulid');
		$ulid = new Ulid();

		$this->assertSame((string)$ulid, $type->toDatabase($ulid, $driver));
		$this->assertEquals($ulid, $type->toPHP((string)$ulid, $driver));
		$this->assertEquals($ulid, $type->marshal((string)$ulid));
		$this->assertNull($type->marshal(''));
	}
}
