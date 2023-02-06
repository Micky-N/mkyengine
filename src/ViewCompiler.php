<?php

namespace MkyEngine;

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
    private string $layout = '';
    /**
     * @var array<string, mixed>
     */
    private array $injects = [];

    public function __construct(private readonly Environment $environment, private readonly string $view, private array $params = [])
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
     * @return Block
     */
    public function endblock(): Block
    {
        $content = ob_get_clean();
        $blockIndex = array_key_last($this->blocks);
        if (isset($this->blocks[$blockIndex])) {
            if ($this->blocks[$blockIndex]->getContent() === "--EMPTY[$blockIndex]--") {
                $this->blocks[$blockIndex]->setContent($content);
            } else {
                $this->blocks[$blockIndex]->addContent($content);
            }
        }
        return $this->blocks[$blockIndex];
    }

    /**
     * Includes a component
     *
     * @param string $component
     * @return string|Component
     */
    public function component(string $component): string|Component
    {
        return new Component($this->environment, $component);
    }

    /**
     * Render the view
     *
     * @param string $type
     * @return string
     * @throws EnvironmentException
     */
    public function render(string $type = 'view'): string
    {
        extract(array_replace_recursive($this->params, $this->environment->context()));
        ob_start();
        require($this->environment->view($this->getView(), $type));
        $view = ob_get_clean();
        if ($layout = $this->getLayout()) {
            $layout = new static($this->environment, $layout, $this->params);
            $layout->setBlocks($this->getBlocks());
            return $layout->render('layout');
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
        return htmlspecialchars($content);
    }

    /**
     * Get all params
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set params
     *
     * @param array $params
     * @return ViewCompiler
     */
    public function setParams(array $params): ViewCompiler
    {
        $this->params = $params;
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
}