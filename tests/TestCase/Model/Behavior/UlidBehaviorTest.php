<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class UlidBehaviorTest extends TestCase {
	public function testAssignsAValidUlid(): void {
		$table = TableRegistry::getTableLocator()->get('Foundation.Users');
		$entity = $table->newEntity(['email' => 'person@example.com', 'status' => 'pending']);
		$table->saveOrFail($entity);
		$this->assertTrue(Ulid::isValid((string)$entity->id));
	}
}
