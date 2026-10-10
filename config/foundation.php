<?php
declare(strict_types=1);

return [
	'Foundation' => [
		'accounts' => [
			'table' => 'foundation_accounts',
			'model' => 'Foundation.Accounts',
			'identifierField' => 'email',
			'activeStatuses' => ['active'],
			'activateOnEmailVerification' => true,
		],
		'audit' => [
			'onMissingActor' => 'skip',
		],
		'authentication' => [
			'loginUrl' => '/foundation/login',
			'loginRedirect' => '/',
			'logoutRedirect' => '/foundation/login',
			'queryParam' => 'redirect',
		],
		'authorization' => [
			'exemptActions' => [
				'Foundation' => [
					'Accounts' => [
						'login',
						'register',
						'forgotPassword',
						'resetPassword',
						'verifyEmail'
					]
				]
			]
		],
		'email' => [
			'profile' => 'default',
			'from' => null,
			'applicationName' => 'Application',
		],
		'passwords' => [
			'minLength' => 12,
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
		'routes' => [
			'enabled' => false,
			'prefix' => '/foundation',
		],
		'tokens' => [
			'table' => 'foundation_account_tokens',
			'emailVerificationTtl' => '+24 hours',
			'passwordResetTtl' => '+1 hour',
		],
	],
];
