<?php
declare(strict_types=1);

namespace Foundation\Authorization;

use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\Policy\OrmResolver;

final class AuthorizationServiceFactory {
	public function create(): AuthorizationServiceInterface {
		return new AuthorizationService(new OrmResolver());
	}
}
