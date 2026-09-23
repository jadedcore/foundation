<?php
declare(strict_types=1);

namespace Foundation\Model\Entity;

use Cake\ORM\Entity;

class PersistentLogin extends Entity {
	protected array $_accessible = [];
	protected array $_hidden = ['validator_hash'];
}
