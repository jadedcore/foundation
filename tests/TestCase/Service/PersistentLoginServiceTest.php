<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Service;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Foundation\Model\Entity\Account;
use Foundation\Model\Table\AccountsTable;
use Foundation\Model\Table\PersistentLoginsTable;
use Foundation\Service\PersistentLoginService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PersistentLoginServiceTest extends TestCase {
	private AccountsTable $accounts;
	private PersistentLoginsTable $logins;

	protected function setUp(): void {
		parent::setUp();
		$locator = TableRegistry::getTableLocator();
		/** @var \Foundation\Model\Table\AccountsTable $accounts */
		$accounts = $locator->get('Foundation.Accounts');
		/** @var \Foundation\Model\Table\PersistentLoginsTable $logins */
		$logins = $locator->get('Foundation.PersistentLogins');
		$this->accounts = $accounts;
		$this->logins = $logins;
		$this->logins->deleteAll([]);
		$this->accounts->deleteAll([]);
	}

	public function testIssueHashesValidatorAndConsumeRotatesIt(): void {
		$account = $this->createAccount('person@example.com');
		$service = new PersistentLoginService($this->logins);
		$issued = $service->issue($account);
		[$selector, $validator] = explode('.', $issued->plainText, 2);

		$this->assertSame(hash('sha256', $validator), $issued->record->validator_hash);
		$this->assertNotSame($validator, $issued->record->validator_hash);

		$consumed = $service->consume($issued->plainText);
		$this->assertSame((string)$account->id, (string)$consumed->account->id);
		$this->assertSame($selector, explode('.', $consumed->plainText, 2)[0]);
		$this->assertNotSame($issued->plainText, $consumed->plainText);
		$this->assertNotNull($consumed->record->last_used_at);
		$this->assertSame(
			hash('sha256', explode('.', $consumed->plainText, 2)[1]),
			$consumed->record->validator_hash,
		);

		$this->expectException(RuntimeException::class);
		$service->consume($issued->plainText);
	}

	public function testMalformedTokenIsRejected(): void {
		$service = new PersistentLoginService($this->logins);

		$this->expectException(RuntimeException::class);
		$service->consume('malformed');
	}

	public function testIncorrectValidatorIsRejected(): void {
		$account = $this->createAccount('person@example.com');
		$service = new PersistentLoginService($this->logins);
		$issued = $service->issue($account);
		$selector = explode('.', $issued->plainText, 2)[0];

		$this->expectException(RuntimeException::class);
		$service->consume($selector . '.incorrect-validator');
	}

	public function testExpiredLoginIsRejected(): void {
		$account = $this->createAccount('person@example.com');
		$service = new PersistentLoginService($this->logins);
		$issued = $service->issue($account);
		$issued->record->expires_at = DateTime::now()->modify('-1 hour');
		$this->logins->saveOrFail($issued->record);

		$this->expectException(RuntimeException::class);
		$service->consume($issued->plainText);
	}

	public function testRevokePreventsLoginConsumption(): void {
		$account = $this->createAccount('person@example.com');
		$service = new PersistentLoginService($this->logins);
		$issued = $service->issue($account);

		$service->revoke($issued->record);
		$this->assertNotNull($this->logins->get($issued->record->id)->revoked_at);

		$this->expectException(RuntimeException::class);
		$service->consume($issued->plainText);
	}

	public function testRevokeAllLeavesOtherAccountsLoginsActive(): void {
		$account = $this->createAccount('person@example.com');
		$otherAccount = $this->createAccount('other@example.com');
		$service = new PersistentLoginService($this->logins);
		$first = $service->issue($account);
		$second = $service->issue($account);
		$unrelated = $service->issue($otherAccount);

		$service->revokeAll($account);

		$this->assertNotNull($this->logins->get($first->record->id)->revoked_at);
		$this->assertNotNull($this->logins->get($second->record->id)->revoked_at);
		$this->assertNull($this->logins->get($unrelated->record->id)->revoked_at);
	}

	private function createAccount(string $email): Account {
		$account = $this->accounts->newEntity(['email' => $email]);
		$account->status = 'active';

		return $this->accounts->saveOrFail($account);
	}
}
