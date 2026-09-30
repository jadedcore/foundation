<?php
declare(strict_types=1);

namespace Foundation\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Foundation\Mailer\AccountMailer;
use Foundation\Model\Table\AccountsTable;
use Foundation\Service\AccountTokenService;
use Foundation\Service\EmailVerificationService;
use Foundation\Service\PasswordResetService;
use Foundation\Service\PasswordService;
use Foundation\Service\RegistrationService;
use Throwable;

/**
 * Optional neutral account UI. Applications can disable these routes and call
 * Foundation services from their own controllers instead.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FlashComponent $Flash
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
		$accounts = $this->accounts();
		$account = $accounts->newEmptyEntity();
		if ($this->request->is('post')) {
			$password = (string)$this->request->getData('password');
			$passwordConfirmation = (string)$this->request->getData('password_confirmation');
			if ($password !== $passwordConfirmation) {
				$account->setError('password_confirmation', 'Passwords do not match.');
			} else {
				try {
					$result = $this->registrationService($accounts)->register(
						(string)$this->request->getData('email'),
						$password,
					);
					(new AccountMailer())->sendEmailVerification($result->account, $result->verificationToken->plainText);
					$this->Flash->success('Check your email to verify your account.');

					return $this->redirect(['action' => 'login']);
				} catch (Throwable $exception) {
					$this->Flash->error('The account could not be created.');
					$account = $accounts->newEntity(['email' => $this->request->getData('email')]);
				}
			}
		}
		$this->set(compact('account'));

		return null;
	}

	/** Verify an account email address. */
	public function verifyEmail(string $token): Response {
		try {
			$accounts = $this->accounts();
			(new EmailVerificationService($accounts, $this->tokenService()))->verify($token);
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
				$account = $token->record->get('account') ?? $this->accounts()->get($token->record->account_id);
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

	/** Return the configured Foundation accounts table. */
	private function accounts(): AccountsTable {
		/** @var \Foundation\Model\Table\AccountsTable */
		return $this->fetchTable('Foundation.Accounts');
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
		$accounts = $this->accounts();
		/** @var \Foundation\Model\Table\PersistentLoginsTable $logins */
		$logins = $this->fetchTable('Foundation.PersistentLogins');

		return new PasswordResetService($accounts, $this->tokenService(), new PasswordService($accounts), $logins);
	}
}
