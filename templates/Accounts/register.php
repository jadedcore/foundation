<?php
/** @var \Foundation\Model\Entity\Account $account */
?>
<h1>Create account</h1>
<?= $this->Form->create($account) ?>
<?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
<?= $this->Form->control('password', ['type' => 'password', 'required' => true]) ?>
<?= $this->Form->control('password_confirmation', ['type' => 'password', 'required' => true]) ?>
<?= $this->Form->button('Create account') ?>
<?= $this->Form->end() ?>
