<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\User;
use Foundation\Model\Table\UsersTable;

final class EmailVerificationService {
	/** Create the email verification service. */
	public function __construct(private readonly UsersTable $users, private readonly AccountTokenService $tokens) {
	}

	/** Verify an email token and update the corresponding account. */
	public function verify(string $plainToken): User {
		$record = $this->tokens->resolve($plainToken, AccountTokenService::EMAIL_VERIFICATION);
		/** @var \Foundation\Model\Entity\User $user */
		$user = $record->user;
		$this->users->getConnection()->transactional(function () use ($user, $record): void {
			$user->email_verified_at = DateTime::now();
			if (Configure::read('Foundation.users.activateOnEmailVerification', true)) {
				$user->status = 'active';
			}
			$this->users->saveOrFail($user);
			$this->tokens->consume($record);
		});
		EventManager::instance()->dispatch(new Event('Foundation.User.emailVerified', $this, ['user' => $user]));

		return $user;
	}
}
