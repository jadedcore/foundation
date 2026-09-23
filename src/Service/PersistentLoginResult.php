<?php
declare(strict_types=1);

namespace Foundation\Service;

use Foundation\Model\Entity\PersistentLogin;
use Foundation\Model\Entity\User;

final readonly class PersistentLoginResult {
	/** Create a persistent login result. */
	public function __construct(public User $user, public PersistentLogin $record, public string $plainText) {
	}
}
