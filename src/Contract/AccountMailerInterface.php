<?php
declare(strict_types=1);

namespace Foundation\Contract;

use Foundation\Model\Entity\Account;

interface AccountMailerInterface {
	/** Send an email verification message. */
	public function sendEmailVerification(Account $account, string $plainToken): void;

	/** Send a password reset message. */
	public function sendPasswordReset(Account $account, string $plainToken): void;
}
