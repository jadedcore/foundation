<?php
declare(strict_types=1);

namespace Foundation\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Middleware\UnauthorizedHandler\RedirectHandler;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Redirects anonymous users to login while preserving forbidden responses for
 * authenticated identities.
 */
final class RedirectOrForbiddenHandler extends RedirectHandler {
	/** Handle an authorization failure. */
	public function handle(
		Exception $exception,
		ServerRequestInterface $request,
		array $options = [],
	): ResponseInterface {
		if ($request->getAttribute('identity') !== null && $exception instanceof ForbiddenException) {
			throw $exception;
		}
		$response = new Response();

		return $response
			->withHeader('Location', $this->getUrl($request, $options))
			->withStatus((int)($options['statusCode'] ?? 302));
	}
}
