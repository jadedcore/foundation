<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateFoundationUsers extends BaseMigration {
	/** Create the Foundation accounts table. */
	public function change(): void {
		$this->table('foundation_accounts', ['id' => false, 'primary_key' => ['id']])
			->addColumn('id', 'char', ['limit' => 26, 'null' => false])
			->addColumn('email', 'string', ['limit' => 320, 'null' => false])
			->addColumn('password_hash', 'string', ['limit' => 255, 'null' => true])
			->addColumn('status', 'string', ['limit' => 32, 'default' => 'pending', 'null' => false])
			->addColumn('email_verified_at', 'datetime', ['null' => true])
			->addColumn('password_changed_at', 'datetime', ['null' => true])
			->addColumn('created', 'datetime', ['null' => false])
			->addColumn('modified', 'datetime', ['null' => false])
			->addColumn('created_by', 'char', ['limit' => 26, 'null' => true])
			->addColumn('modified_by', 'char', ['limit' => 26, 'null' => true])
			->addIndex(['email'], ['unique' => true])
			->addIndex(['status'])
			->create();
	}
}
