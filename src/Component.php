<?php

namespace MkyEngine;

use Closure;
use Exception;
use MkyEngine\Traits\SlotTrait;
use MkyEngine\Exceptions\ComponentException;
use MkyEngine\Exceptions\EnvironmentException;
use Throwable;

/**
 * The component represents an included file,
 * useful for splitting a view into several small reusable parts.
 * The component is isolated from its parent.
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class Component
{
    use SlotTrait;

    private ViewCompiler $viewCompiler;
    private int $forCount = 0;
    private ?Closure $forClosure = null;
    private ?bool $condition = null;
    private array $forData = [];
    private string $otherView = '';
    private string $index;

    private array $variables = [];

    /**
     * @var Component[]
     */
    private array $components = [];

    private ?string $same = null;

    /**
     * @param Environment $environment
     * @param string $component
     * @throws ComponentException|EnvironmentException
     * @author Mickaël Ndinga <ndingamickael@gmail.com>
     */
    public function __construct(Environment $environment, string $component)
    {
        if (!$environment->fileExists($component, DirectoryType::COMPONENT)) {
            throw ComponentException::FileNotFound($environment->view($component, DirectoryType::COMPONENT));
        }
        $this->setViewCompiler($environment, $component);
        $this->generateIndex();
    }

    /**
     * Generate id for component
     *
     * @return void
     */
    public function generateIndex(): void
    {
        $this->index = md5(uniqid());
    }

    public function setComponents(array $components): void
    {
        $this->components = $components;
    }

    /**
     * Bind param value
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws Exception
     */
    public function bind(string $name, mixed $value): static
    {
        $this->setVariable($name, $value);
        return $this;
    }

    /**
     * Set variable to component
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    private function setVariable(string $name, mixed $value): void
    {
        $this->variables[$name] = is_string($value) ? htmlspecialchars($value) : $value;
    }

    /**
     * Bind multiple variable to component
     *
     * @param array $variables
     * @return $this
     */
    public function multipleBind(array $variables): static
    {
        foreach ($variables as $name => $value) {
            $this->setVariable($name, $value);
        }
        return $this;
    }

    /**
     * Repeat the component
     *
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
                    if (is_int($bind)) {
                        $bind = $value;
                    }
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
     * Get variable in object from (dot) string
     *
     * @param array|object $data
     * @param string $value
     * @return mixed
     * @throws ComponentException
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
                } else {
                    throw ComponentException::VariableNotFound('array', $val, $this->viewCompiler->getView());
                }
            } elseif (is_object($data)) {
                if (property_exists($data, $val)) {
                    $data = $data->{$val};
                    continue;
                } else if (method_exists($data, 'get' . ucfirst($val))) {
                    $data = $data->{'get' . ucfirst($val)}();
                    continue;
                }
                try {
                    $data = $data->{$val};
                    continue;
                } catch (Throwable $th) {
                    throw ComponentException::VariableNotFound('object', $val, $this->viewCompiler->getView());
                }
            }
            break;
        }
        return $data;
    }

    /**
     * Get the view file name
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->viewCompiler->getView();
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

        $variables = $this->variables;
        if ($this->forCount) {
            $render = '';
            $closure = $this->forClosure;
            for ($i = 0; $i < $this->forCount; $i++) {
                if ($closure) {
                    $variables = $closure($variables, $i, $this->forData);
                }
                $this->viewCompiler->setVariables($variables);
                $render .= $this->viewCompiler->render(DirectoryType::COMPONENT);
            }
            return $render;
        } elseif ($this->otherView) {
            $this->setViewCompiler($this->viewCompiler->getEnvironment(), $this->otherView);
        } elseif ($this->forClosure) {
            return '';
        }
        $this->viewCompiler->setVariables($this->variables);
        return $this->viewCompiler->render(DirectoryType::COMPONENT, $this);
    }

    /**
     * @return ViewCompiler
     */
    public function getViewCompiler(): ViewCompiler
    {
        return $this->viewCompiler;
    }

    /**
     * Set a viewCompile component
     *
     * @param Environment $environment
     * @param string $component
     */
    public function setViewCompiler(Environment $environment, string $component): void
    {
        $this->viewCompiler = new ViewCompiler($environment, $component);
    }

    /**
     * Open buffering for set html script in component
     *
     * @return Component
     * @throws Exception
     */
    public function start(): static
    {
        $this->same = $this->getIndex();
        ob_start();
        return $this;
    }

    /**
     * Get the component index
     *
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * Close the buffering and set in the default slot
     *
     * @return void
     */
    public function end(): void
    {
        $content = ob_get_clean();
        $components = array_filter($this->components, function (Component $component) {
            return $component->getView() == $this->getView() && $component->getSame();
        });

        if ($components) {
            $component = reset($components);
            $component->setSlot('default', trim($content));
            $component->setSame(null);
            echo $component;
        }
    }

    /**
     * Get the current component index
     *
     * @return string|null
     */
    public function getSame(): ?string
    {
        return $this->same;
    }

    /**
     * Set current component index
     *
     * @param string|null $same
     */
    public function setSame(?string $same): void
    {
        $this->same = $same;
    }
}
