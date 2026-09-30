<?php
declare(strict_types=1);

namespace Foundation\Service;

use Foundation\Model\Entity\Account;
use Foundation\Model\Entity\PersistentLogin;

final readonly class PersistentLoginResult {
	/** Create a persistent login result. */
	public function __construct(public Account $account, public PersistentLogin $record, public string $plainText) {
	}
}
