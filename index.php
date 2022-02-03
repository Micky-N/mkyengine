<?php

use MkyEngine\MkyEngine;


require './vendor/autoload.php';

$view = 'index';
$params = ['name' => $view];

try {
    $config = include './app/config.php';
    $mkyEngine = new MkyEngine($config);
    return $mkyEngine->view($view, $params);
} catch (Exception $ex) {
    var_export($ex);
}