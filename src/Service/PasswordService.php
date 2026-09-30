<?php
declare(strict_types=1);

namespace Foundation\Service;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\Account;
use Foundation\Model\Table\AccountsTable;
use InvalidArgumentException;

final class PasswordService {
	private DefaultPasswordHasher $hasher;

	/** Create the service for an accounts table. */
	public function __construct(private readonly AccountsTable $accounts) {
		$this->hasher = new DefaultPasswordHasher();
	}

	/** Validate, hash, and persist a new password. */
	public function set(Account $account, string $password): Account {
		$minimum = (int)Configure::read('Foundation.passwords.minLength', 12);
		if (mb_strlen($password) < $minimum) {
			throw new InvalidArgumentException(sprintf('Password must be at least %d characters.', $minimum));
		}
		$account->set('password_hash', $this->hasher->hash($password));
		$account->set('password_changed_at', DateTime::now());

		return $this->accounts->saveOrFail($account);
	}

	/** Change a password after checking the current password. */
	public function change(Account $account, string $currentPassword, string $newPassword): Account {
		if (!$this->hasher->check($currentPassword, (string)$account->password_hash)) {
			throw new InvalidArgumentException('The current password is incorrect.');
		}

		return $this->set($account, $newPassword);
	}
}
