<?php
declare(strict_types=1);

namespace Foundation\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Foundation\Exception\WhoDidItException;
use Foundation\Utility\ActorContextInterface;

class WhoDidItBehavior extends Behavior {
	protected array $_defaultConfig = [
		'createdByField' => 'created_by',
		'modifiedByField' => 'modified_by',
		'actorOption' => 'actor_id',
		'skipModifiedByOption' => 'skip_modified_by',
		'actorResolver' => null,
		'actorContext' => null,
		'onMissingActor' => 'skip',
		'fallbackValue' => null,
		'primaryKeyField' => 'id',
	];

	/** @inheritDoc */
	public function initialize(array $config): void {
		parent::initialize($config);
		$resolver = $this->getConfig('actorResolver');
		if ($resolver !== null && !is_callable($resolver)) {
			throw new WhoDidItException('WhoDidIt actorResolver must be callable.');
		}
		$actorContext = $this->getConfig('actorContext');
		if ($actorContext !== null && !$actorContext instanceof ActorContextInterface) {
			throw new WhoDidItException('WhoDidIt actorContext must implement ActorContextInterface.');
		}
		$validModes = ['skip', 'error', 'entityPrimaryKey', 'value', 'callback'];
		if (!in_array($this->getConfig('onMissingActor'), $validModes, true)) {
			throw new WhoDidItException('WhoDidIt onMissingActor must be skip, error, entityPrimaryKey, value, or callback.');
		}
		if ($this->getConfig('onMissingActor') === 'callback' && !is_callable($this->getConfig('fallbackValue'))) {
			throw new WhoDidItException('WhoDidIt fallbackValue must be callable when onMissingActor is callback.');
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
		if ($options->offsetExists($option) && $options[$option] !== null && $options[$option] !== '') {
			return $options[$option];
		}
		$value = null;
		$resolver = $this->getConfig('actorResolver');
		if ($resolver !== null) {
			$value = $resolver($entity, $options, $this->table());
		}
		if ($value === null || $value === '') {
			$actorContext = $this->getConfig('actorContext');
			if ($actorContext instanceof ActorContextInterface) {
				$value = $actorContext->actorId();
			}
		}
		if ($value !== null && $value !== '') {
			return $value;
		}

		return match ($this->getConfig('onMissingActor')) {
			'skip' => null,
			'entityPrimaryKey' => $entity->get($this->getConfig('primaryKeyField')),
			'value' => $this->getConfig('fallbackValue'),
			'callback' => ($this->getConfig('fallbackValue'))($entity, $this->table()),
			'error' => throw new WhoDidItException('WhoDidIt could not resolve an actor ID.'),
		};
	}
}
