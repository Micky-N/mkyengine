<?php $this->extends('layout') ?>

<?php $this->block('content') ?>
    <h1>Hello World</h1>
<?= $this->component('name-input')->bind('name', 'Micky') ?>
<?= $this->component('name-input')->multipleBind(['name' => 'Micky']) ?>
<?= $this->component('name-input')->each($users, ['name']) ?>
<?= $this->component('name-input')->each($users, ['name' => 'address.postcode']) ?>

<?php $this->component('warning')->attrMerge(['class' => 'alert-danger'])->bind('type', 'danger')->start() ?>
<?php $this->addslot('confirm') ?>
    Yes
<?php $this->endslot() ?>
<?php $this->addslot('close', 'Close') ?>
<?php $this->component('warning')->end() ?>

<?php $this->component('warning')->attrMerge(['class' => 'alert-danger'])->bind('type', 'danger')->start() ?>
    qsd
<?php $this->addslot('confirm') ?>
    Yes
<?php $this->endslot() ?>
<?php $this->addslot('close', 'Close') ?>
<?php $this->component('warning')->end() ?>

<?php $this->endblock() ?>