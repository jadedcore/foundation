<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Service;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Foundation\Model\Table\AccountTokensTable;
use Foundation\Model\Table\AccountsTable;
use Foundation\Model\Table\PersistentLoginsTable;
use Foundation\Service\AccountTokenService;
use Foundation\Service\EmailVerificationService;
use Foundation\Service\PasswordResetService;
use Foundation\Service\PasswordService;
use Foundation\Service\PersistentLoginService;
use Foundation\Service\RegistrationService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AccountLifecycleTest extends TestCase {
	private AccountsTable $accounts;
	private AccountTokensTable $tokens;
	private PersistentLoginsTable $logins;

	protected function setUp(): void {
		parent::setUp();
		$locator = TableRegistry::getTableLocator();
		/** @var \Foundation\Model\Table\AccountsTable $accounts */
		$accounts = $locator->get('Foundation.Accounts');
		/** @var \Foundation\Model\Table\AccountTokensTable $tokens */
		$tokens = $locator->get('Foundation.AccountTokens');
		/** @var \Foundation\Model\Table\PersistentLoginsTable $logins */
		$logins = $locator->get('Foundation.PersistentLogins');
		$this->accounts = $accounts;
		$this->tokens = $tokens;
		$this->logins = $logins;
		$this->logins->deleteAll([]);
		$this->tokens->deleteAll([]);
		$this->accounts->deleteAll([]);
	}

	public function testRegistrationAndEmailVerification(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->accounts);
		$registration = new RegistrationService($this->accounts, $tokenService, $passwordService);
		$result = $registration->register('Person@Example.com', 'a sufficiently long password');
		$this->assertSame('person@example.com', $result->account->email);
		$this->assertSame('pending', $result->account->status);
		$this->assertTrue((new DefaultPasswordHasher())->check('a sufficiently long password', $result->account->password_hash));
		$verified = (new EmailVerificationService($this->accounts, $tokenService))
			->verify($result->verificationToken->plainText);
		$this->assertSame('active', $verified->status);
		$this->assertNotNull($verified->email_verified_at);
	}

	public function testPasswordResetConsumesTokenAndChangesPassword(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->accounts);
		$registration = new RegistrationService($this->accounts, $tokenService, $passwordService);
		$registered = $registration->register('person@example.com', 'initial long password');
		$reset = new PasswordResetService($this->accounts, $tokenService, $passwordService, $this->logins);
		$token = $reset->request($registered->account->email);
		$this->assertNotNull($token);
		$loginService = new PersistentLoginService($this->logins);
		$login = $loginService->issue($registered->account);
		$account = $reset->reset($token->plainText, 'replacement long password');
		$this->assertTrue((new DefaultPasswordHasher())->check('replacement long password', $account->password_hash));
		$this->assertNotNull($this->logins->get($login->record->id)->revoked_at);
		$this->expectException(RuntimeException::class);
		$reset->reset($token->plainText, 'another replacement password');
	}

	public function testAccountTokensArePurposeScoped(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->accounts);
		$registration = new RegistrationService($this->accounts, $tokenService, $passwordService);
		$registered = $registration->register('person@example.com', 'initial long password');

		$this->expectException(RuntimeException::class);
		$tokenService->resolve($registered->verificationToken->plainText, AccountTokenService::PASSWORD_RESET);
	}

	public function testIssuingReplacementTokenInvalidatesPreviousToken(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->accounts);
		$registration = new RegistrationService($this->accounts, $tokenService, $passwordService);
		$registered = $registration->register('person@example.com', 'initial long password');
		$tokenService->issue($registered->account, AccountTokenService::EMAIL_VERIFICATION, '+1 hour');

		$this->expectException(RuntimeException::class);
		$tokenService->resolve($registered->verificationToken->plainText, AccountTokenService::EMAIL_VERIFICATION);
	}

	public function testExpiredAccountTokenIsRejected(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->accounts);
		$registration = new RegistrationService($this->accounts, $tokenService, $passwordService);
		$registered = $registration->register('person@example.com', 'initial long password');
		$registered->verificationToken->record->expires_at = DateTime::now()->modify('-1 hour');
		$this->tokens->saveOrFail($registered->verificationToken->record);

		$this->expectException(RuntimeException::class);
		$tokenService->resolve($registered->verificationToken->plainText, AccountTokenService::EMAIL_VERIFICATION);
	}

	public function testPasswordResetRequestDoesNotIssueTokensForMissingOrDisabledAccounts(): void {
		$tokenService = new AccountTokenService($this->tokens);
		$passwordService = new PasswordService($this->accounts);
		$registration = new RegistrationService($this->accounts, $tokenService, $passwordService);
		$registered = $registration->register('person@example.com', 'initial long password');
		$registered->account->status = 'disabled';
		$this->accounts->saveOrFail($registered->account);
		$reset = new PasswordResetService($this->accounts, $tokenService, $passwordService, $this->logins);

		$this->assertNull($reset->request('missing@example.com'));
		$this->assertNull($reset->request('person@example.com'));
		$this->assertSame(0, $this->tokens->find()->where(['purpose' => AccountTokenService::PASSWORD_RESET])->count());
	}
}
