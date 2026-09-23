<?php
declare(strict_types=1);

namespace Foundation\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Foundation\Contract\AccountMailerInterface;
use Foundation\Model\Entity\User;

final class AccountMailer implements AccountMailerInterface {
	/** @inheritDoc */
	public function sendEmailVerification(User $user, string $plainToken): void {
		$this->send($user, 'Verify your email address', 'Foundation.email_verification', Router::url([
			'plugin' => 'Foundation',
			'controller' => 'Accounts',
			'action' => 'verifyEmail',
			$plainToken,
		], true));
	}

	/** @inheritDoc */
	public function sendPasswordReset(User $user, string $plainToken): void {
		$this->send($user, 'Reset your password', 'Foundation.password_reset', Router::url([
			'plugin' => 'Foundation',
			'controller' => 'Accounts',
			'action' => 'resetPassword',
			$plainToken,
		], true));
	}

	/** Send a lifecycle message using the configured mail profile. */
	private function send(User $user, string $subject, string $template, string $url): void {
		$mailer = new Mailer(Configure::read('Foundation.email.profile', 'default'));
		$from = Configure::read('Foundation.email.from');
		if ($from !== null) {
			$mailer->setFrom($from);
		}
		$mailer->setTo($user->email)->setSubject($subject)->setEmailFormat('both');
		$mailer->viewBuilder()->setTemplate($template);
		$mailer->setViewVars([
			'applicationName' => Configure::read('Foundation.email.applicationName', 'Application'),
			'url' => $url,
		]);
		$mailer->send();
	}
}
