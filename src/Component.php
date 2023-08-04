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
    private array $variables = [];
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
        $this->variables[$name] = is_string($value) ? htmlspecialchars($value) : $value;
        return $this;
    }

    /**
     * Bind param value
     *
     * @param array $variables
     * @return $this
     */
    public function multipleBind(array $variables): static
    {
        foreach ($variables as $name => $value) {
            $this->variables[$name] = is_string($value) ? htmlspecialchars($value) : $value;
        }
        return $this;
    }

    /**
     * Repeat the component
     * @param int $count number of repetitions
     * @param Closure|null $closure callback for each iteration
     * @return $this
     */
    public function for(int $count, ?Closure $closure = null): static
    {
        $this->forCount = $count;
        $this->forClosure = $closure;
        return $this;
    }

    /**
     * Repeat the component for the data value
     *
     * @param array<string|int, mixed> $data
     * @param array|Closure|string|null $binds
     * @param string $otherView
     * @return $this
     */
    public function each(array $data, array|Closure|string|null $binds = null, string $otherView = ''): static
    {
        $this->otherView = $otherView;
        $this->forCount = count($data);
        $this->forData = $data;
        if (is_array($binds)) {
            $this->forClosure = function (array $variables, int $index, array $data) use ($binds) {
                $arrIndex = array_keys($data);
                $currentData = $data[$arrIndex[$index]];
                foreach ($binds as $bind => $value) {
                    $variables[$bind] = $this->getBindVariable($currentData, $value);
                }
                return $variables;
            };
        } elseif (is_callable($binds) || is_null($binds)) {
            $this->forClosure = $binds;
        } elseif (is_string($binds)) {
            $this->forClosure = function (array $variables, int $index, array $data) use ($binds) {
                $arrIndex = array_keys($data);
                $variables[$binds] = $data[$arrIndex[$index]];
                return $variables;
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
                if($closure){
                    $this->variables = $closure($this->variables, $i, $this->forData);
                }
                $this->component->setVariables($this->variables);
                $render .= $this->component->render(DirectoryTypes::COMPONENT);
            }
            return $render;
        } elseif ($this->otherView) {
            $this->setComponent($this->component->getEnvironment(), $this->otherView);
        } elseif ($this->forClosure) {
            return '';
        }

        $this->component->setVariables($this->variables);
        return $this->component->render(DirectoryTypes::COMPONENT);
    }

    /**
     * @param array|object $data
     * @param string $value
     * @return mixed
     */
    private function getBindVariable(array|object $data, string $value): mixed
    {
        $values = explode('.', $value);
        for ($i = 0; $i < count($values); $i++) {
            $val = $values[$i];
            if (is_array($data)) {
                if (isset($data[$val])) {
                    $data = $data[$val];
                    continue;
                }
            } elseif (is_object($data)) {
                if (property_exists($data, $val)) {
                    $data = $data->{$val};
                    continue;
                }
            }
            break;
        }
        return $data;
    }
}
