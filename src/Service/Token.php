<?php
declare(strict_types=1);

namespace Foundation\Service;

use Foundation\Model\Entity\AccountToken;

final readonly class Token {
	/** Create a token result containing its persisted and plaintext forms. */
	public function __construct(public AccountToken $record, public string $plainText) {
	}
}
