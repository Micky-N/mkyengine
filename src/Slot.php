<?php

namespace MkyEngine;

/**
 * The Block class represent a part of the final view
 * call in layout with the section method
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class Slot
{

    private ?bool $condition = null;

    /**
     * @var string
     */
    private string $content = '';
    private string $name;
    private string $index;

    /**
     * @param string $name
     * @param string $content
     * @param string $index
     */
    public function __construct(string $name, string $content, string $index)
    {
        $this->setName($name);
        $this->setContent($content);
        $this->setIndex($index);
    }

    /**
     * Display content if condition is true
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
     * Render content in view
     *
     * @return string
     */
    public function __toString(): string
    {
        if (is_bool($this->condition) && !$this->condition) {
            return '';
        }
        return $this->getContent();
    }

    public function getCondition(): ?bool
    {
        return $this->condition;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Slot
     */
    public function setContent(string $content): Slot
    {
        $this->content = $content;
        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @param string $index
     */
    public function setIndex(string $index): void
    {
        $this->index = $index;
    }
}