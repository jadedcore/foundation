<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Authentication;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Http\Session;
use Cake\ORM\TableRegistry;
use Foundation\Authentication\AuthenticationServiceFactory;
use Foundation\Model\Entity\Account;
use Foundation\Model\Table\AccountsTable;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceFactoryTest extends TestCase {
	private AccountsTable $accounts;
	private Account $account;
	private Session $session;

	protected function setUp(): void {
		parent::setUp();
		/** @var \Foundation\Model\Table\AccountsTable $accounts */
		$accounts = TableRegistry::getTableLocator()->get('Foundation.Accounts');
		$this->accounts = $accounts;
		$this->session = new Session();
		$this->account = $this->accounts->newEntity([
			'email' => 'factory-test@example.com',
		]);
		$this->account->password_hash = (new DefaultPasswordHasher())->hash('a sufficiently long password');
		$this->account->status = 'active';
		$this->accounts->saveOrFail($this->account);
	}

	protected function tearDown(): void {
		$this->accounts->delete($this->account);
		$this->session->destroy();
		parent::tearDown();
	}

	public function testFormLoginCanReloadFoundationIdentityFromSessionUsingConfiguredFinder(): void {
		$factory = new AuthenticationServiceFactory();
		$loginRequest = $this->request('POST', '/foundation/login', [
			'email' => $this->account->email,
			'password' => 'a sufficiently long password',
		]);
		$loginService = $factory->create($loginRequest);
		$loginResult = $loginService->authenticate($loginRequest);

		$this->assertTrue($loginResult->isValid(), implode('; ', $loginResult->getErrors()));
		$loginService->persistIdentity($loginRequest, new Response(), $loginResult->getData());

		$sessionRequest = $this->request('GET', '/private');
		$sessionService = $factory->create($sessionRequest);
		$sessionResult = $sessionService->authenticate($sessionRequest);

		$this->assertTrue($sessionResult->isValid());
		$this->assertInstanceOf(Account::class, $sessionResult->getData());
		$this->assertSame((string)$this->account->id, (string)$sessionResult->getData()->id);

		$this->account->status = 'disabled';
		$this->accounts->saveOrFail($this->account);
		$disabledRequest = $this->request('GET', '/private');
		$disabledService = $factory->create($disabledRequest);
		$disabledResult = $disabledService->authenticate($disabledRequest);

		$this->assertFalse($disabledResult->isValid());
	}

	/** @param array<string, string> $data */
	private function request(string $method, string $path, array $data = []): ServerRequest {
		$request = new ServerRequest([], [], $path, $method, 'php://memory', [], [], [], $data);

		return $request->withAttribute('session', $this->session);
	}
}
