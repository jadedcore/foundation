<?php
declare(strict_types=1);

namespace Foundation;

use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Foundation\Contract\AccountMailerInterface;
use Foundation\Mailer\AccountMailer;
use Foundation\Utility\ActorContext;
use Foundation\Utility\ActorContextInterface;

class FoundationPlugin extends BasePlugin {
	/** @inheritDoc */
	public function services(ContainerInterface $container): void {
		$container->addShared(ActorContextInterface::class, ActorContext::class);
		$container->add(AccountMailerInterface::class, AccountMailer::class);
	}
}
