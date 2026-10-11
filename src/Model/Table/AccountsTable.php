<?php
declare(strict_types=1);

namespace Foundation\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Foundation\Model\Entity\Account;

class AccountsTable extends Table {
	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->setTable(Configure::read('Foundation.accounts.table', 'foundation_accounts'));
		$this->setPrimaryKey('id');
		$this->setDisplayField('email');
		$this->setEntityClass(Account::class);
		$this->addBehavior('Timestamp');
		$this->addBehavior('Foundation.Ulid', ['fields' => ['id']]);
		$this->hasMany('AccountTokens', [
			'className' => 'Foundation.AccountTokens',
			'foreignKey' => 'account_id',
			'dependent' => true,
		]);
		$this->hasMany('PersistentLogins', [
			'className' => 'Foundation.PersistentLogins',
			'foreignKey' => 'account_id',
			'dependent' => true,
		]);
	}

	/** @inheritDoc */
	public function validationDefault(Validator $validator): Validator {
		return $validator
			->email('email', false, 'Enter a valid email address.')
			->maxLength('email', 320)
			->notEmptyString('email')
			->inList('status', ['pending', 'active', 'disabled', 'locked']);
	}

	/** @inheritDoc */
	public function buildRules(RulesChecker $rules): RulesChecker {
		$message = 'An account using this email address already exists.';
		$rules->add($rules->isUnique(['email'], $message), ['errorField' => 'email']);

		return $rules;
	}

	/** Limit authentication to configured eligible statuses. */
	public function findForAuthentication(SelectQuery $query): SelectQuery {
		return $query->where([
			$this->aliasField('status') . ' IN' =>
				Configure::read('Foundation.accounts.activeStatuses', ['active']),
		]);
	}
}
