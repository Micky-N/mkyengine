<?php

namespace Tests;

use MkyEngine\MkyEngine;
use PHPUnit\Framework\TestCase;

class MkyConfigTest extends TestCase
{
    public function testConfig()
    {
        $config = [
            'views' => __DIR__.'/app/views',
            'cache' =>__DIR__.'/app/config'
        ];
        $mky = new MkyEngine($config);
    }
}