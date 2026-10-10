<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Foundation\Controller\Component\AuthorizationExemptionsComponent;
use PHPUnit\Framework\TestCase;

class AuthorizationExemptionsComponentTest extends TestCase {
	private array $originalExemptActions;
	private ?string $originalEncoding;

	protected function setUp(): void {
		parent::setUp();
		$this->originalExemptActions = Configure::read('Foundation.authorization.exemptActions', []);
		$this->originalEncoding = Configure::read('App.encoding');
		Configure::write('App.encoding', 'UTF-8');
	}

	protected function tearDown(): void {
		Configure::write('Foundation.authorization.exemptActions', $this->originalExemptActions);
		Configure::write('App.encoding', $this->originalEncoding);
		parent::tearDown();
	}

	public function testSkipsAuthorizationForConfiguredPluginAction(): void {
		$authorization = $this->startup([
			'plugin' => 'Foundation',
			'controller' => 'Accounts',
			'action' => 'login',
		], [
			'Foundation' => ['Accounts' => ['login']],
		]);

		$this->assertSame(1, $authorization->skipAuthorizationCalls);
	}

	public function testDoesNotSkipAuthorizationForUnconfiguredAction(): void {
		$authorization = $this->startup([
			'plugin' => 'Foundation',
			'controller' => 'Accounts',
			'action' => 'logout',
		], [
			'Foundation' => ['Accounts' => ['login']],
		]);

		$this->assertSame(0, $authorization->skipAuthorizationCalls);
	}

	public function testDefaultsToAppWhenPluginIsMissing(): void {
		$authorization = $this->startup([
			'controller' => 'Dashboard',
			'action' => 'index',
		], [
			'App' => ['Dashboard' => ['index']],
		]);

		$this->assertSame(1, $authorization->skipAuthorizationCalls);
	}

	/** @param array<string, string> $params
	 * @param array<string, array<string, list<string>>> $exemptActions
	 */
	private function startup(array $params, array $exemptActions): AuthorizationExemptionsAuthorizationStub {
		Configure::write('Foundation.authorization.exemptActions', $exemptActions);
		$registry = new ComponentRegistry();
		$authorization = new AuthorizationExemptionsAuthorizationStub();
		$controller = new AuthorizationExemptionsTestController(new ServerRequest(['params' => $params]), $registry, $authorization);
		$this->assertSame($controller, $registry->getController());

		$component = new AuthorizationExemptionsComponent($registry);
		$component->startup();

		return $authorization;
	}
}

class AuthorizationExemptionsTestController extends Controller {
	public AuthorizationExemptionsAuthorizationStub $Authorization;

	public function __construct(
		ServerRequest $request,
		ComponentRegistry $components,
		AuthorizationExemptionsAuthorizationStub $authorization,
	) {
		$this->Authorization = $authorization;
		parent::__construct($request, 'Test', null, $components);
	}
}

class AuthorizationExemptionsAuthorizationStub {
	public int $skipAuthorizationCalls = 0;

	public function skipAuthorization(): void {
		$this->skipAuthorizationCalls++;
	}
}
