<?php $this->extends('layout') ?>

<?php $this->block('content') ?>
    <h1>Hello World</h1>
<?= $this->component('name-input')->bind('name', 'test') ?>
<?= $this->component('name-input')->multipleBind(['name' => 'test55']) ?>
<?= $this->component('name-input')->each($users, ['name']) ?>
<?= $this->component('name-input')->each($users, ['name' => 'address.postcode']) ?>

<?php $this->component('alert')->bind('type', 'danger')->start() ?>
<?php $this->addslot('confirm') ?>
    Yes
<?php $this->endslot() ?>
<?php $this->addslot('close', 'Close') ?>
<?php $this->component('alert')->end() ?>

<?php $this->component('alert')->bind('type', 'info')->start() ?>
    qsd
<?php $this->addslot('confirm') ?>
    Yes
<?php $this->endslot() ?>
<?php $this->addslot('close', 'Close') ?>
<?php $this->component('alert')->end() ?>

<?php $this->endblock() ?>