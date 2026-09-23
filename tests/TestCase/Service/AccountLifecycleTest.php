<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Service;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Foundation\Model\Table\AccountTokensTable;
use Foundation\Model\Table\PersistentLoginsTable;
use Foundation\Model\Table\UsersTable;
use Foundation\Service\AccountTokenService;
use Foundation\Service\EmailVerificationService;
use Foundation\Service\PasswordResetService;
use Foundation\Service\PasswordService;
use Foundation\Service\RegistrationService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AccountLifecycleTest extends TestCase {
	private UsersTable $users;
	private AccountTokensTable $tokens;
	private PersistentLoginsTable $logins;

	protected function setUp(): void {
		parent::setUp();
		$locator = TableRegistry::getTableLocator();
		/** @var \Foundation\Model\Table\UsersTable $users */
		$users = $locator->get('Foundation.Users');
		/** @var \Foundation\Model\Table\AccountTokensTable $tokens */
		$tokens = $locator->get('Foundation.AccountTokens');
		/** @var \Foundation\Model\Table\PersistentLoginsTable $logins */
		$logins = $locator->get('Foundation.PersistentLogins');
		$this->users = $users;
		$this->tokens = $tokens;
		$this->logins = $logins;
		$this->logins->deleteAll([]);
		$this->tokens->deleteAll([]);
		$this->users->deleteAll([]);
	}

	public function testRegistrationAndEmailVerification(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->users);
		$registration = new RegistrationService($this->users, $tokenService, $passwordService);
		$result = $registration->register('Person@Example.com', 'a sufficiently long password');
		$this->assertSame('person@example.com', $result->user->email);
		$this->assertSame('pending', $result->user->status);
		$this->assertTrue((new DefaultPasswordHasher())->check('a sufficiently long password', $result->user->password_hash));
		$verified = (new EmailVerificationService($this->users, $tokenService))
			->verify($result->verificationToken->plainText);
		$this->assertSame('active', $verified->status);
		$this->assertNotNull($verified->email_verified_at);
	}

	public function testPasswordResetConsumesTokenAndChangesPassword(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->users);
		$registration = new RegistrationService($this->users, $tokenService, $passwordService);
		$registered = $registration->register('person@example.com', 'initial long password');
		$reset = new PasswordResetService($this->users, $tokenService, $passwordService, $this->logins);
		$token = $reset->request($registered->user->email);
		$this->assertNotNull($token);
		$user = $reset->reset($token->plainText, 'replacement long password');
		$this->assertTrue((new DefaultPasswordHasher())->check('replacement long password', $user->password_hash));
		$this->expectException(RuntimeException::class);
		$reset->reset($token->plainText, 'another replacement password');
	}
}
