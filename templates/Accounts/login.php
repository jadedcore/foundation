<h1>Sign in</h1>
<?= $this->Form->create() ?>
<?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
<?= $this->Form->control('password', ['type' => 'password', 'required' => true]) ?>
<?= $this->Form->button('Sign in') ?>
<?= $this->Form->end() ?>

<p><?= $this->Html->link('Forgot password?', ['action' => 'forgotPassword']) ?></p>
