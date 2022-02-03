<?php


namespace Core\MkyCompiler;


use Core\Interfaces\MkyFormatterInterface;
use Core\MkyCompiler\MkyFormatters\ArrayFormatter;
use Core\MkyCompiler\MkyFormatters\BaseFormatter;

class MkyFormatter
{
    /**
     * @var string[]
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