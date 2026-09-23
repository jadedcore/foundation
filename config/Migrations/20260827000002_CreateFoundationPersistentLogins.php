<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateFoundationPersistentLogins extends BaseMigration {
	/** Create the persistent login table. */
	public function change(): void {
		$this->table('foundation_persistent_logins', ['id' => false, 'primary_key' => ['id']])
			->addColumn('id', 'char', ['limit' => 26, 'null' => false])
			->addColumn('user_id', 'char', ['limit' => 26, 'null' => false])
			->addColumn('selector', 'string', ['limit' => 32, 'null' => false])
			->addColumn('validator_hash', 'char', ['limit' => 64, 'null' => false])
			->addColumn('expires_at', 'datetime', ['null' => false])
			->addColumn('last_used_at', 'datetime', ['null' => true])
			->addColumn('revoked_at', 'datetime', ['null' => true])
			->addColumn('created', 'datetime', ['null' => false])
			->addIndex(['selector'], ['unique' => true])
			->addIndex(['user_id', 'expires_at'])
			->addForeignKey('user_id', 'foundation_users', 'id', ['delete' => 'CASCADE'])
			->create();
	}
}
