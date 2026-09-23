<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateFoundationAccountTokens extends BaseMigration {
	/** Create the account token table. */
	public function change(): void {
		$this->table('foundation_account_tokens', ['id' => false, 'primary_key' => ['id']])
			->addColumn('id', 'char', ['limit' => 26, 'null' => false])
			->addColumn('user_id', 'char', ['limit' => 26, 'null' => false])
			->addColumn('purpose', 'string', ['limit' => 40, 'null' => false])
			->addColumn('selector', 'string', ['limit' => 32, 'null' => false])
			->addColumn('token_hash', 'char', ['limit' => 64, 'null' => false])
			->addColumn('expires_at', 'datetime', ['null' => false])
			->addColumn('consumed_at', 'datetime', ['null' => true])
			->addColumn('created', 'datetime', ['null' => false])
			->addIndex(['selector'], ['unique' => true])
			->addIndex(['user_id', 'purpose'])
			->addForeignKey('user_id', 'foundation_users', 'id', ['delete' => 'CASCADE'])
			->create();
	}
}
