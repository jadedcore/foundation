<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Foundation\Model\Table\UsersTable;

final class RegistrationService {
	/** Create the registration service. */
	public function __construct(
		private readonly UsersTable $users,
		private readonly AccountTokenService $tokens,
		private readonly PasswordService $passwords,
	) {
	}

	/** Register a pending account and issue an email verification token. */
	public function register(string $email, string $password): RegistrationResult {
		$result = $this->users->getConnection()->transactional(function () use ($email, $password): RegistrationResult {
			/** @var \Foundation\Model\Entity\User $user */
			$user = $this->users->newEntity(['email' => $email]);
			$user->status = 'pending';
			$this->users->saveOrFail($user);
			$this->passwords->set($user, $password);
			$token = $this->tokens->issue(
				$user,
				AccountTokenService::EMAIL_VERIFICATION,
				Configure::read('Foundation.tokens.emailVerificationTtl', '+24 hours'),
			);

			return new RegistrationResult($user, $token);
		});
		EventManager::instance()->dispatch(new Event('Foundation.User.registered', $this, ['user' => $result->user]));

		return $result;
	}
}
