<?php
declare(strict_types=1);

namespace Foundation\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use InvalidArgumentException;
use Symfony\Component\Uid\Ulid;

class UlidBehavior extends Behavior {
	protected array $_defaultConfig = ['fields' => ['id']];

	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$fields = $this->getConfig('fields');
		if (!is_array($fields) || $fields === []) {
			throw new InvalidArgumentException('UlidBehavior fields must be a non-empty array.');
		}
		foreach ($fields as $field) {
			if (!is_string($field) || $field === '') {
				throw new InvalidArgumentException('ULID field names must be non-empty strings.');
			}
		}

		$this->applyUlidSchema(...array_values($fields));
	}

	/** Assign configured ULID fields before inserting a new entity. */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$entity->isNew()) {
			return;
		}
		foreach ($this->getConfig('fields') as $field) {
			if ($entity->get($field) === null || $entity->get($field) === '') {
				$entity->set($field, (string)new Ulid());
			}
		}
	}

	protected function applyUlidSchema(string ...$columns): void {
		$schema = $this->table()->getSchema();

		foreach ($columns as $column) {
			if ($schema->hasColumn($column)) {
				$schema->setColumnType($column, 'foundation_ulid');
			}
		}
	}
}
