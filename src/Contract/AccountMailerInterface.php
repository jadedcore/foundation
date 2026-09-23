<?php
declare(strict_types=1);

namespace Foundation\Contract;

use Foundation\Model\Entity\User;

interface AccountMailerInterface {
	/** Send an email verification message. */
	public function sendEmailVerification(User $user, string $plainToken): void;

	/** Send a password reset message. */
	public function sendPasswordReset(User $user, string $plainToken): void;
}
