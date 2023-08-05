<?php

namespace MkyEngine;

use MkyEngine\Abstracts\Partial;
use MkyEngine\Exceptions\ComponentException;
use MkyEngine\Exceptions\EnvironmentException;

/**
 * The view compiler compile the view with
 * transformation methods like extends(),
 * component() and others.
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class ViewCompiler
{
    /**
     * @var array<string, Block>
     */
    private array $blocks = [];

    /**
     * @var array<string, Component>
     */
    private array $components = [];
    private string $layout = '';

    /**
     * @var array<string, mixed>
     */
    private array $injects = [];

    private ?Partial $partial = null;

    public function __construct(private readonly Environment $environment, private readonly string $view, private array $variables = [])
    {
    }

    /**
     * Create a block
     *
     * @param string $name
     * @param mixed|null $value
     * @return Block
     */
    public function block(string $name, mixed $value = null): Block
    {
        if (!is_null($value)) {
            return $this->blocks[$name] = new Block($value);
        }
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = new Block("--EMPTY[$name]--");
        }
        ob_start();
        return $this->blocks[$name];
    }

    /**
     * End the block
     *
     * @return void
     */
    public function endblock(): void
    {
        $content = ob_get_clean();
        $content .= "\n";
        $blockIndex = array_key_last($this->blocks);
        if (isset($this->blocks[$blockIndex])) {
            if ($this->blocks[$blockIndex]->getContentAsString() === "--EMPTY[$blockIndex]--") {
                $this->blocks[$blockIndex]->setContent($content);
            } else {
                $this->blocks[$blockIndex]->addContent($content);
            }
        }
    }

    /**
     * Includes a component
     *
     * @param string $component
     * @return string|Component
     */
    public function component(string $component): string|Component
    {
        $this->components[$component] = new Component($this->environment, $component);
        $this->partial = $this->components[$component];
        ob_start();
        return $this->components[$component];
    }

    /**
     * Includes a component
     *
     * @return void
     */
    public function endComponent(): void
    {
        $content = ob_get_clean();
        $componentIndex = array_key_last($this->components);
        if (isset($this->components[$componentIndex])) {
            $this->components[$componentIndex]->setSlot('default', trim($content));
        }
        echo $this->components[$componentIndex];
    }

    /**
     * Render the view
     *
     * @param DirectoryType $type
     * @param Partial|null $partial
     * @return string
     * @throws EnvironmentException
     */
    public function render(DirectoryType $type = DirectoryType::VIEW, ?Partial $partial = null): string
    {
        $variables = array_replace_recursive($this->variables, $this->environment->context());
        foreach ($variables as $name => $variable) {
            $variables[$name] = is_string($variable) ? htmlspecialchars($variable) : $variable;
        }

        if ($partial) {
            $this->partial = $partial;
        }
        extract($variables);
        ob_start();
        require($this->environment->view($this->getView(), $type));
        $view = ob_get_clean();
        if ($layout = $this->getLayout()) {
            $layout = new static($this->environment, $layout, $this->variables);
            $layout->setBlocks($this->getBlocks());
            return $layout->render(DirectoryType::LAYOUT);
        }
        return $view;
    }

    /**
     * Get view filename
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Get layout filename
     *
     * @return string|null
     */
    public function getLayout(): ?string
    {
        return $this->layout;
    }

    /**
     * Get all blocks
     *
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Set blocks
     *
     * @param array $blocks
     * @return void
     */
    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
    }

    /**
     * Set the layout filename
     *
     * @param string $name
     * @return void
     */
    public function extends(string $name): void
    {
        $this->layout = $name;
    }

    /**
     * Render the block name
     *
     * @param string $name
     * @param string $default
     * @return string|Block
     */
    public function section(string $name, string $default = ''): string|Block
    {
        return $this->blocks[$name] ?? $default;
    }

    /**
     * Read a content as html string
     *
     * @param string $content
     * @return string
     */
    public function escape(string $content): string
    {
        return htmlspecialchars_decode($content);
    }

    public function attr(array ...$attributes): string
    {
        $attributes = array_merge_recursive(...$attributes);
        array_walk($attributes, function (&$attribute, $key) {
            if (is_array($attribute) && is_string(array_keys($attribute)[0])) {
                $last = array_key_last($attribute);
                array_walk($attribute, function (&$value, $key2) use ($last) {
                    $separator = $last !== $key2 ? ';' : '';
                    $value = "$key2: $value$separator";
                });
            }
            $value = join(' ', (array)$attribute);
            $attribute = "$key=\"$value\"";
        });

        return join(' ', $attributes);
    }

    /**
     * Get all params
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Set params
     *
     * @param array $variables
     * @return ViewCompiler
     */
    public function setVariables(array $variables): ViewCompiler
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Add a property to params
     *
     * @param string $name
     * @param string $class
     * @return $this
     */
    public function inject(string $name, string $class): static
    {
        $this->injects[$name] = new $class();
        return $this;
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if ($this->partial && property_exists($this->partial, $name)) {
            return $this->partial->{$name};
        }
        return $this->injects[$name] ?? null;
    }

    /**
     * Get environment
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function addslot(string $name, string $content = null): Slot
    {
        if ($content) {
            return $this->partial->setSlot($name, $content);
        }
        $slot = $this->partial->setSlot($name, "--EMPTY_SLOT[$name]--");
        ob_start();
        return $slot;
    }

    /**
     * @param string $name
     * @param string|null $default
     * @return string
     * @throws ComponentException
     */
    public function slot(string $name, string $default = null): string
    {
        if ($this->partial->hasSlot($name)) {
            return $this->partial->getSlot($name);
        }
        if($default){
            return $default;
        }
        throw ComponentException::ScopeNotFound($name, $this->partial->getView());
    }

    public function hasSlot(string $name): bool
    {
        return $this->partial->hasSlot($name);
    }

    /**
     * End the block
     *
     * @return void
     */
    public function endslot(): void
    {
        $content = ob_get_clean();
        $content = trim($content);
        $slots = $this->partial->getSlots();
        $slotIndex = array_key_last($slots);
        if (isset($slots[$slotIndex]) && $slots[$slotIndex]->getContent() === "--EMPTY_SLOT[$slotIndex]--") {
            $slots[$slotIndex]->setContent($content);
        }
    }
}
