<?php
declare(strict_types=1);

namespace Foundation\Service;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\User;
use Foundation\Model\Table\UsersTable;
use InvalidArgumentException;

final class PasswordService {
	private DefaultPasswordHasher $hasher;

	/** Create the service for a users table. */
	public function __construct(private readonly UsersTable $users) {
		$this->hasher = new DefaultPasswordHasher();
	}

	/** Validate, hash, and persist a new password. */
	public function set(User $user, string $password): User {
		$minimum = (int)Configure::read('Foundation.passwords.minLength', 12);
		if (mb_strlen($password) < $minimum) {
			throw new InvalidArgumentException(sprintf('Password must be at least %d characters.', $minimum));
		}
		$user->set('password_hash', $this->hasher->hash($password));
		$user->set('password_changed_at', DateTime::now());

		return $this->users->saveOrFail($user);
	}

	/** Change a password after checking the current password. */
	public function change(User $user, string $currentPassword, string $newPassword): User {
		if (!$this->hasher->check($currentPassword, (string)$user->password_hash)) {
			throw new InvalidArgumentException('The current password is incorrect.');
		}

		return $this->set($user, $newPassword);
	}
}
