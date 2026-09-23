<?php
declare(strict_types=1);

namespace Foundation\Test\TestCase\Utility;

use Foundation\Utility\ActorContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ActorContextTest extends TestCase {
	public function testRunAsRestoresPreviousActor(): void {
		$context = new ActorContext();
		$context->setActorId('original');
		$result = $context->runAs('temporary', function () use ($context): string {
			$this->assertSame('temporary', $context->actorId());

			return 'result';
		});
		$this->assertSame('result', $result);
		$this->assertSame('original', $context->actorId());
	}

	public function testRunAsRestoresActorAfterException(): void {
		$context = new ActorContext();
		$context->setActorId(0);
		try {
			$context->runAs(42, static fn() => throw new RuntimeException('failure'));
		} catch (RuntimeException) {
		}
		$this->assertSame(0, $context->actorId());
		$this->assertTrue($context->hasActor());
	}
}
