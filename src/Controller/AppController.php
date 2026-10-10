<?php
declare(strict_types=1);

namespace Foundation\Controller;

use Cake\Controller\Controller;

class AppController extends Controller {

	/** @inheritDoc */
	public function initialize(): void {
		parent::initialize();
		$this->loadComponent('Flash', ['duplicate' => false]);
		$this->loadComponent('Authentication.Authentication');
		$this->loadComponent('Authorization.Authorization');
		$this->loadComponent('Foundation.AuthorizationExemptions');
	}
}
