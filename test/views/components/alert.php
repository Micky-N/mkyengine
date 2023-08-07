<div class="alert alert-<?= $type ?>">
    <div>
        <p><?= ucfirst($type) ?>:</p>
        <?= $this->slot('default') ?>
    </div>
    <?php if ($this->hasSlot('confirm')): ?>
        <button id="confirm"><?= $this->slot('confirm') ?></button>
    <?php endif ?>
    <?php if ($this->hasSlot('close')): ?>
        <button id="close"><?= $this->slot('close') ?></button>
    <?php endif ?>
</div>