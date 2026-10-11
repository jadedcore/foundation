<?php
declare(strict_types=1);

namespace Foundation\Middleware;

use Closure;
use Foundation\Utility\ActorContextInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ActorContextMiddleware implements MiddlewareInterface {
	/** Create the middleware with an actor context and optional resolver. */
	public function __construct(
		private readonly ActorContextInterface $context,
		private readonly ?Closure $resolver = null,
	) {
	}

	/** Populate actor context for the duration of the request. */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$identity = $request->getAttribute('identity');
		$actorId = $this->resolver !== null ? ($this->resolver)($identity, $request) : $this->defaultActorId($identity);

		return $this->context->runAs($actorId, fn(): ResponseInterface => $handler->handle($request));
	}

	/** Resolve a conventional identity identifier. */
	private function defaultActorId(mixed $identity): string|int|null {
		return match (true) {
			$identity === null => null,
			method_exists($identity, 'getIdentifier') => $identity->getIdentifier(),
			isset($identity->id) => $identity->id,
			default => null,
		};
	}
}
