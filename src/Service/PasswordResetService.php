<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\Account;
use Foundation\Model\Table\AccountsTable;
use Foundation\Model\Table\PersistentLoginsTable;

final class PasswordResetService {
	/** Create the password reset service. */
	public function __construct(
		private readonly AccountsTable $accounts,
		private readonly AccountTokenService $tokens,
		private readonly PasswordService $passwords,
		private readonly PersistentLoginsTable $persistentLogins,
	) {
	}

	/** Issue a reset token when an eligible account exists. */
	public function request(string $email): ?Token {
		/** @var \Foundation\Model\Entity\Account|null $account */
		$account = $this->accounts->find()->where(['email' => mb_strtolower(trim($email))])->first();
		if ($account === null || $account->status === 'disabled') {
			return null;
		}
		$token = $this->tokens->issue(
			$account,
			AccountTokenService::PASSWORD_RESET,
			Configure::read('Foundation.tokens.passwordResetTtl', '+1 hour'),
		);
		EventManager::instance()->dispatch(new Event(
			'Foundation.Account.passwordResetRequested',
			$this,
			['account' => $account],
		));

		return $token;
	}

	/** Reset a password and revoke existing persistent logins. */
	public function reset(string $plainToken, string $newPassword): Account {
		$record = $this->tokens->resolve($plainToken, AccountTokenService::PASSWORD_RESET);
		/** @var \Foundation\Model\Entity\Account $account */
		$account = $record->account;
		$this->accounts->getConnection()->transactional(function () use ($account, $record, $newPassword): void {
			$this->passwords->set($account, $newPassword);
			$this->tokens->consume($record);
			$this->persistentLogins->updateAll(['revoked_at' => new DateTime()], [
				'account_id' => (string)$account->id,
				'revoked_at IS' => null,
			]);
		});
		EventManager::instance()->dispatch(new Event('Foundation.Account.passwordReset', $this, ['account' => $account]));

		return $account;
	}
}
