<?php
declare(strict_types=1);

return [
	'Foundation' => [
		'routes' => [
			'enabled' => false,
			'prefix' => '/foundation',
		],
		'users' => [
			'table' => 'foundation_users',
			'model' => 'Foundation.Users',
			'identifierField' => 'email',
			'activeStatuses' => ['active'],
			'activateOnEmailVerification' => true,
		],
		'tokens' => [
			'table' => 'foundation_account_tokens',
			'emailVerificationTtl' => '+24 hours',
			'passwordResetTtl' => '+1 hour',
		],
		'passwords' => [
			'minLength' => 12,
		],
		'authentication' => [
			'loginUrl' => '/foundation/login',
			'loginRedirect' => '/',
			'logoutRedirect' => '/foundation/login',
			'queryParam' => 'redirect',
		],
		'rememberMe' => [
			'enabled' => false,
			'table' => 'foundation_persistent_logins',
			'cookieName' => 'FoundationRemember',
			'ttl' => '+30 days',
			'secure' => true,
			'httpOnly' => true,
			'sameSite' => 'Lax',
		],
		'email' => [
			'profile' => 'default',
			'from' => null,
			'applicationName' => 'Application',
		],
		'audit' => [
			'onMissingActor' => 'skip',
		],
	],
];
