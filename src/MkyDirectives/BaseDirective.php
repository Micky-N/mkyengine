<?php


namespace MkyEngine\MkyDirectives;


use MkyEngine\MkyEngine;
use MkyEngine\Interfaces\MkyDirectiveInterface;

class BaseDirective implements MkyDirectiveInterface
{
    private static array $conditions = [
        'firstCaseSwitch' => false
    ];

    public function getFunctions()
    {
        return [
            'script' => [[$this, 'script'], [$this, 'endscript']],
            'style' => [[$this, 'style'], [$this, 'endstyle']],
            'if' => [[$this, 'if'], [$this, 'endif']],
            'elseif' => [$this, 'elseif'],
            'else' => [$this, 'else'],
            'each' => [[$this, 'each'], [$this, 'endeach']],
            'repeat' => [[$this, 'repeat'], [$this, 'endrepeat']],
            'switch' => [[$this, 'switch'], [$this, 'endswitch']],
            'case' => [$this, 'case'],
            'break' => [$this, 'break'],
            'default' => [$this, 'default'],
            'json' => [$this, 'json'],
            'php' => [[$this, 'php'], [$this, 'endphp']],
            'set' => [[$this, 'set'], [$this, 'endset']],
        ];
    }

    public function php()
    {
        return '<?php ';
    }

    public function endphp()
    {
        return ' ?>';
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
        $variable = MkyEngine::getRealVariable($cond);
        $cond = $variable ?: json_encode($cond);
        $expression = $cond;
        return "<?php if($expression): ?>";
    }

    public function elseif($cond)
    {
        $variable = MkyEngine::getRealVariable($cond);
        $cond = $variable ?: json_encode($cond);
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

        $variable = MkyEngine::getRealVariable($loop);
        $loop = $variable !== false ? $variable : var_export($loop, true);
        $loop .= ' as ' . ($key ? "\$$key => " : '') . ($as ? "\$$as" : '$self');
        return "<?php foreach($loop): ?>";
    }

    public function endeach()
    {
        return '<?php endforeach; ?>';
    }

    public function repeat(int $for, int $step = 1, string $key = null)
    {
        if(empty($key)){
            $key = '$i';
        }
        return "<?php for($key = 0; $key < $for; $key+= $step): ?>";
    }

    public function endrepeat()
    {
        return '<?php endfor; ?>';
    }

    public function switch($cond)
    {
        self::$conditions['firstCaseSwitch'] = true;
        $variable = MkyEngine::getRealVariable($cond);
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

    public function json($data)
    {
        if(is_null($data)){
            $data = MkyEngine::getRealVariable($data);
            return "<?= json_encode($data) ?>";
        }
        $data = json_encode($data);
        return $data;
    }

    public function set($key, $value = null)
    {
        $val = '<<<HTML';
        if(!is_null($value)){
            $val = $value . ' ?>';
        }
        return "<?php \$$key = $val";
    }

    public function endset()
    {
        return "HTML; ?>";
    }
}
