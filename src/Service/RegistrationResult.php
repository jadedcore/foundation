<?php
declare(strict_types=1);

namespace Foundation\Service;

use Foundation\Model\Entity\User;

final readonly class RegistrationResult {
	/** Create a registration result. */
	public function __construct(public User $user, public Token $verificationToken) {
	}
}
