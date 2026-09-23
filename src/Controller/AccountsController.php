<?php
declare(strict_types=1);

namespace Foundation\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Foundation\Mailer\AccountMailer;
use Foundation\Model\Table\UsersTable;
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

	/** Sign a user in. */
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

	/** Sign the current user out. */
	public function logout(): Response {
		$this->request->allowMethod(['post']);
		$this->Authentication->logout();

		return $this->redirect(Configure::read('Foundation.authentication.logoutRedirect', '/foundation/login'));
	}

	/** Register a pending account. */
	public function register(): ?Response {
		$this->request->allowMethod(['get', 'post']);
		$users = $this->users();
		$user = $users->newEmptyEntity();
		if ($this->request->is('post')) {
			$password = (string)$this->request->getData('password');
			$passwordConfirmation = (string)$this->request->getData('password_confirmation');
			if ($password !== $passwordConfirmation) {
				$user->setError('password_confirmation', 'Passwords do not match.');
			} else {
				try {
					$result = $this->registrationService($users)->register(
						(string)$this->request->getData('email'),
						$password,
					);
					(new AccountMailer())->sendEmailVerification($result->user, $result->verificationToken->plainText);
					$this->Flash->success('Check your email to verify your account.');

					return $this->redirect(['action' => 'login']);
				} catch (Throwable $exception) {
					$this->Flash->error('The account could not be created.');
					$user = $users->newEntity(['email' => $this->request->getData('email')]);
				}
			}
		}
		$this->set(compact('user'));

		return null;
	}

	/** Verify an account email address. */
	public function verifyEmail(string $token): Response {
		try {
			$users = $this->users();
			(new EmailVerificationService($users, $this->tokenService()))->verify($token);
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
				/** @var \Foundation\Model\Entity\User $user */
				$user = $token->record->get('user') ?? $this->users()->get($token->record->user_id);
				(new AccountMailer())->sendPasswordReset($user, $token->plainText);
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

	/** Return the configured Foundation users table. */
	private function users(): UsersTable {
		/** @var \Foundation\Model\Table\UsersTable */
		return $this->fetchTable('Foundation.Users');
	}

	/** Build the account token service. */
	private function tokenService(): AccountTokenService {
		/** @var \Foundation\Model\Table\AccountTokensTable $tokens */
		$tokens = $this->fetchTable('Foundation.AccountTokens');

		return new AccountTokenService($tokens);
	}

	/** Build the registration service. */
	private function registrationService(UsersTable $users): RegistrationService {
		return new RegistrationService($users, $this->tokenService(), new PasswordService($users));
	}

	/** Build the password reset service. */
	private function passwordResetService(): PasswordResetService {
		$users = $this->users();
		/** @var \Foundation\Model\Table\PersistentLoginsTable $logins */
		$logins = $this->fetchTable('Foundation.PersistentLogins');

		return new PasswordResetService($users, $this->tokenService(), new PasswordService($users), $logins);
	}
}
