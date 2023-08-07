<?php $this->extends('layout') ?>
<?php $this->inject('form', \MkyEngine\Test\FormBuilder::class) ?>
<?php $this->block('content') ?>
<?= $this->form->open(['method' => 'POST', 'action' => '/back']) ?>
    <?= $this->form->input('text', 'firstname', 'Micky') ?>
    <?= $this->form->input('text', 'lastname', 'Ndinga') ?>
    <?= $this->form->submit('Save') ?>
<?= $this->form->close() ?>
<?php $this->endblock() ?>