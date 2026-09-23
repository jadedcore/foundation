<?php
declare(strict_types=1);

namespace Foundation\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Table;
use Foundation\Model\Entity\AccountToken;

class AccountTokensTable extends Table {
	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->setTable(Configure::read('Foundation.tokens.table', 'foundation_account_tokens'));
		$this->setPrimaryKey('id');
		$this->setEntityClass(AccountToken::class);
		$this->getSchema()->setColumnType('id', 'foundation_ulid');
		$this->getSchema()->setColumnType('user_id', 'foundation_ulid');
		$this->addBehavior('Timestamp', ['modified' => false]);
		$this->addBehavior('Foundation.Ulid');
		$this->belongsTo('Users', ['className' => 'Foundation.Users', 'foreignKey' => 'user_id']);
	}
}
