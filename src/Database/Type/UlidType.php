<?php
declare(strict_types=1);

namespace Foundation\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Type\BaseType;
use PDO;
use Symfony\Component\Uid\Ulid;
use Throwable;

class UlidType extends BaseType {
	/** @inheritDoc */
	public function toPHP(mixed $value, Driver $driver): ?Ulid {
		if ($value === null || $value === '') {
			return null;
		}
		if ($value instanceof Ulid) {
			return $value;
		}
		try {
			return new Ulid((string)$value);
		} catch (Throwable $exception) {
			throw new DatabaseException('Cannot convert value to a ULID.', null, $exception);
		}
	}

	/** @inheritDoc */
	public function toDatabase(mixed $value, Driver $driver): ?string {
		if ($value === null || $value === '') {
			return null;
		}
		try {
			return (string)($value instanceof Ulid ? $value : new Ulid((string)$value));
		} catch (Throwable $exception) {
			throw new DatabaseException('Cannot convert value to a database ULID.', null, $exception);
		}
	}

	/** @inheritDoc */
	public function marshal(mixed $value): ?Ulid {
		if ($value === null || $value === '') {
			return null;
		}
		try {
			return $value instanceof Ulid ? $value : new Ulid((string)$value);
		} catch (Throwable) {
			return null;
		}
	}

	/** @inheritDoc */
	public function toStatement(mixed $value, Driver $driver): int {
		return PDO::PARAM_STR;
	}

	/** @inheritDoc */
	public function getBaseType(): string {
		return 'string';
	}
}
