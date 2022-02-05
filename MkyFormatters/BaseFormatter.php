<?php


namespace MkyEngine\MkyFormatters;


class BaseFormatter implements \MkyEngine\Interfaces\MkyFormatterInterface
{

    public function getFormats()
    {
        return [
            'currency' => [$this, 'currency'],
            'uppercase' => [$this, 'uppercase'],
            'lowercase' => [$this, 'lowercase'],
            'firstCapitalize' => [$this, 'firstCapitalize'],
            'join' => [$this, 'join'],
            'count' => [$this, 'count']
        ];
    }

    public function currency($number, string $currency = 'EUR', string $locale = 'fr_FR')
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($number, strtoupper($currency));
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

    public function join(array $array, string $glue = ', ')
    {
        return join($glue, $array);
    }

    public function count(array $array)
    {
        return count($array);
    }
}