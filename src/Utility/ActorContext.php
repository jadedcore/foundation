<?php
declare(strict_types=1);

namespace Foundation\Utility;

final class ActorContext implements ActorContextInterface {
	private string|int|null $actorId = null;

	/** @inheritDoc */
	public function actorId(): string|int|null {
		return $this->actorId;
	}

	/** @inheritDoc */
	public function hasActor(): bool {
		return $this->actorId !== null;
	}

	/** @inheritDoc */
	public function setActorId(string|int|null $actorId): void {
		$this->actorId = $actorId;
	}

	/** @inheritDoc */
	public function clear(): void {
		$this->actorId = null;
	}

	/** @inheritDoc */
	public function runAs(string|int|null $actorId, callable $callback): mixed {
		$previous = $this->actorId;
		$this->actorId = $actorId;
		try {
			return $callback();
		} finally {
			$this->actorId = $previous;
		}
	}
}
