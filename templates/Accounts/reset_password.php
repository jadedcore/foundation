<h1>Reset password</h1>
<?= $this->Form->create() ?>
<?= $this->Form->control('password', ['type' => 'password', 'required' => true]) ?>
<?= $this->Form->control('password_confirmation', ['type' => 'password', 'required' => true]) ?>
<?= $this->Form->button('Reset password') ?>
<?= $this->Form->end() ?>
