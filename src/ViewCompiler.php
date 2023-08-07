<?php

namespace MkyEngine;

use Exception;
use MkyEngine\Exceptions\ComponentException;
use MkyEngine\Exceptions\EnvironmentException;
use MkyEngine\Exceptions\ViewCompilerException;

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
    private ?Component $currentComponent = null;

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
     * @param string $name
     * @return string|Component
     * @throws ComponentException|EnvironmentException
     */
    public function component(string $name): string|Component
    {
        $this->currentComponent = new Component($this->environment, $name);
        $this->components[] = $this->currentComponent;
        $this->currentComponent->setComponents($this->components);
        return $this->currentComponent;
    }

    /**
     * Render the view
     *
     * @param DirectoryType $directoryType
     * @param Component|null $component
     * @return string
     * @throws EnvironmentException
     */
    public function render(DirectoryType $directoryType = DirectoryType::VIEW, ?Component $component = null): string
    {
        if ($component) {
            $this->currentComponent = $component;
        }
        $variables = array_replace_recursive($this->variables, $this->environment->context());

        extract($variables);
        ob_start();
        require($this->environment->view($this->getView(), $directoryType));
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

    /**
     * Get all params
     *
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
     * Set params
     *
     * @param string $name
     * @param mixed $value
     * @return ViewCompiler
     */
    public function setVariable(string $name, mixed $value): ViewCompiler
    {
        $this->variables[$name] = is_string($value) ? htmlspecialchars($value) : $value;
        return $this;
    }

    /**
     * Add a class property
     *
     * @param string $name
     * @param string|object $class
     * @return $this
     */
    public function inject(string $name, string|object $class): static
    {
        $this->injects[$name] = is_string($class) ? new $class : $class;
        return $this;
    }

    /**
     * Magic getter for injected classes
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
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

    /**
     * Add slot to component
     *
     * @param string $name
     * @param string|null $content
     * @return Slot
     * @throws ViewCompilerException
     */
    public function addslot(string $name, string $content = null): Slot
    {
        $this->inComponentScope();
        if ($content) {
            return $this->currentComponent->setSlot($name, $content);
        }
        $slot = $this->currentComponent->setSlot($name, "--EMPTY_SLOT[$name]--");
        ob_start();
        return $slot;
    }

    /**
     * Check if code is in component scope
     *
     * @param string $type
     * @return void
     * @throws ViewCompilerException
     */
    private function inComponentScope(string $type = 'slot'): void
    {
        if (!$this->currentComponent) {
            throw ViewCompilerException::InComponentScope($type);
        }
    }

    /**
     * Show slot html or default if empty
     *
     * @param string $name
     * @param string|null $default
     * @return string
     * @throws ComponentException|ViewCompilerException
     */
    public function slot(string $name, string $default = null): string
    {
        $this->inComponentScope();
        if ($this->currentComponent->hasSlot($name)) {
            return $this->currentComponent->getSlot($name);
        }
        if ($default) {
            return $default;
        }
        throw ComponentException::SlotNotFound($name, $this->currentComponent->getView());
    }

    /**
     * Check if slot is defined
     *
     * @param string $name
     * @return bool
     * @throws ViewCompilerException
     */
    public function hasSlot(string $name): bool
    {
        $this->inComponentScope();
        return $this->currentComponent->hasSlot($name);
    }

    /**
     * End the slot
     *
     * @return void
     * @throws ViewCompilerException
     */
    public function endslot(): void
    {
        $this->inComponentScope();
        $content = ob_get_clean();
        $content = trim($content);
        $slots = $this->currentComponent->getSlots();
        $slotIndex = array_key_last($slots);

        if (isset($slots[$slotIndex])) {
            $slot = $slots[$slotIndex];
            $name = $slot->getName();
            if ($slot->getContent() === "--EMPTY_SLOT[$name]--") {
                $slots[$slotIndex]->setContent($content);
            }
        }
    }
}
