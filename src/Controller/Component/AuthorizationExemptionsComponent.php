<?php
declare(strict_types=1);

namespace Foundation\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;

class AuthorizationExemptionsComponent extends Component {
	/**
	 * Apply authorization exemptions for the current request.
	 */
	public function startup(): void {
		$controller = $this->getController();
		$request = $controller->getRequest();

		$plugin = $request->getParam('plugin') ?: 'App';
		$controllerName = $request->getParam('controller');
		$action = $request->getParam('action');

		$exemptions = Configure::read('Foundation.authorization.exemptActions', []);

		if (in_array($action, $exemptions[$plugin][$controllerName] ?? [], true)) {
			$controller->Authorization->skipAuthorization();
		}
	}
}
