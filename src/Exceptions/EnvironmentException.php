<?php

namespace MkyEngine\Exceptions;

use Exception;

class EnvironmentException extends Exception
{
    /**
     * @param string $namespace
     * @return static
     */
    static public function NamespaceNotFound(string $namespace): static
    {
        return new static("Namespace '$namespace' not found");
    }

    /**
     * @param string $namespace
     * @return static
     */
    static public function NamespaceAlreadyExists(string $namespace): static
    {
        return new static("Loader '$namespace' already exists");
    }
}