<?php


namespace Core\MkyCompiler\MkyDirectives;


use Core\Interfaces\MkyDirectiveInterface;

class BaseDirective extends Directive implements MkyDirectiveInterface
{
    private static array $conditions = [
        'firstCaseSwitch' => false
    ];
    private array $sections = [];

    public function getFunctions()
    {
        return [
            'assets' => [[$this, 'assets']],
            'script' => [[$this, 'script'], [$this, 'endscript']],
            'style' => [[$this, 'style'], [$this, 'endstyle']],
            'if' => [[$this, 'if'], [$this, 'endif']],
            'elseif' => [[$this, 'elseif']],
            'else' => [[$this, 'else']],
            'each' => [[$this, 'each'], [$this, 'endeach']],
            'repeat' => [[$this, 'repeat'], [$this, 'endrepeat']],
            'switch' => [[$this, 'switch'], [$this, 'endswitch']],
            'case' => [[$this, 'case']],
            'break' => [[$this, 'break']],
            'default' => [[$this, 'default']],
            'dump' => [[$this, 'dump']],
            'permission' => [[$this, 'permission'], [$this, 'endpermission']],
            'notpermission' => [[$this, 'notpermission'], [$this, 'endnotpermission']],
            'auth' => [[$this, 'auth'], [$this, 'endauth']],
            'json' => [[$this, 'json']],
            'currentRoute' => [[$this, 'currentRoute'], [$this, 'endcurrentRoute']],
            'route' => [[$this, 'route']]
        ];
    }

    public function script(string $src = null)
    {
        if($src){
            return '<script type="text/javascript" src=' . $src . '></script>';
        }
        return '<script>';
    }

    public function endscript()
    {
        return '</script>';
    }

    public function style(string $href = null)
    {
        if($href){
            return '<link rel="stylesheet" type="text/css" href=' . $href . '>';
        }
        return '<style>';
    }

    public function endstyle(string $href = null)
    {
        return '</style>';
    }

    public function if($cond)
    {
        $variable = $this->getRealVariable($cond);
        $cond = $variable ?? json_encode($cond);
        $expression = $cond;
        return "<?php if($expression): ?>";
    }

    public function elseif($cond)
    {
        $variable = $this->getRealVariable($cond);
        $cond = $variable ?? json_encode($cond);
        $expression = $cond;
        return "<?php else if($expression): ?>";
    }

    public function else()
    {
        return "<?php else: ?>";
    }

    public function endif()
    {
        return "<?php endif; ?>";
    }

    public function each($loop, string $as = null, string $key = null)
    {

        $variable = $this->getRealVariable($loop);
        $loop = $variable !== false ? $variable : var_export($loop, true);
        $loop .= ' as ' . ($key ? "\$$key => " : '') . ($as ? "\$$as" : '$self');
        return "<?php foreach($loop): ?>";
    }

    public function endeach()
    {
        return '<?php endforeach; ?>';
    }

    public function repeat($loop, string $as = null, string $key = null)
    {
        $loop = 'range(0, ' . ($loop - 1) . ') as ' . ($key ? "\$$key => " : '') . ($as ? "\$$as" : '$index');
        return "<?php foreach($loop): ?>";
    }

    public function endrepeat()
    {
        return '<?php endforeach; ?>';
    }

    public function switch($cond)
    {
        self::$conditions['firstCaseSwitch'] = true;
        $variable = $this->getRealVariable($cond);
        $cond = $variable !== false ? $variable : (is_string($cond) ? "'$cond'" : $cond);
        return '<?php switch(' . $cond . '):';
    }

    public function case($case)
    {
        $case = is_string($case) ? "'$case'" : $case;
        if(self::$conditions['firstCaseSwitch']){
            self::$conditions['firstCaseSwitch'] = false;
            return ' case ' . $case . ': ?>';
        }
        return '<?php case(' . $case . '): ?>';
    }

    public function break()
    {
        return '<?php break; ?>';
    }

    public function default()
    {
        return '<?php default; ?>';
    }

    public function endswitch()
    {
        return '<?php endswitch; ?>';
    }

    public function dump($var)
    {
        dump($var);
    }

    public function can($permission, $subject)
    {
        $condition = json_encode(\Core\Facades\Permission::authorizeAuth($permission, $subject));
        return "<?php if($condition): ?>";
    }

    public function endcan()
    {
        return '<?php endif; ?>';
    }

    public function notcan($permission, $subject)
    {
        $condition = json_encode(\Core\Facades\Permission::authorizeAuth($permission, $subject));
        return "<?php if(!$condition): ?>";
    }

    public function endnotcan()
    {
        return '<?php endif; ?>';
    }

    public function auth(bool $is)
    {
        $cond = json_encode($is === (new \Core\AuthManager())->isLogin());
        return "<?php if($cond): ?>";
    }

    public function endauth()
    {
        return '<?php endif; ?>';
    }

    public function json($data)
    {
        $data = json_encode($data);
        return $data;
    }

    public function currentRoute(string $name = '', bool $path = false)
    {
        $current = \Core\Facades\Route::currentRoute($name, $path);
        if($name){
            $current = json_encode($current);
            return "<?php if($current): ?>";
        }
        return $current;
    }

    public function endcurrentRoute()
    {
        return '<?php endif; ?>';
    }

    public function assets(string $path)
    {
        $path = trim($path, '\'\"');
        return BASE_ULR . 'public/' . 'assets/' . $path;
    }

    public function route(string $name, array $params = [])
    {
        return \Core\Facades\Route::generateUrlByName($name, $params);
    }
}