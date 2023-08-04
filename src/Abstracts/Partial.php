<?php

namespace MkyEngine\Abstracts;

use MkyEngine\Scope;

/**
 * The component represents an included file,
 * useful for splitting a view into several small reusable parts.
 * The component is isolated from its parent.
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
abstract class Partial
{

    private Partial $instance;
    
    /**
     * [Description for $scopes]
     *
     * @var array<string, Scope>
     */
    public array $scopes = [];

    /**
     * @param string $component
     * @author Mickaël Ndinga <ndingamickael@gmail.com>
     */
    public function __construct()
    {
        $this->instance = $this;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScope(string $name, string $content): Scope
    {
        $this->scopes[$name] = new Scope($content);
        return $this->scopes[$name];
    }
}
