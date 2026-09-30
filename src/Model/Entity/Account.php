<?php
declare(strict_types=1);

namespace Foundation\Model\Entity;

use Cake\ORM\Entity;

class Account extends Entity {
	protected array $_accessible = ['email' => true];
	protected array $_hidden = ['password_hash'];

	/** Normalize email addresses before assignment. */
	protected function _setEmail(string $email): string {
		return mb_strtolower(trim($email));
	}

	/** Check whether this account is active. */
	public function isActive(): bool {
		return $this->status === 'active';
	}

	/** Check whether this account has a verified email address. */
	public function isEmailVerified(): bool {
		return $this->email_verified_at !== null;
	}
}
