<?php
declare(strict_types=1);

namespace Foundation\Middleware;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HostHeaderMiddleware implements MiddlewareInterface {
	/** @param array<string> $allowedHosts */
	public function __construct(private readonly array $allowedHosts, private readonly bool $required = true) {
	}

	/** Process and validate the request host. */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		if ($this->allowedHosts === []) {
			if ($this->required) {
				throw new InternalErrorException('No trusted application hosts are configured.');
			}

			return $handler->handle($request);
		}
		$host = strtolower($request->getUri()->getHost());
		$allowed = array_map('strtolower', $this->allowedHosts);
		if ($host === '' || !in_array($host, $allowed, true)) {
			throw new BadRequestException('Invalid Host header.');
		}

		return $handler->handle($request);
	}
}
