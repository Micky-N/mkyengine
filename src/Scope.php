<?php

namespace MkyEngine;

/**
 * The Block class represent a part of the final view
 * call in layout with the section method
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class Scope
{

    private ?bool $condition = null;

    /**
     * @var string
     */
    private string $content = '';

    /**
     * @param string $content
     */
    public function __construct(string $content = '')
    {
        $this->setContent($content);
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
     * @param string $contents
     * @return Scope
     */
    public function setContent(string $content): Scope
    {
        $this->content = $content;
        return $this;
    }
}