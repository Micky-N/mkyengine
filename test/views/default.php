<?php $this->extends('layout') ?>

<?php $this->block('content') ?>
    <h1>Hello World</h1>
<?= $this->component('name-input')->bind('name', 'Micky') ?>
<?= $this->component('name-input')->multipleBind(['name' => 'Micky']) ?>
<?= $this->component('name-input')->each($users, ['name']) ?>
<?= $this->component('name-input')->each($users, ['name' => 'address.postcode']) ?>
<?= $this->component('user')->each($users, 'user') ?>
<?php $this->component('warning')->bind('attr', ['style' => ['color' => 'white']]) ?>
    qsdqsd
<?php $this->addslot('help') ?>
    test
<?php $this->endslot() ?>
<?php $this->endComponent() ?>
<?php $this->endblock() ?>