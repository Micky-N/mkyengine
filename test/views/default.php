<?php $this->extends('layout') ?>

<?php $this->block('content') ?>
  <h1>Hello World</h1>
  <?= $this->component('name-input')->bind('name', 'Micky') ?>
  <?= $this->component('name-input')->multipleBind(['name' => 'Micky']) ?>
<?php $this->endblock() ?>