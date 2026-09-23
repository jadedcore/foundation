<h1>Forgot password</h1>
<p>Enter your email address. If an eligible account exists, a reset link will be sent.</p>

<?= $this->Form->create() ?>
<?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
<?= $this->Form->button('Send reset link') ?>
<?= $this->Form->end() ?>
