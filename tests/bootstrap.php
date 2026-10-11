<?php
declare(strict_types=1);

$localAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$hostAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once file_exists($localAutoload) ? $localAutoload : $hostAutoload;

use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Foundation\Utility\ActorContext;

require_once dirname(__DIR__) . '/config/bootstrap.php';
Configure::write('Foundation.actorContext', new ActorContext());
Cache::setConfig('_cake_core_', ['className' => NullEngine::class]);
ConnectionManager::setConfig('default', [
	'className' => Connection::class,
	'driver' => Sqlite::class,
	'database' => ':memory:',
]);

/** @var \Cake\Database\Connection $connection */
$connection = ConnectionManager::get('default');
$connection->execute('CREATE TABLE foundation_accounts (
	id CHAR(26) PRIMARY KEY,
	email VARCHAR(320) NOT NULL UNIQUE,
	password_hash VARCHAR(255),
	status VARCHAR(32) NOT NULL DEFAULT \'pending\',
	email_verified_at DATETIME,
	password_changed_at DATETIME,
	created DATETIME NOT NULL,
	modified DATETIME NOT NULL,
	created_by CHAR(26),
	modified_by CHAR(26))');
$connection->execute('CREATE TABLE foundation_account_tokens (
	id CHAR(26) PRIMARY KEY,
	account_id CHAR(26) NOT NULL,
	purpose VARCHAR(40) NOT NULL,
	selector VARCHAR(32) NOT NULL UNIQUE,
	token_hash CHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL,
	consumed_at DATETIME,
	created DATETIME NOT NULL)');
$connection->execute('CREATE TABLE foundation_persistent_logins (
	id CHAR(26) PRIMARY KEY,
	account_id CHAR(26) NOT NULL,
	selector VARCHAR(32) NOT NULL UNIQUE,
	validator_hash CHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL,
	last_used_at DATETIME,
	revoked_at DATETIME,
	created DATETIME NOT NULL)');
