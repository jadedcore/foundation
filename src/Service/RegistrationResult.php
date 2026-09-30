<?php
declare(strict_types=1);

namespace Foundation\Service;

use Foundation\Model\Entity\Account;

final readonly class RegistrationResult {
	/** Create a registration result. */
	public function __construct(public Account $account, public Token $verificationToken) {
	}
}
