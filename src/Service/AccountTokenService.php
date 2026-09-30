<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\I18n\DateTime;
use Foundation\Model\Entity\Account;
use Foundation\Model\Entity\AccountToken;
use Foundation\Model\Table\AccountTokensTable;
use RuntimeException;

final class AccountTokenService {
	public const EMAIL_VERIFICATION = 'email_verification';
	public const PASSWORD_RESET = 'password_reset';

	/** Create the token service for a token table. */
	public function __construct(private readonly AccountTokensTable $tokens) {
	}

	/** Issue a purpose-scoped, single-use account token. */
	public function issue(Account $account, string $purpose, string $ttl): Token {
		$this->tokens->updateAll(['consumed_at' => DateTime::now()], [
			'account_id' => (string)$account->id,
			'purpose' => $purpose,
			'consumed_at IS' => null,
		]);
		$selector = bin2hex(random_bytes(9));
		$validator = $this->base64Url(random_bytes(32));
		$record = $this->tokens->newEmptyEntity();
		$record->account_id = (string)$account->id;
		$record->purpose = $purpose;
		$record->selector = $selector;
		$record->token_hash = hash('sha256', $validator);
		$record->expires_at = new DateTime($ttl);
		$this->tokens->saveOrFail($record);

		return new Token($record, $selector . '.' . $validator);
	}

	/** Resolve and validate a plaintext token without consuming it. */
	public function resolve(string $plainToken, string $purpose): AccountToken {
		[$selector, $validator] = $this->split($plainToken);
		$record = $this->tokens->find()->where([
			'selector' => $selector,
			'purpose' => $purpose,
			'consumed_at IS' => null,
			'expires_at >' => DateTime::now(),
		])->contain(['Accounts'])->first();
		if ($record === null || !hash_equals($record->token_hash, hash('sha256', $validator))) {
			throw new RuntimeException('The account token is invalid or has expired.');
		}

		return $record;
	}

	/** Mark a validated token as consumed. */
	public function consume(AccountToken $record): void {
		if ($record->consumed_at !== null) {
			throw new RuntimeException('The account token has already been used.');
		}
		$record->consumed_at = DateTime::now();
		$this->tokens->saveOrFail($record);
	}

	/** @return array{string, string} */
	private function split(string $plainToken): array {
		$parts = explode('.', $plainToken, 2);
		if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
			throw new RuntimeException('The account token is malformed.');
		}

		return [$parts[0], $parts[1]];
	}

	/** Encode random bytes for URL-safe transport. */
	private function base64Url(string $bytes): string {
		return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
	}
}
