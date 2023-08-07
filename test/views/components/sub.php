<?php $i ??= 1 ?>
<div>
    <?= $i ?>
    <?= $this->slot('default', 'test') ?>
    <?php if($i <= 10): ?>
        <?php $this->component('sub')->bind('i', $i * 2)->start() ?>
            <?= $i + 1 ?> next
        <?php $this->component('sub')->end() ?>
    <?php endif ?>

</div>