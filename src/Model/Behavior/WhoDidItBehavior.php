<?php
declare(strict_types=1);

namespace Foundation\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use RuntimeException;

class WhoDidItBehavior extends Behavior {
	protected array $_defaultConfig = [
		'createdByField' => 'created_by',
		'modifiedByField' => 'modified_by',
		'actorOption' => 'actor_id',
		'skipModifiedByOption' => 'skip_modified_by',
		'actorResolver' => null,
		'onMissingActor' => 'skip',
		'fallbackValue' => null,
	];

	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$resolver = $this->getConfig('actorResolver');
		if ($resolver !== null && !is_callable($resolver)) {
			throw new RuntimeException('WhoDidIt actorResolver must be callable.');
		}
		if (!in_array($this->getConfig('onMissingActor'), ['skip', 'error', 'value'], true)) {
			throw new RuntimeException('WhoDidIt onMissingActor must be skip, error, or value.');
		}
	}

	/** Populate audit fields before saving. */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$schema = $this->table()->getSchema();
		$createdField = $this->getConfig('createdByField');
		$modifiedField = $this->getConfig('modifiedByField');
		if (!$schema->hasColumn($createdField) && !$schema->hasColumn($modifiedField)) {
			return;
		}
		$actorId = $this->resolveActorId($entity, $options);
		if ($actorId === null || $actorId === '') {
			return;
		}
		if ($schema->hasColumn($createdField) && $entity->isNew() && $entity->get($createdField) === null) {
			$entity->set($createdField, $actorId);
		}
		if ($schema->hasColumn($modifiedField) && empty($options[$this->getConfig('skipModifiedByOption')])) {
			$entity->set($modifiedField, $actorId);
		}
	}

	/** Resolve an actor from save options, a callback, or configured fallback. */
	private function resolveActorId(EntityInterface $entity, ArrayObject $options): mixed {
		$option = $this->getConfig('actorOption');
		if (array_key_exists($option, $options) && $options[$option] !== null && $options[$option] !== '') {
			return $options[$option];
		}
		$resolver = $this->getConfig('actorResolver');
		if ($resolver !== null) {
			$value = $resolver($entity, $options, $this->table());
			if ($value !== null && $value !== '') {
				return $value;
			}
		}

		return match ($this->getConfig('onMissingActor')) {
			'skip' => null,
			'value' => $this->getConfig('fallbackValue'),
			'error' => throw new RuntimeException('WhoDidIt could not resolve an actor ID.'),
		};
	}
}
