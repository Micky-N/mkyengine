<?php


namespace MkyEngine\Tests\App;


class TestDirective implements \MkyEngine\Interfaces\MkyDirectiveInterface
{

    public function getFunctions()
    {
        return [
            'test' => [$this, 'test']
        ];
    }

    public function test($test)
    {
        return "$test test";
    }
}