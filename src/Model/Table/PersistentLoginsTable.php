<?php
declare(strict_types=1);

namespace Foundation\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Table;
use Foundation\Model\Entity\PersistentLogin;

class PersistentLoginsTable extends Table {
	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->setTable(Configure::read('Foundation.rememberMe.table', 'foundation_persistent_logins'));
		$this->setPrimaryKey('id');
		$this->setEntityClass(PersistentLogin::class);
		$this->addBehavior('Timestamp', ['modified' => false]);
		$this->addBehavior('Foundation.Ulid', [
			'fields' => [
				'id',
				'account_id'
			]
		]);
		$this->belongsTo('Accounts', ['className' => 'Foundation.Accounts', 'foreignKey' => 'account_id']);
	}
}
