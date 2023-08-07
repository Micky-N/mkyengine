<?php

namespace MkyEngine;

/**
 * The Block class represent a part of the final view
 * call in layout with the section method
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class Block
{
    /**
     * @var bool[]
     */
    private array $conditions = [];

    /**
     * @var string[]
     */
    private array $contents = [];

    /**
     * @param array|string $contents
     */
    public function __construct(array|string $contents = '')
    {
        $this->setContent($contents);
    }

    /**
     * Display content if condition is true
     *
     * @param bool $condition
     * @return $this
     */
    public function if(bool $condition): static
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Render content in view
     *
     * @return string
     */
    public function __toString(): string
    {
        $contents = $this->getContents();
        if ($this->conditions) {
            $contents = array_filter($contents, function ($part, $index) {
                return !isset($this->conditions[$index]) || $this->conditions[$index];
            }, ARRAY_FILTER_USE_BOTH);
        }
        return join("\n", $contents);
    }

    /**
     * Get all contents
     *
     * @return string[]
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * Add content
     *
     * @param string $content
     * @return $this
     */
    public function addContent(string $content): static
    {
        $this->contents[] = $content;
        return $this;
    }

    /**
     * Get all contents as string
     *
     * @return string
     */
    public function getContentAsString(): string
    {
        return join("\n", $this->contents);
    }

    /**
     * Set content
     *
     * @param array|string $contents
     * @return Block
     */
    public function setContent(array|string $contents): Block
    {
        $this->contents = $contents ? (array)$contents : [];
        return $this;
    }
}