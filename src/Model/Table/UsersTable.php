<?php
declare(strict_types=1);

namespace Foundation\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Foundation\Model\Entity\User;

class UsersTable extends Table {
	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->setTable(Configure::read('Foundation.users.table', 'foundation_users'));
		$this->setPrimaryKey('id');
		$this->setDisplayField('email');
		$this->setEntityClass(User::class);
		$this->getSchema()->setColumnType('id', 'foundation_ulid');
		$this->addBehavior('Timestamp');
		$this->addBehavior('Foundation.Ulid');
		$this->hasMany('AccountTokens', [
			'className' => 'Foundation.AccountTokens',
			'foreignKey' => 'user_id',
			'dependent' => true,
		]);
		$this->hasMany('PersistentLogins', [
			'className' => 'Foundation.PersistentLogins',
			'foreignKey' => 'user_id',
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
		$rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

		return $rules;
	}

	/** Limit authentication to configured eligible statuses. */
	public function findForAuthentication(SelectQuery $query): SelectQuery {
		return $query->where([
			$this->aliasField('status') . ' IN' => Configure::read('Foundation.users.activeStatuses', ['active']),
		]);
	}
}
