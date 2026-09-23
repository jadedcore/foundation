<?php
declare(strict_types=1);

namespace Foundation\Model\Entity;

use Cake\ORM\Entity;

class AccountToken extends Entity {
	protected array $_accessible = [];
	protected array $_hidden = ['token_hash'];
}
