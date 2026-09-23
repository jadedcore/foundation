<?php
declare(strict_types=1);

namespace Foundation\Utility;

interface ActorContextInterface {
	/** Return the current actor identifier. */
	public function actorId(): string|int|null;

	/** Check whether an actor is set. */
	public function hasActor(): bool;

	/** Set the current actor identifier. */
	public function setActorId(string|int|null $actorId): void;

	/** Clear the current actor. */
	public function clear(): void;

	/** Run a callback with a temporary actor. */
	public function runAs(string|int|null $actorId, callable $callback): mixed;
}
