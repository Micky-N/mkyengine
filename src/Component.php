<?php

namespace MkyEngine;

use Closure;
use MkyEngine\Exceptions\EnvironmentException;

/**
 * The component represents an included file,
 * useful for splitting a view into several small reusable parts.
 * The component is isolated from its parent.
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class Component
{
    private ViewCompiler $component;
    private array $params = [];
    private int $forCount = 0;
    private ?Closure $forClosure = null;
    private ?bool $condition = null;
    private array $forData = [];
    private string $otherView = '';

    /**
     * @param Environment $environment
     * @param string $component
     * @author Mickaël Ndinga <ndingamickael@gmail.com>
     */
    public function __construct(Environment $environment, string $component)
    {
        $this->setComponent($environment, $component);
    }

    /**
     * Set a viewCompile component
     *
     * @param Environment $environment
     * @param string $component
     */
    public function setComponent(Environment $environment, string $component): void
    {
        $this->component = new ViewCompiler($environment, $component);
    }

    /**
     * Bind param value
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function bind(string $name, mixed $value): static
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Repeat the component
     * @param int $count number of repetitions
     * @param Closure $closure callback for each iteration
     * @return $this
     */
    public function for(int $count, Closure $closure): static
    {
        $this->forCount = $count;
        $this->forClosure = $closure;
        return $this;
    }

    /**
     * Repeat the component for the data value
     *
     * @param array<string|int, mixed> $data
     * @param array|Closure|string $binds
     * @param string $otherView
     * @return $this
     */
    public function each(array $data, array|Closure|string $binds, string $otherView = ''): static
    {
        $this->otherView = $otherView;
        $this->forCount = count($data);
        $this->forData = $data;
        if (is_array($binds)) {
            $this->forClosure = function (array $params, int $index, array $data) use ($binds) {
                $arrIndex = array_keys($data);
                $param = $data[$arrIndex[$index]];
                foreach ($binds as $bind => $value) {
                    $params[$bind] = $this->getBindParam($param, $value);
                }
                return $params;
            };
        } elseif (is_callable($binds)) {
            $this->forClosure = $binds;
        } elseif (is_string($binds)) {
            $this->forClosure = function (array $params, int $index, array $data) use ($binds) {
                $arrIndex = array_keys($data);
                $param = $data[$arrIndex[$index]];
                $params[$binds] = $param;
                return $params;
            };
        }
        return $this;
    }

    /**
     * Display the component if condition is true
     *
     * @param bool $condition
     * @return $this
     */
    public function if(bool $condition): static
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Render the component
     *
     * @return string
     * @throws EnvironmentException
     */
    public function __toString(): string
    {
        if (is_bool($this->condition) && $this->condition === false) {
            return '';
        }

        if ($this->forCount) {
            $render = '';
            $closure = $this->forClosure;
            for ($i = 0; $i < $this->forCount; $i++) {
                $this->params = $closure($this->params, $i, $this->forData);
                $this->component->setParams($this->params);
                $render .= $this->component->render('component');
            }
            return $render;
        }elseif($this->otherView){
            $this->setComponent($this->component->getEnvironment(), $this->otherView);
        }elseif($this->forClosure){
            return '';
        }
        $this->component->setParams($this->params);
        return $this->component->render('component');
    }

    private function getBindParam(array $param, mixed $value): mixed
    {
        $values = explode('.', $value);
        for($i = 0; $i < count($values); $i++){
            $val = $values[$i];
            if(is_array($param)){
                if(isset($param[$val])){
                    $param = $param[$val];
                    continue;
                }
            }elseif(is_object($param)){
                if(property_exists($param, $val)){
                    $param = $param->{$val};
                    continue;
                }
            }
            break;
        }
        return $param;
    }
}