<?php

namespace MkyEngine\Abstracts;

use MkyEngine\Slot;

/**
 * The component represents an included file,
 * useful for splitting a view into several small reusable parts.
 * The component is isolated from its parent.
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
abstract class Partial
{

    /**
     * @var array<string, Slot>
     */
    private array $slots = [];

    public function getSlots(): array
    {
        return $this->slots;
    }

    public function getSlot(string $name): Slot
    {
        return $this->slots[$name];
    }

    public function hasSlot(string $name): bool
    {
        return isset($this->slots[$name]);
    }

    public function setSlot(string $name, string $content): Slot
    {
        $this->slots[$name] = new Slot($content);
        return $this->slots[$name];
    }

    abstract public function getView(): string;
}
