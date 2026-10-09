<?php
declare(strict_types=1);

namespace Foundation\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Exception\PersistenceFailedException;
use Foundation\{
	Mailer\AccountMailer,
	Model\Entity\Account,
	Model\Table\AccountsTable,
	Service\AccountTokenService,
	Service\EmailVerificationService,
	Service\PasswordResetService,
	Service\PasswordService,
	Service\RegistrationResult,
	Service\RegistrationService
};
use InvalidArgumentException;
use Throwable;

/**
 * Optional neutral account UI. Applications can disable these routes and call
 * Foundation services from their own controllers instead.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \Foundation\Model\Table\AccountsTable $Accounts
 */
class AccountsController extends AppController {
	/** @inheritDoc */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);
		$this->Authentication->addUnauthenticatedActions([
			'login',
			'register',
			'verifyEmail',
			'forgotPassword',
			'resetPassword',
		]);
	}

	/** Sign an account in. */
	public function login(): ?Response {
		$this->request->allowMethod(['get', 'post']);
		$result = $this->Authentication->getResult();
		if ($result->isValid()) {
			return $this->redirect(
				$this->Authentication->getLoginRedirect()
					?? Configure::read('Foundation.authentication.loginRedirect', '/'),
			);
		}
		if ($this->request->is('post')) {
			$this->Flash->error('Invalid email address or password.');
		}

		return null;
	}

	/** Sign the current account out. */
	public function logout(): Response {
		$this->request->allowMethod(['post']);
		$this->Authentication->logout();

		return $this->redirect(Configure::read('Foundation.authentication.logoutRedirect', '/foundation/login'));
	}

	/** Register a pending account. */
	public function register(): ?Response {
		$this->request->allowMethod(['get', 'post']);
		$account = $this->Accounts->newEmptyEntity();
		if ($this->request->is('post')) {
			$account = $this->Accounts->newEntity(['email' => $this->request->getData('email')]);
			$result = $this->attemptRegistration($this->Accounts, $account);
			if ($result !== null) {
				return $this->sendVerificationEmail($result);
			}
		}
		$this->set(compact('account'));

		return null;
	}

	/** Validate the registration form and attempt to create the account. */
	private function attemptRegistration(AccountsTable $accountsTable, Account $account): ?RegistrationResult {
		$password = (string)$this->request->getData('password');
		if ($password !== (string)$this->request->getData('password_confirmation')) {
			$account->setError('password_confirmation', 'Passwords do not match.');

			return null;
		}

		try {
			return $this->registrationService($accountsTable)->register(
				(string)$this->request->getData('email'),
				$password,
			);
		} catch (InvalidArgumentException $exception) {
			$account->setError('password', $exception->getMessage());
		} catch (PersistenceFailedException $exception) {
			$errors = $exception->getEntity()->getErrors();
			if ($errors !== []) {
				$account->setErrors($errors, true);
			} else {
				$this->Flash->error('The account could not be created.');
			}
		}

		return null;
	}

	/** Send the verification email and redirect to login. */
	private function sendVerificationEmail(RegistrationResult $result): Response {
		try {
			(new AccountMailer())->sendEmailVerification($result->account, $result->verificationToken->plainText);
		} catch (Throwable) {
			$this->Flash->error(
				'Your account was created, but we could not send the verification email. Please contact support.',
			);

			return $this->redirect(['action' => 'login']);
		}

		$this->Flash->success('Check your email to verify your account.');

		return $this->redirect(['action' => 'login']);
	}

	/** Verify an account email address. */
	public function verifyEmail(string $token): Response {
		try {
			(new EmailVerificationService($this->Accounts, $this->tokenService()))->verify($token);
			$this->Flash->success('Your email address has been verified.');
		} catch (Throwable) {
			$this->Flash->error('The verification link is invalid or has expired.');
		}

		return $this->redirect(['action' => 'login']);
	}

	/** Request a password reset without revealing account existence. */
	public function forgotPassword(): ?Response {
		$this->request->allowMethod(['get', 'post']);
		if ($this->request->is('post')) {
			$service = $this->passwordResetService();
			$token = $service->request((string)$this->request->getData('email'));
			if ($token !== null) {
				/** @var \Foundation\Model\Entity\Account $account */
				$account = $token->record->get('account') ?? $this->Accounts->get($token->record->account_id);
				(new AccountMailer())->sendPasswordReset($account, $token->plainText);
			}
			$this->Flash->success('If an eligible account exists, a reset link has been sent.');

			return $this->redirect(['action' => 'login']);
		}

		return null;
	}

	/** Reset a password with a one-time token. */
	public function resetPassword(string $token): ?Response {
		$this->request->allowMethod(['get', 'post']);
		if ($this->request->is('post')) {
			$password = (string)$this->request->getData('password');
			if ($password !== (string)$this->request->getData('password_confirmation')) {
				$this->Flash->error('Passwords do not match.');

				return null;
			}
			try {
				$this->passwordResetService()->reset($token, $password);
				$this->Flash->success('Your password has been reset.');

				return $this->redirect(['action' => 'login']);
			} catch (Throwable) {
				$this->Flash->error('The reset link is invalid or has expired.');
			}
		}
		$this->set(compact('token'));

		return null;
	}

	/** Build the account token service. */
	private function tokenService(): AccountTokenService {
		/** @var \Foundation\Model\Table\AccountTokensTable $tokens */
		$tokens = $this->fetchTable('Foundation.AccountTokens');

		return new AccountTokenService($tokens);
	}

	/** Build the registration service. */
	private function registrationService(AccountsTable $accounts): RegistrationService {
		return new RegistrationService($accounts, $this->tokenService(), new PasswordService($accounts));
	}

	/** Build the password reset service. */
	private function passwordResetService(): PasswordResetService {
		/** @var \Foundation\Model\Table\PersistentLoginsTable $logins */
		$logins = $this->fetchTable('Foundation.PersistentLogins');

		return new PasswordResetService($this->Accounts, $this->tokenService(), new PasswordService($this->Accounts), $logins);
	}
}
