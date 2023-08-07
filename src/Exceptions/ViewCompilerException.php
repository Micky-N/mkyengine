<?php

namespace MkyEngine\Exceptions;

use Exception;

class ViewCompilerException extends Exception
{
    static public function InComponentScope(string $type): static
    {
        return new static(ucfirst($type) . " script must be in component scope.");
    }

    static public function InBlockScope(string $type): static
    {
        return new static(ucfirst($type) . " script must be in block scope.");
    }
}
