<?php
declare(strict_types=1);

namespace Foundation\Authentication;

use Authentication\Identity as BaseIdentity;
use Symfony\Component\Uid\Ulid;

final class Identity extends BaseIdentity {
	/** Return ULID identifiers as strings for Authentication's scalar ID contract. */
	public function getIdentifier(): array|string|int|null {
		$identifier = $this->get('id');

		return $identifier instanceof Ulid ? (string)$identifier : $identifier;
	}
}
