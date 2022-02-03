<?php

namespace Core\MkyCompiler;

use Core\Interfaces\MkyDirectiveInterface;
use Core\Interfaces\MkyFormatterInterface;
use Core\MkyCompiler\Exceptions\MkyEngineException;
use Core\MkyCompiler\MkyDirectives\Directive;
use Exception;
use Core\Facades\Cache;
use Core\Facades\Session;

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
    public array $errors;

    public function __construct(array $config)
    {
        if(!isset($config['cache'])){
            $config['cache'] = 'cache/views';
        }
        $this->config = $config;
        $this->errors = $_GET['errors'] ?? [];
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
        foreach ($directives as $directive){
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
        foreach ($formatters as $formatter){
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
     * @throws Exception
     */
    public function view(string $viewName, array $data = [], $extends = false)
    {
        $viewPath = '';
        if(!$extends){
            $viewPath = $this->getConfig('views') . '/' . $this->parseViewName($viewName);
            $this->viewName = $viewName;
            $this->data = array_merge($this->data, $data);
            $this->viewPath = $viewPath;
        } else {
            $viewPath = $this->getConfig('layouts') . '/' . $this->parseViewName($viewName);
        }
        $this->view = file_get_contents($viewPath);
        $this->data = array_merge($this->data, $this->includeData);
        $this->parse();

        $cachePath = $this->getConfig('cache') . '/' . md5($this->viewName) . self::CACHE_SUFFIX;
        if(!file_exists($cachePath)){
            Cache::addCache($cachePath, $this->view);
        } else if(explode("\n", file_get_contents($cachePath)) !== explode("\n", $this->view) && trim($this->view)){
            echo '<!-- cache file updated -->';
            Cache::addCache($cachePath, $this->view);
        }

        if(
            (filemtime($cachePath) < filemtime($viewPath)) ||
            (filemtime($cachePath) < filemtime($this->viewPath))
        ){
            echo '<!-- cache file updated -->';
            Cache::addCache($cachePath, $this->view);
        }
        if(!$extends){
            ob_start();
            extract($this->data);
            require $cachePath;
            echo ob_get_clean();
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
     * @param string|null $view
     */
    public function parseVariables(string $view = null): void
    {
        $this->view = preg_replace_callback(sprintf("/%s(.*?)(#(.*?)(\((.*?)\)?)?)?%s/", self::ECHO[0], self::ECHO[1]), function ($variable) {
            $var = trim($variable[1]);
            if(isset($variable[2])){
                $args = isset($variable[4]) ? trim(trim($variable[4]), '\(\)') : null;
                $params = ["'" . trim($variable[3]) . "'", $var, "[" . $args . "]"];
                $var = sprintf("call_user_func_array([new %s(), '%s'], [%s])", MkyFormatter::class, 'callFormat', join(', ', array_filter($params)));
            }
            return "<?= $var ?>";
        }, $view ?? $this->view);
    }


    /**
     * Get config value
     *
     * @param string $key
     * @return mixed
     */
    private function getConfig(string $key)
    {
        return $this->config[$key] ?? $this->config;
    }

    /**
     * Compile included files
     */
    public function parseIncludes(): void
    {
        $this->view = preg_replace_callback(sprintf("/%sinclude name=[\"\'](.*?)[\"\']( data=[\"\'](.*?)[\"\'])? ?%s/s", self::SINGLE_FUNCTION[0], self::SINGLE_FUNCTION[1]), function ($viewName) {
            $name = trim($viewName[1], '"\'');
            $view = file_get_contents($this->getConfig('views') . '/' . $this->parseViewName($name));
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
            }
        }
    }

    private function blockDirective(MkyDirective $mkyDirective, $key, string $view)
    {
        $str = sprintf('/%s%s ([\w]+=[\"\'](.*?)[\"\'])+? ?%s(.*?)%s%s%s/s', self::OPEN_FUNCTION[0], $key, self::OPEN_FUNCTION[1], self::CLOSE_FUNCTION[0], $key, self::CLOSE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            if(isset($expression[3])){
                $xmlempty = str_replace($expression[3], '-- CODE --', $expression[0]);
            }

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
                    Directive::setRealVariable((string)$attribute, $var);
                } else {
                    $attribute = (string)$attribute;
                    if(str_starts_with($attribute, '/') || strpos($attribute, '.')){
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
        $str = sprintf('/(%s%s ([\w]+=[\"\'](.*?)[\"\'])+? ?%s)+/', self::SINGLE_FUNCTION[0], $key, self::SINGLE_FUNCTION[1]);
        $this->view = preg_replace_callback($str, function ($expression) use ($mkyDirective, $key) {
            $xmlExpr = str_replace('mky:', '', $expression[0]);
            $xml = new \SimpleXMLElement($xmlExpr);
            $exprArray = [];
            foreach ($xml->attributes() as $k => $attribute) {
                if(strpos($attribute, '$') !== false){
                    extract($this->data);
                    $getvar = str_replace('$', '', $attribute);
                    if(array_key_exists($getvar, $this->data) || strpos($getvar, '->') !== false ||strpos($getvar, '[') !== false){
                        @eval("\$var = $attribute; return true;");
                        $var = is_string($var) ? "'$var'" : $var;
                        $exprArray[$k] = $var;
                        Directive::setRealVariable((string)$attribute, $var);
                    }else{
                        throw new MkyEngineException(sprintf('Undefined variable: %s', $attribute));
                    }
                } else {
                    $attribute = (string)$attribute;
                    if(str_starts_with($attribute, '/') || strpos($attribute, '.')){
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
}
