<?php


namespace MkyEngine\MkyFormatters;


use MkyEngine\Interfaces\MkyFormatterInterface;

class ArrayFormatter implements MkyFormatterInterface
{

    public function getFormats()
    {
        return [
            'join' => [$this, 'join'],
            'count' => [$this, 'count']
        ];
    }

    public function join(array $array, string $glue = ', ')
    {
        return join($glue, $array);
    }

    public function count(array $array)
    {
        return count($array);
    }
}