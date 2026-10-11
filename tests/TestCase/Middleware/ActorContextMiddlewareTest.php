<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Foundation\Middleware\ActorContextMiddleware;
use Foundation\Model\Table\AccountsTable;
use Foundation\Utility\ActorContext;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ActorContextMiddlewareTest extends TestCase {
	private ActorContext $actorContext;
	private ?string $originalEncoding;

	protected function setUp(): void {
		parent::setUp();
		$actorContext = Configure::read('Foundation.actorContext');
		$this->assertInstanceOf(ActorContext::class, $actorContext);
		$this->actorContext = $actorContext;
		$this->actorContext->clear();
		$this->originalEncoding = Configure::read('App.encoding');
		Configure::write('App.encoding', 'UTF-8');
	}

	protected function tearDown(): void {
		$this->actorContext->clear();
		Configure::write('App.encoding', $this->originalEncoding);
		TableRegistry::getTableLocator()->clear();
		parent::tearDown();
	}

	public function testAuthenticatedRequestAutomaticallyAuditsSavesAndClearsContext(): void {
		$locator = TableRegistry::getTableLocator();
		$locator->clear();
		/** @var \Foundation\Model\Table\AccountsTable $accounts */
		$accounts = $locator->get('Foundation.Accounts');
		$accounts->deleteAll([]);
		$accounts->addBehavior('Foundation.WhoDidIt', ['actorContext' => $this->actorContext]);
		$account = $accounts->newEntity(['email' => 'middleware-audit@example.com', 'status' => 'pending']);
		$handler = new CallbackRequestHandler(function () use ($accounts, $account): ResponseInterface {
			$accounts->saveOrFail($account);

			return new Response();
		});
		$request = (new ServerRequest())->withAttribute('identity', new TestIdentity('authenticated-actor'));

		(new ActorContextMiddleware($this->actorContext))->process($request, $handler);

		$this->assertSame('authenticated-actor', $account->created_by);
		$this->assertSame('authenticated-actor', $account->modified_by);
		$this->assertNull($this->actorContext->actorId());
	}

	public function testRestoresPriorContextWhenDownstreamHandlingThrows(): void {
		$this->actorContext->setActorId('outer-actor');
		$handler = new CallbackRequestHandler(
			static fn() => throw new ActorContextMiddlewareTestException('request failed'),
		);
		$request = (new ServerRequest())->withAttribute('identity', new TestIdentity('request-actor'));

		try {
			(new ActorContextMiddleware($this->actorContext))->process($request, $handler);
			$this->fail('Expected downstream exception.');
		} catch (ActorContextMiddlewareTestException) {
			$this->assertSame('outer-actor', $this->actorContext->actorId());
		}
	}
}

class TestIdentity {
	public function __construct(private readonly string $identifier) {
	}

	public function getIdentifier(): string {
		return $this->identifier;
	}
}

class CallbackRequestHandler implements RequestHandlerInterface {
	/** @param callable(ServerRequestInterface): ResponseInterface $callback */
	public function __construct(private readonly mixed $callback) {
	}
	public function handle(ServerRequestInterface $request): ResponseInterface {
		return ($this->callback)($request);
	}
}

class ActorContextMiddlewareTestException extends RuntimeException {
}
