<?php
declare(strict_types=1);

return [
	'Foundation' => [
		'routes' => [
			'enabled' => false,
			'prefix' => '/foundation',
		],
		'accounts' => [
			'table' => 'foundation_accounts',
			'model' => 'Foundation.Accounts',
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
