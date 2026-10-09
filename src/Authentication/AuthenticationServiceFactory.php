<?php
declare(strict_types=1);

namespace Foundation\Authentication;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Cake\Core\Configure;
use Psr\Http\Message\ServerRequestInterface;

final class AuthenticationServiceFactory {
	/**
	 * Build the Foundation authentication service for a request.
	 */
	public function create(ServerRequestInterface $request): AuthenticationServiceInterface {
		$loginUrl = Configure::read('Foundation.authentication.loginUrl', '/foundation/login');
		$resolver = [
			'className' => 'Authentication.Orm',
			'userModel' => Configure::read('Foundation.accounts.model', 'Foundation.Accounts'),
			'finder' => 'forAuthentication',
		];
		$service = new AuthenticationService([
			'unauthenticatedRedirect' => $loginUrl,
			'queryParam' => Configure::read('Foundation.authentication.queryParam', 'redirect'),
		]);
		$service->loadAuthenticator('Authentication.PrimaryKeySession', [
			'idField' => 'id',
			'identifier' => [
				'className' => 'Authentication.Token',
				'tokenField' => 'id',
				'dataField' => 'key',
				'resolver' => $resolver,
			],
		]);
		$service->loadAuthenticator('Authentication.Form', [
			'loginUrl' => $loginUrl,
			'fields' => ['username' => 'email', 'password' => 'password'],
			'identifier' => [
				'className' => 'Authentication.Password',
				'fields' => ['username' => 'email', 'password' => 'password_hash'],
				'resolver' => $resolver,
			],
		]);

		return $service;
	}
}
