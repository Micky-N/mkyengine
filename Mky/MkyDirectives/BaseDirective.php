<?php


namespace MkyEngine\MkyDirectives;


use MkyEngine\Interfaces\MkyDirectiveInterface;

class BaseDirective extends Directive implements MkyDirectiveInterface
{
    private static array $conditions = [
        'firstCaseSwitch' => false
    ];
    private array $sections = [];

    public function getFunctions()
    {
        return [
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
            'json' => [[$this, 'json']],
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

    public function json($data)
    {
        $data = json_encode($data);
        return $data;
    }
}