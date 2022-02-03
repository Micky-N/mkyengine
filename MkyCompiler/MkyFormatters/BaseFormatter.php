<?php


namespace Core\MkyCompiler\MkyFormatters;


class BaseFormatter implements \Core\Interfaces\MkyFormatterInterface
{

    public function getFormats()
    {
        return [
            'currency' => [$this, 'currency'],
            'uppercase' => [$this, 'uppercase'],
            'lowercase' => [$this, 'lowercase'],
            'firstCapitalize' => [$this, 'firstCapitalize']
        ];
    }

    public function currency($number, string $currency = '€')
    {
        return "$number $currency";
    }

    public function uppercase(string $text)
    {
        return strtoupper($text);
    }

    public function lowercase(string $text)
    {
        return strtolower($text);
    }

    public function firstCapitalize(string $text)
    {
        return ucfirst($text);
    }
}