<?php

namespace MkyEngine;

use Exception;
use MkyEngine\Exceptions\MkyEngineException;
use MkyEngine\Interfaces\MkyDirectiveInterface;
use MkyEngine\Interfaces\MkyFormatterInterface;
use ReflectionException;

class MkyEngine
{

    private const VIEW_SUFFIX = '.mky.php';
    private const CACHE_SUFFIX = '.cache.php';
    private const ECHO = ['{{', '}}'];
    private const SINGLE_FUNCTION = ['<mky:', '\/>'];
    private const OPEN_FUNCTION = ['<mky:', '>'];
    private const CLOSE_FUNCTION = ['<\/mky:', '>'];
    private static array $variables = [];
    private array $config;
    /**
     * @var null|string|false
     */
    private string|null|false $view;
    private array $sections = [];
    private array $data = [];
    private string $viewName = '';
    private string $viewPath = '';
    private array $includeData = [];

    /**
     * Mky Contructor
     * require config: views
     * optional config: cache
     *
     * @param array $config
     * @throws MkyEngineException
     */
    public function __construct(array $config)
    {
        if (empty($config['views'])) {
            throw new MkyEngineException("Config for views not found");
        }
        if (empty($config['cache'])) {
            $config['cache'] = __DIR__ . '/cache/views';
        }
        $this->config = $config;
    }

    public static function getRealVariable($value): bool|int|string
    {
        $key = array_search($value, self::$variables, true);
        unset(self::$variables[$key]);
        return $key;
    }

    /**
     * Add directives
     *
     * @param MkyDirectiveInterface|MkyDirectiveInterface[] $directives
     * @return $this
     */
    public function addDirectives(array|MkyDirectiveInterface $directives): static
    {
        $directives = !is_array($directives) ? [$directives] : $directives;
        foreach ($directives as $directive) {
            MkyDirective::addDirective($directive);
        }
        return $this;
    }

    /**
     * Add formatters
     *
     * @param MkyFormatterInterface|MkyFormatterInterface[] $formatters
     * @return $this
     */
    public function addFormatters(array|MkyFormatterInterface $formatters): static
    {
        $formatters = !is_array($formatters) ? [$formatters] : $formatters;
        foreach ($formatters as $formatter) {
            MkyFormatter::addFormatter($formatter);
        }
        return $this;
    }

    /**
     * Add global variable to view
     *
     * @param string $name
     * @param mixed $variable
     * @return MkyEngine
     */
    public function addGlobalVariable(string $name, mixed $variable): static
    {
        $this->data[$name] = $variable;
        return $this;
    }

    /**
     * Compile and send the view
     *
     * @param string $viewName
     * @param array $data
     * @param bool $extends
     * @return false|string
     */
    public function view(string $viewName, array $data = [], bool $extends = false): string|false
    {
        $viewPath = $this->config['views'] . '/' . $this->parseViewName($viewName);
        if (!$extends) {
            $this->viewName = $viewName;
            $this->data = array_merge($this->data, $data);
            $this->viewPath = $viewPath;
        }
        $this->view = file_get_contents($viewPath);
        $this->parse();
        $this->data = array_merge($this->data, $this->includeData);

        $cachePath = $this->config['cache'] . '/' . md5($this->viewName) . self::CACHE_SUFFIX;
        if (!file_exists($cachePath)) {
            $this->addCache($cachePath, $this->view);
        } else if (explode("\n", file_get_contents($cachePath)) !== explode("\n", $this->view) && trim($this->view)) {
            $this->addCache($cachePath, $this->view);
        }

        if (
            (filemtime($cachePath) < filemtime($viewPath)) ||
            (filemtime($cachePath) < filemtime($this->viewPath))
        ) {
            $this->addCache($cachePath, $this->view);
        }
        if (!$extends) {
            ob_start();
            extract($this->data);
            if (file_exists($cachePath)) {
                require $cachePath;
            }
            return ob_get_clean();
        }
        return false;
    }

    /**
     * Format the view file
     *
     * @param string $viewName
     * @return string
     */
    private function parseViewName(string $viewName): string
    {
        $viewName = str_replace('.', '/', $viewName);
        return $viewName . self::VIEW_SUFFIX;
    }

    /**
     * Compile the view
     */
    public function parse()
    {
        $this->parseIncludes()
            ->parseVariables()
            ->parseSections()
            ->parseExtends()
            ->parseYields()
            ->parseDirectives();
    }

    /**
     * Compile directives
     *
     * @return MkyEngine
     * @throws ReflectionException
     * @see MkyCompile
     */
    private function parseDirectives(): static
    {
        $mkyDirective = new MkyDirective();
        foreach ($mkyDirective->getDirectives() as $directives) {
            foreach ($directives->getFunctions() as $key => $function) {
                $this->singleDirective($mkyDirective, $key, $this->view)
                    ->blockDirective($mkyDirective, $key, $this->view)
                    ->blockEmptyDirective($mkyDirective, $key, $this->view);
            }
        }
        return $this;
    }

    /**
     * @param MkyDirective $mkyDirective
     * @param $key
     * @param string $view
     * @return $this
     * @throws ReflectionException
     */
    private function blockEmptyDirective(MkyDirective $mkyDirective, $key, string $view): static
    {
        $str = sprintf('/%s%s ?%s(.*?)%s%s%s/s', self::OPEN_FUNCTION[0], $key, self::OPEN_FUNCTION[1], self::CLOSE_FUNCTION[0], $key, self::CLOSE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            preg_match(sprintf('/%s%s ?%s/', self::OPEN_FUNCTION[0], $key, self::OPEN_FUNCTION[1]), $expression[0], $xmlExprUp);
            $xmlExprUp = $xmlExprUp[0];

            preg_match(sprintf('/%s%s%s/', self::CLOSE_FUNCTION[0], $key, self::CLOSE_FUNCTION[1]), $expression[0], $xmlExprDown);
            $xmlExprDown = $xmlExprDown[0];
            $params = [$key, []];
            $ref = new \ReflectionClass($mkyDirective);
            $getUp = $ref->getMethod('callFunction')->invokeArgs($ref->newInstance(), $params);
            $getDown = $ref->getMethod('callFunction')->invokeArgs($ref->newInstance(), array_merge($params, [false]));
            return str_replace([$xmlExprUp, $xmlExprDown], [$getUp, $getDown], $expression[0]);
        }, $view);
        return $this;
    }

    /**
     * @param MkyDirective $mkyDirective
     * @param $key
     * @param string $view
     * @return MkyEngine
     * @throws ReflectionException
     * @throws Exception
     */
    private function blockDirective(MkyDirective $mkyDirective, $key, string $view): static
    {
        $str = sprintf('/%s%s ([\w]+=[\"\'](.*?)[\"\'])+? ?%s(.*?)%s%s%s/s', self::OPEN_FUNCTION[0], $key, self::OPEN_FUNCTION[1], self::CLOSE_FUNCTION[0], $key, self::CLOSE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            $xmlempty = '';
            if (isset($expression[3])) {
                $xmlempty = str_replace($expression[3], '-- CODE --', $expression[0]);
            }
            $var = null;
            preg_match(sprintf('/%s%s ([\w]+=[\"\'](.*?)[\"\'])+? ?%s/', self::OPEN_FUNCTION[0], $key, self::OPEN_FUNCTION[1]), $expression[0], $xmlExprUp);
            $xmlExprUp = $xmlExprUp[0];
            preg_match(sprintf('/%s%s%s/', self::CLOSE_FUNCTION[0], $key, self::CLOSE_FUNCTION[1]), $expression[0], $xmlExprDown);
            $xmlExprDown = $xmlExprDown[0];

            $xmlExpr = str_replace('mky:', '', $xmlempty);
            $xml = new \SimpleXMLElement($xmlExpr);
            $exprArray = [];
            foreach ($xml->attributes() as $k => $attribute) {
                if (str_contains($attribute, '$')) {
                    extract($this->data);
                    @eval("\$var = $attribute; return true;");
                    $exprArray[$k] = $var;
                    self::setRealVariable((string)$attribute, $var);
                } else {
                    $attribute = (string)$attribute;
                    if (str_starts_with($attribute, '/') || strpos($attribute, '.')) {
                        $attribute = (string)"'$attribute'";
                    }
                    @eval("\$var = $attribute; return true;");
                    $exprArray[$k] = $var;
                }
            }
            $params = [$key, $exprArray];
            $ref = new \ReflectionClass($mkyDirective);
            $getUp = $ref->getMethod('callFunction')->invokeArgs($ref->newInstance(), $params);
            $getDown = $ref->getMethod('callFunction')->invokeArgs($ref->newInstance(), array_merge($params, [false]));
            return str_replace([$xmlExprUp, $xmlExprDown], [$getUp, $getDown], $expression[0]);
        }, $view);
        return $this;
    }

    public static function setRealVariable($variable, $value): void
    {
        self::$variables[$variable] = $value;
    }

    /**
     * @param MkyDirective $mkyDirective
     * @param $key
     * @param string $view
     * @return $this
     * @throws ReflectionException
     * @throws Exception
     */
    private function singleDirective(MkyDirective $mkyDirective, $key, string $view): static
    {
        $str = sprintf('/(%s%s ([\w]+=[\"\'](.*?)[\"\'])? ?%s)+/', self::SINGLE_FUNCTION[0], $key, self::SINGLE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            $xmlExpr = str_replace('mky:', '', $expression[0]);
            $xml = new \SimpleXMLElement($xmlExpr);
            $exprArray = [];
            $var = null;
            foreach ($xml->attributes() as $k => $attribute) {
                if (str_contains($attribute, '$')) {
                    extract($this->data);
                    @eval("\$var = $attribute; return true;");
                    $exprArray[$k] = $var;
                    self::setRealVariable((string)$attribute, $var);
                } else {
                    $attribute = (string)$attribute;
                    if (str_starts_with($attribute, '/') || strpos($attribute, '.')) {
                        $attribute = (string)"'$attribute'";
                    }
                    @eval("\$var = $attribute; return true;");
                    $exprArray[$k] = $var;
                }
            }
            $params = [$key, $exprArray];
            $ref = new \ReflectionClass($mkyDirective);
            $newExpr = $ref->getMethod('callFunction')->invokeArgs($ref->newInstance(), $params);
            return str_replace($expression[1], $newExpr, $expression[0]);
        }, $view);
        return $this;
    }

    /**
     * compile layout yield block
     *
     * @return MkyEngine
     */
    public function parseYields(): static
    {
        $this->view = preg_replace_callback(sprintf("/%syield name=[\"\'](.*?)[\"\']( default=[\"\'](.*?)[\"\'])? ?%s/", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($yieldName) {
            $name = trim($yieldName[1], '"\'');
            $default = isset($yieldName[2]) ? trim($yieldName[3], '"\'') : '';
            return $this->sections[$name] ?? $default;
        }, $this->view);
        return $this;
    }

    /**
     * Compile the layout
     *
     * @return MkyEngine
     */
    public function parseExtends(): static
    {
        $this->view = preg_replace_callback(sprintf("/%sextends name=[\"\'](.*?)[\"\'] ?%s/s", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($viewName) {
            $name = trim($viewName[1], '"\'');
            return $this->view($name, $this->data, true);
        }, $this->view);
        return $this;
    }

    /**
     * Compile view sections
     *
     * @return MkyEngine
     */
    public function parseSections(): static
    {
        $this->view = preg_replace_callback(sprintf('/%ssection name=[\"\'](.*?)[\"\'] value=[\"\'](.*?)[\"\'] ?%s/', self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($sectionDetail) {
            $value = trim($sectionDetail[2], '"\'');
            $name = trim($sectionDetail[1], '"\'');
            $this->sections[$name] = $value;
            return '';
        }, $this->view);
        $this->view = preg_replace_callback(sprintf('/%ssection name=[\"\'](.*?)[\"\'] ?%s(.*?)%ssection%s/s', self::OPEN_FUNCTION[0], self::OPEN_FUNCTION[1], self::CLOSE_FUNCTION[0], self::CLOSE_FUNCTION[1]), function ($sectionName) {
            $name = trim($sectionName[1], '"\'');
            $this->sections[$name] = $sectionName[2];
            return '';
        }, $this->view);

        return $this;
    }

    /**
     * Compile echo variables
     */
    public function parseVariables(): static
    {
        $this->view = preg_replace_callback(sprintf("/%s(.*?)(#(.*?)(\((.*?)\)?)?)?%s/", self::ECHO[0], self::ECHO[1]), function ($variable) {
            $var = trim($variable[1]);
            $formats = isset($variable[2]) ? explode("#", trim($variable[2])) : null;
            if ($formats) {
                $formats = array_filter($formats);
                foreach ($formats as $format) {
                    $args = null;
                    $function = trim($format);
                    if (str_contains($format, '(')) {
                        preg_match('/(.*?)\((.*)?\)+/', $format, $matches);
                        $args = trim($matches[2]);
                        $function = trim($matches[1]);
                    }
                    $params = ["'" . $function . "'", $var, "[$args]"];
                    $var = sprintf("call_user_func_array([new %s(), '%s'], [%s])", MkyFormatter::class, 'callFormat', join(', ', array_filter($params)));
                }
            }
            return "<?= $var ?>";
        }, $this->view);
        return $this;
    }

    /**
     * Compile included files
     * @return MkyEngine
     */
    public function parseIncludes(): static
    {
        $this->view = preg_replace_callback(sprintf("/%sinclude name=[\"\'](.*?)[\"\']( data=[\"\'](.*?)[\"\'])? ?%s/s", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($viewName) {
            $name = trim($viewName[1], '"\'');
            $view = file_get_contents($this->config['views'] . '/' . $this->parseViewName($name));
            $data = [];
            if (isset($viewName[3])) {
                extract($this->data);
                preg_match_all('/\$[\w]+/', $viewName[3], $matchesVar);
                $matchesVar = $matchesVar[0];
                @eval("\$data = $viewName[3]; return true;");
                foreach ($data as $key => $value) {
                    $index = array_search($key, array_keys(array_filter($data, fn($d) => $d === null)), true);
                    if (is_null($value)) {
                        $view = preg_replace('/\\$' . $key . '/', $matchesVar[$index], $view);
                    } else {
                        $this->includeData[str_replace('.', '_', $name . '_' . $key)] = $value;
                    }
                }
                preg_match_all('/\$[\w]+/', $view, $matchesAll);
                if ($matchesAll) {
                    foreach ($matchesAll as $matches) {
                        foreach ($matches as $k => $match) {
                            $ma = str_replace('$', '', $match);
                            if (!array_key_exists($ma, $this->data) && array_key_exists(str_replace('.', '_', $name . '_' . $ma), $this->includeData)) {
                                $view = preg_replace('/\\' . $match . '/', str_replace('.', '_', '$' . $name . '_' . $ma), $view);
                            }
                        }
                    }
                }
            }
            return $view;
        }, $this->view);
        return $this;
    }

    /**
     * @param string $cachePath
     * @param string $view
     * @return void
     */
    private function addCache(string $cachePath, string $view): void
    {
        $array = explode('/', $cachePath);
        $start = '';
        foreach ($array as $file) {
            $start .= $file;
            if (str_contains($file, '.')) {
                file_put_contents($start, $view);
            } else {
                if (!file_exists($start)) {
                    mkdir($start, 1);
                }
            }
            $start .= '/';
        }
    }

    public function getConfig(string $key = null)
    {
        $config = $this->config;
        if (!is_null($key) && isset($this->config[$key])) {
            $config = $this->config[$key];
        }
        return $config;
    }
}
