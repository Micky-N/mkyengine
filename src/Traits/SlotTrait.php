<?php

namespace MkyEngine\Traits;

use MkyEngine\Slot;

/**
 * The class is used to handle slot in component
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
trait SlotTrait
{

    /**
     * @var array<string, Slot>
     */
    private array $slots = [];

    /**
     * Get all slots
     *
     * @return array<string, Slot>
     */
    public function getSlots(): array
    {
        return $this->slots;
    }

    /**
     * Find a slot by name
     *
     * @param string $name
     * @return Slot
     */
    public function getSlot(string $name): Slot
    {
        $slots = $this->slots;
        foreach($slots as $slot){
            if($slot->getIndex() == $this->getIndex() && $slot->getName() == $name){
                return $slot;
            }
        }
        return $this->slots[$name];
    }

    /**
     * Check if slot exists
     *
     * @param string $name
     * @return bool
     */
    public function hasSlot(string $name): bool
    {
        $slots = $this->slots;
        $currentSlot = false;
        foreach($slots as $slot){
            if($slot->getIndex() == $this->getIndex() && $slot->getName() == $name){
                $currentSlot = $slot;
                break;
            }
        }
        if (!$currentSlot) {
            return false;
        }

        $condition = $currentSlot->getCondition();

        if (is_null($condition)) {
            return true;
        }

        return $condition;
    }

    /**
     * Add slot to component
     *
     * @param string $name
     * @param string $content
     * @return Slot
     */
    public function setSlot(string $name, string $content): Slot
    {
        $slot = new Slot($name, $content, $this->getIndex());
        $this->slots[] = $slot;
        return $slot;
    }
}
