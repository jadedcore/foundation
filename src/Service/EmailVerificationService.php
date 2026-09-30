<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Foundation\Model\Entity\Account;
use Foundation\Model\Table\AccountsTable;

final class EmailVerificationService {
	/** Create the email verification service. */
	public function __construct(private readonly AccountsTable $accounts, private readonly AccountTokenService $tokens) {
	}

	/** Verify an email token and update the corresponding account. */
	public function verify(string $plainToken): Account {
		$record = $this->tokens->resolve($plainToken, AccountTokenService::EMAIL_VERIFICATION);
		/** @var \Foundation\Model\Entity\Account $account */
		$account = $record->account;
		$this->accounts->getConnection()->transactional(function () use ($account, $record): void {
			$account->email_verified_at = DateTime::now();
			if (Configure::read('Foundation.accounts.activateOnEmailVerification', true)) {
				$account->status = 'active';
			}
			$this->accounts->saveOrFail($account);
			$this->tokens->consume($record);
		});
		EventManager::instance()->dispatch(new Event('Foundation.Account.emailVerified', $this, ['account' => $account]));

		return $account;
	}
}
