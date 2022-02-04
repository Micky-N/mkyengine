<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    public function testTrue()
    {
        $this->assertTrue(1==1, "Got it");
        
    }
}