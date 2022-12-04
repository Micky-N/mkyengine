<?php


namespace MkyEngine;


use MkyEngine\Interfaces\MkyFormatterInterface;
use MkyEngine\MkyFormatters\BaseFormatter;

class MkyFormatter
{
    /**
     * @var MkyFormatterInterface[]
     */
    private static array $formatters = [];

    public function __construct()
    {
        self::$formatters[] = new BaseFormatter();
    }

    public static function addFormatter(MkyFormatterInterface $formatter): void
    {
        self::$formatters[] = $formatter;
    }

    public function callFormat(string $format, mixed $variable, mixed $params = null): mixed
    {
        $params = (array) $params;
        foreach (self::$formatters as $formatter) {
            if(array_key_exists($format, $formatter->getFormats())){
                return call_user_func_array($formatter->getFormats()[$format], [$variable, ...$params]);
            }
        }
        return $variable;
    }
}