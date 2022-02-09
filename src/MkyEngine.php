<?php

namespace MkyEngine;

use MkyEngine\Interfaces\MkyDirectiveInterface;
use MkyEngine\Interfaces\MkyFormatterInterface;
use MkyEngine\Exceptions\MkyEngineException;

class MkyEngine
{

    private const VIEW_SUFFIX = '.mky';
    private const CACHE_SUFFIX = '.cache.php';
    private const ECHO = ['{{', '}}'];
    private const SINGLE_FUNCTION = ['<mky:', '\/>'];
    private const OPEN_FUNCTION = ['<mky:', '>'];
    private const CLOSE_FUNCTION = ['<\/mky:', '>'];
    private array $config;
    /**
     * @var null|string|false
     */
    private $view;
    private array $sections = [];
    private array $data = [];
    private string $viewName = '';
    private string $viewPath = '';
    private array $includeData = [];
    private static array $variables = [];

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
        if(empty($config['views'])){
            throw new MkyEngineException("Config for views not found");
        }
        if(empty($config['includes'])){
            $config['includes'] = $config['views'];
        }
        if(empty($config['layouts'])){
            $config['layouts'] = $config['views'];
        }
        if(empty($config['cache'])){
            $config['cache'] = __DIR__ . '/cache/views';
        }
        $this->config = $config;
    }

    /**
     * Add directives
     *
     * @param MkyDirectiveInterface[]|MkyDirectiveInterface $directives
     * @return $this
     */
    public function addDirectives($directives)
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
     * @param MkyFormatterInterface[]|MkyFormatterInterface $formatters
     * @return $this
     */
    public function addFormatters($formatters)
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
    public function addGlobalVariable(string $name, $variable)
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
    public function view(string $viewName, array $data = [], $extends = false)
    {
        $viewPath = $this->config['layouts'] . '/' . $this->parseViewName($viewName);
        if(!$extends){
            $viewPath = $this->config['views'] . '/' . $this->parseViewName($viewName);
            $this->viewName = $viewName;
            $this->data = array_merge($this->data, $data);
            $this->viewPath = $viewPath;
        }
        $this->view = file_get_contents($viewPath);
        $this->parse();
        $this->data = array_merge($this->data, $this->includeData);

        $cachePath = $this->config['cache'] . '/' . md5($this->viewName) . self::CACHE_SUFFIX;
        if(!file_exists($cachePath)){
            $this->addCache($cachePath, $this->view);
        } else if(explode("\n", file_get_contents($cachePath)) !== explode("\n", $this->view) && trim($this->view)){
            $this->addCache($cachePath, $this->view);
        }

        if(
            (filemtime($cachePath) < filemtime($viewPath)) ||
            (filemtime($cachePath) < filemtime($this->viewPath))
        ){
            $this->addCache($cachePath, $this->view);
        }
        if(!$extends){
            ob_start();
            extract($this->data);
            if(file_exists($cachePath)){
                require $cachePath;
            }
            return ob_get_clean();
        }
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
        $this->parseIncludes();
        $this->parseVariables();
        $this->parseSections();
        $this->parseExtends();
        $this->parseYields();
        $this->parseDirectives();
    }

    /**
     * Compile echo variables
     */
    public function parseVariables(): void
    {
        $this->view = preg_replace_callback(sprintf("/%s(.*?)(#(.*?)(\((.*?)\)?)?)?%s/", self::ECHO[0], self::ECHO[1]), function ($variable) {
            $var = trim($variable[1]);
            $formats = isset($variable[2]) ? explode("#", trim($variable[2])) : null;
            if($formats){
                $formats = array_filter($formats);
                foreach ($formats as $format) {
                    $args = null;
                    $function = trim($format);
                    if(strpos($format, '(') !== false){
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
    }

    /**
     * Compile included files
     */
    public function parseIncludes(): void
    {
        $this->view = preg_replace_callback(sprintf("/%sinclude name=[\"\'](.*?)[\"\']( data=[\"\'](.*?)[\"\'])? ?%s/s", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($viewName) {
            $name = trim($viewName[1], '"\'');
            $view = file_get_contents($this->config['includes'] . '/' . $this->parseViewName($name));
            $data = [];
            if(isset($viewName[3])){
                extract($this->data);
                preg_match_all('/\$[\w]+/', $viewName[3], $matchesVar);
                $matchesVar = $matchesVar[0];
                @eval("\$data = $viewName[3]; return true;");
                foreach ($data as $key => $value) {
                    $index = array_search($key, array_keys(array_filter($data, fn($d) => $d === null)), true);
                    if(is_null($value)){
                        $view = preg_replace('/\\$' . $key . '/', $matchesVar[$index], $view);
                    } else {
                        $this->includeData[str_replace('.', '_', $name . '_' . $key)] = $value;
                    }
                }
                preg_match_all('/\$[\w]+/', $view, $matchesAll);
                if($matchesAll){
                    foreach ($matchesAll as $matches) {
                        foreach ($matches as $k => $match) {
                            $ma = str_replace('$', '', $match);
                            if(!array_key_exists($ma, $this->data) && array_key_exists(str_replace('.', '_', $name . '_' . $ma), $this->includeData)){
                                $view = preg_replace('/\\' . $match . '/', str_replace('.', '_', '$' . $name . '_' . $ma), $view);
                            }
                        }
                    }
                }
            }
            return $view;
        }, $this->view);
    }

    /**
     * Compile the layout
     */
    public function parseExtends(): void
    {
        $this->view = preg_replace_callback(sprintf("/%sextends name=[\"\'](.*?)[\"\'] ?%s/s", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($viewName) {
            $name = trim($viewName[1], '"\'');
            return $this->view($name, $this->data, true);
        }, $this->view);
    }

    /**
     * compile layout yield block
     */
    public function parseYields(): void
    {
        $this->view = preg_replace_callback(sprintf("/%syield name=[\"\'](.*?)[\"\']( default=[\"\'](.*?)[\"\'])? ?%s/", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($yieldName) {
            $name = trim($yieldName[1], '"\'');
            $default = isset($yieldName[2]) ? trim($yieldName[3], '"\'') : '';
            return $this->sections[$name] ?? $default;
        }, $this->view);
    }

    /**
     * Compile view sections
     */
    public function parseSections(): void
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
    }

    /**
     * Compile directives
     *
     * @see MkyCompile
     */
    private function parseDirectives(): void
    {
        $mkyDirective = new MkyDirective();
        foreach ($mkyDirective->getDirectives() as $directives) {
            foreach ($directives->getFunctions() as $key => $function) {
                $this->singleDirective($mkyDirective, $key, $this->view);
                $this->blockDirective($mkyDirective, $key, $this->view);
                $this->blockEmptyDirective($mkyDirective, $key, $this->view);
            }
        }
    }

    private function blockEmptyDirective(MkyDirective $mkyDirective, $key, string $view)
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
    }

    private function blockDirective(MkyDirective $mkyDirective, $key, string $view)
    {
        $str = sprintf('/%s%s ([\w]+=[\"\'](.*?)[\"\'])+? ?%s(.*?)%s%s%s/s', self::OPEN_FUNCTION[0], $key, self::OPEN_FUNCTION[1], self::CLOSE_FUNCTION[0], $key, self::CLOSE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            if(isset($expression[3])){
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
                if(strpos($attribute, '$') !== false){
                    extract($this->data);
                    @eval("\$var = $attribute; return true;");
                    $exprArray[$k] = $var;
                    self::setRealVariable((string)$attribute, $var);
                } else {
                    $attribute = (string)$attribute;
                    if(strpos($attribute, '/') === 0 || strpos($attribute, '.')){
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
    }

    private function singleDirective(MkyDirective $mkyDirective, $key, string $view)
    {
        $str = sprintf('/(%s%s ([\w]+=[\"\'](.*?)[\"\'])? ?%s)+/', self::SINGLE_FUNCTION[0], $key, self::SINGLE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            $xmlExpr = str_replace('mky:', '', $expression[0]);
            $xml = new \SimpleXMLElement($xmlExpr);
            $exprArray = [];
            $var = null;
            foreach ($xml->attributes() as $k => $attribute) {
                if(strpos($attribute, '$') !== false){
                    extract($this->data);
                    @eval("\$var = $attribute; return true;");
                    $exprArray[$k] = $var;
                    self::setRealVariable((string)$attribute, $var);
                } else {
                    $attribute = (string)$attribute;
                    if(strpos($attribute, '/') === 0 || strpos($attribute, '.')){
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
    }

    private function addCache(string $cachePath, string $view)
    {
        $array = explode('/', $cachePath);
        $start = '';
        foreach ($array as $file) {
            $start .= $file;
            if(strpos($file, '.') !== false){
                file_put_contents($start, $view);
            } else {
                if(!file_exists($start)){
                    mkdir($start, 1);
                }
            }
            $start .= '/';
        }
    }

    public static function getRealVariable($value)
    {
        $key = array_search($value, self::$variables, true);
        unset(self::$variables[$key]);
        return $key;
    }

    public static function setRealVariable($variable, $value)
    {
        self::$variables[$variable] = $value;
    }

    public function getConfig(string $key = null)
    {
        $config = $this->config;
        if(!is_null($key) && isset($this->config[$key])){
            $config = $this->config[$key];
        }
        return $config;
    }
}
