<div <?= $this->attr(['class' => ['test', 'my'], 'style' => ['background' => 'blue']], $attr) ?>>
    <p>Warning:</p>
    <div><?= $this->slot('default') ?></div>
    <small><?= $this->slot('help') ?></small>
    <?php if ($this->hasSlot('help2')): ?>
        <small><?= $this->slot('help2') ?></small>
    <?php endif ?>
</div>