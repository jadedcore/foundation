<?php

declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Foundation\Database\Type\UlidType;

$defaults = require __DIR__ . '/foundation.php';
$configured = Configure::read('Foundation', []);

Configure::write('Foundation', array_replace_recursive($defaults['Foundation'], $configured));

if (!TypeFactory::getMapped('foundation_ulid')) {
	TypeFactory::map('foundation_ulid', UlidType::class);
}
