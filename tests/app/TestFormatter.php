<?php


namespace Tests\app;


class TestFormatter implements \MkyEngine\Interfaces\MkyFormatterInterface
{

    public function getFormats()
    {
        return [
            'test' => [$this, 'test'],
            'test2' => [$this, 'test2'],
        ];
    }

    public function test($var)
    {
        return $var." test";
    }

    public function test2($var, $params)
    {
        return $var." test $params";
    }
}