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
	}

	/** Assign configured ULID fields before inserting a new entity. */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (!$entity->isNew()) {
			return;
		}
		foreach ($this->getConfig('fields') as $field) {
			if (!is_string($field) || $field === '') {
				throw new InvalidArgumentException('ULID field names must be non-empty strings.');
			}
			if ($entity->get($field) === null || $entity->get($field) === '') {
				$entity->set($field, (string)new Ulid());
			}
		}
	}
}
