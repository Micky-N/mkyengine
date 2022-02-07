<?php


namespace MkyEngine\MkyFormatters;


use DateTime;
use Exception;
use MkyEngine\Interfaces\MkyFormatterInterface;
use NumberFormatter;

class BaseFormatter implements MkyFormatterInterface
{

    public function getFormats()
    {
        return [
            'currency' => [$this, 'currency'],
            'uppercase' => [$this, 'uppercase'],
            'lowercase' => [$this, 'lowercase'],
            'firstCapitalize' => [$this, 'firstCapitalize'],
            'join' => [$this, 'join'],
            'count' => [$this, 'count'],
            'date' => [$this, 'date']
        ];
    }

    public function currency($number, string $currency = 'EUR', string $locale = 'fr_FR')
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
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

    /**
     * @param DateTime|string $dateTime
     * @param string $format
     * @return string
     * @throws Exception
     */
    public function date($dateTime, string $format = 'Y-m-d H:i:s'): string
    {
        if(is_string($dateTime)){
            $dateTime = new DateTime($dateTime);
        }
        return $dateTime->format($format);
    }
}