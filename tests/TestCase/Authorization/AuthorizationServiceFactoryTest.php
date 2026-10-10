<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Authorization;

use Authorization\AuthorizationService;
use Foundation\Authorization\AuthorizationServiceFactory;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceFactoryTest extends TestCase {
	public function testCreateReturnsAuthorizationService(): void {
		$factory = new AuthorizationServiceFactory();

		$this->assertInstanceOf(AuthorizationService::class, $factory->create());
	}
}
