<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
	if (!Configure::read('Foundation.routes.enabled', false)) {
		return;
	}

	$routes->plugin('Foundation', [
		'path' => Configure::read('Foundation.routes.prefix', '/foundation'),
	], function (RouteBuilder $builder): void {
		$builder->setRouteClass(DashedRoute::class);
		$builder->connect(
			'/login',
			['controller' => 'Accounts', 'action' => 'login'],
			['_name' => 'foundation:login'],
		);
		$builder->connect(
			'/logout',
			['controller' => 'Accounts', 'action' => 'logout'],
			['_name' => 'foundation:logout'],
		);
		$builder->connect(
			'/register',
			['controller' => 'Accounts', 'action' => 'register'],
			['_name' => 'foundation:register'],
		);
		$builder->connect(
			'/verify/{token}',
			['controller' => 'Accounts', 'action' => 'verifyEmail'],
			['_name' => 'foundation:verify-email']
		)
			->setPass(['token'])
			->setPatterns(['token' => '[A-Za-z0-9._-]+']);
		$builder->connect(
			'/forgot-password',
			['controller' => 'Accounts', 'action' => 'forgotPassword'],
			['_name' => 'foundation:forgot-password'],
		);
		$builder->connect(
			'/reset-password/{token}',
			['controller' => 'Accounts', 'action' => 'resetPassword'],
			['_name' => 'foundation:reset-password']
		)
			->setPass(['token'])
			->setPatterns(['token' => '[A-Za-z0-9._-]+']);
	});
};
