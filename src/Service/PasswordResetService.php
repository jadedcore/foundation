<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\User;
use Foundation\Model\Table\PersistentLoginsTable;
use Foundation\Model\Table\UsersTable;

final class PasswordResetService {
	/** Create the password reset service. */
	public function __construct(
		private readonly UsersTable $users,
		private readonly AccountTokenService $tokens,
		private readonly PasswordService $passwords,
		private readonly PersistentLoginsTable $persistentLogins,
	) {
	}

	/** Issue a reset token when an eligible account exists. */
	public function request(string $email): ?Token {
		/** @var \Foundation\Model\Entity\User|null $user */
		$user = $this->users->find()->where(['email' => mb_strtolower(trim($email))])->first();
		if ($user === null || $user->status === 'disabled') {
			return null;
		}
		$token = $this->tokens->issue(
			$user,
			AccountTokenService::PASSWORD_RESET,
			Configure::read('Foundation.tokens.passwordResetTtl', '+1 hour'),
		);
		EventManager::instance()->dispatch(new Event(
			'Foundation.User.passwordResetRequested',
			$this,
			['user' => $user],
		));

		return $token;
	}

	/** Reset a password and revoke existing persistent logins. */
	public function reset(string $plainToken, string $newPassword): User {
		$record = $this->tokens->resolve($plainToken, AccountTokenService::PASSWORD_RESET);
		/** @var \Foundation\Model\Entity\User $user */
		$user = $record->user;
		$this->users->getConnection()->transactional(function () use ($user, $record, $newPassword): void {
			$this->passwords->set($user, $newPassword);
			$this->tokens->consume($record);
			$this->persistentLogins->updateAll(['revoked_at' => new DateTime()], [
				'user_id' => (string)$user->id,
				'revoked_at IS' => null,
			]);
		});
		EventManager::instance()->dispatch(new Event('Foundation.User.passwordReset', $this, ['user' => $user]));

		return $user;
	}
}
