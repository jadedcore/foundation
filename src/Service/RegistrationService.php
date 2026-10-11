<?php
declare(strict_types=1);

namespace Foundation\Service;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Foundation\Model\Table\AccountsTable;

final class RegistrationService {
	/** Create the registration service. */
	public function __construct(
		private readonly AccountsTable $accounts,
		private readonly AccountTokenService $tokens,
		private readonly PasswordService $passwords,
	) {
	}

	/** Register a pending account and issue an email verification token. */
	public function register(string $email, string $password): RegistrationResult {
		$result = $this->accounts->getConnection()->transactional(function () use ($email, $password): RegistrationResult {
			/** @var \Foundation\Model\Entity\Account $account */
			$account = $this->accounts->newEntity(['email' => $email]);
			$account->status = 'pending';
			$this->accounts->saveOrFail($account);
			$this->passwords->set($account, $password);
			$token = $this->tokens->issue(
				$account,
				AccountTokenService::EMAIL_VERIFICATION,
				Configure::read('Foundation.tokens.emailVerificationTtl', '+24 hours'),
			);

			return new RegistrationResult($account, $token);
		});
		EventManager::instance()->dispatch(new Event('Foundation.Account.registered', $this, ['account' => $result->account]));

		return $result;
	}
}
