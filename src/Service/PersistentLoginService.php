<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\PersistentLogin;
use Foundation\Model\Entity\User;
use Foundation\Model\Table\PersistentLoginsTable;
use RuntimeException;

final class PersistentLoginService {
	/** Create the service for a persistent login table. */
	public function __construct(private readonly PersistentLoginsTable $logins) {
	}

	/** Issue a persistent login token for a user. */
	public function issue(User $user): PersistentLoginResult {
		$record = $this->logins->newEmptyEntity();
		$record->user_id = (string)$user->id;
		$record->selector = bin2hex(random_bytes(9));
		$validator = $this->base64Url(random_bytes(32));
		$record->validator_hash = hash('sha256', $validator);
		$record->expires_at = new DateTime(Configure::read('Foundation.rememberMe.ttl', '+30 days'));
		$this->logins->saveOrFail($record);

		return new PersistentLoginResult($user, $record, $record->selector . '.' . $validator);
	}

	/** Validate and rotate a persistent login token. */
	public function consume(string $plainToken): PersistentLoginResult {
		[$selector, $validator] = $this->split($plainToken);
		/** @var \Foundation\Model\Entity\PersistentLogin|null $record */
		$record = $this->logins->find()->where([
			'selector' => $selector,
			'revoked_at IS' => null,
			'expires_at >' => DateTime::now(),
		])->contain(['Users'])->first();
		if ($record === null || !hash_equals($record->validator_hash, hash('sha256', $validator))) {
			throw new RuntimeException('The persistent login token is invalid or expired.');
		}
		$newValidator = $this->base64Url(random_bytes(32));
		$record->validator_hash = hash('sha256', $newValidator);
		$record->last_used_at = DateTime::now();
		$this->logins->saveOrFail($record);

		return new PersistentLoginResult($record->user, $record, $record->selector . '.' . $newValidator);
	}

	/** Revoke one persistent login. */
	public function revoke(PersistentLogin $record): void {
		$record->revoked_at = DateTime::now();
		$this->logins->saveOrFail($record);
	}

	/** Revoke all persistent logins belonging to a user. */
	public function revokeAll(User $user): void {
		$this->logins->updateAll(['revoked_at' => DateTime::now()], [
			'user_id' => (string)$user->id,
			'revoked_at IS' => null,
		]);
	}

	/** @return array{string, string} */
	private function split(string $plainToken): array {
		$parts = explode('.', $plainToken, 2);
		if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
			throw new RuntimeException('The persistent login token is malformed.');
		}

		return [$parts[0], $parts[1]];
	}

	/** Encode random bytes for URL-safe transport. */
	private function base64Url(string $bytes): string {
		return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
	}
}
