<?php

use MkyEngine\MkyEngine;


require './vendor/autoload.php';

$view = 'index';
$params = ['name' => $view, 'd' => new DateTime()];

try {
    $config = include './app/config.php';
    $mkyEngine = new MkyEngine($config);
    echo $mkyEngine->view($view, $params);
} catch (Exception $ex) {
    var_export($ex);
}