<?php


namespace MkyEngine;


use MkyEngine\Interfaces\MkyFormatterInterface;
use MkyEngine\MkyFormatters\ArrayFormatter;
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
        self::$formatters[] = new ArrayFormatter();
    }

    public static function addFormatter(MkyFormatterInterface $formatter)
    {
        self::$formatters[] = $formatter;
    }

    public function callFormat(string $format, $variable, $params = null)
    {
        $params = !is_array($params) ? [$params] : $params;
        foreach (self::$formatters as $formatter) {
            if(array_key_exists($format, $formatter->getFormats())){
                return call_user_func_array($formatter->getFormats()[$format], [$variable, ...$params]);
            }
        }
        return $variable;
    }
}