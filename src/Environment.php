<?php

namespace MkyEngine;

use MkyEngine\Exceptions\EnvironmentException;

/**
 * The environment class has all directories with their namespace
 * and context variables
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class Environment
{
    /**
     * @var array<string, DirectoryLoader>
     */
    private array $loaders;

    /**
     * @param DirectoryLoader $loader
     * @param string $extension
     * @param array<string, mixed> $context
     */
    public function __construct(
        DirectoryLoader         $loader,
        private readonly string $extension = '.php',
        private readonly array  $context = []
    )
    {
        $this->loaders['root'] = $loader;
    }

    /**
     * Get all loaders
     *
     * @return DirectoryLoader[]
     */
    public function loaders(): array
    {
        return $this->loaders;
    }

    /**
     * Get view file extension
     *
     * @return string
     */
    public function extension(): string
    {
        return $this->extension;
    }

    /**
     * Get context variables
     *
     * @return array
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * Get file from namespace and type
     *
     * @param string $view '@namespace:path/view' or 'path/view' for root namespace
     * @param string $type type be view, layout or component
     * @return string
     * @throws EnvironmentException
     */
    public function view(string $view, string $type = 'view'): string
    {
        if ($view[0] !== '@') {
            $namespace = 'root';
        } else {
            $parts = explode(':', $view);
            $namespace = str_replace('@', '', $parts[0]);
            $view = $parts[1];
        }
        $loader = $this->loader($namespace);
        return rtrim($loader->{$type . 'Dir'}(), '\/') . DIRECTORY_SEPARATOR . $view . $this->extension;
    }

    /**
     * Get loader from namespace
     *
     * @param string $namespace
     * @return DirectoryLoader
     * @throws EnvironmentException
     */
    public function loader(string $namespace): DirectoryLoader
    {
        if ($this->hasLoader($namespace)) {
            return $this->loaders[$namespace];
        }
        throw new EnvironmentException("Namespace '$namespace' not found");
    }

    /**
     * Check if the namespace loader exists
     *
     * @param string $namespace
     * @return bool
     */
    public function hasLoader(string $namespace): bool
    {
        return isset($this->loaders[$namespace]);
    }

    /**
     * Add a loader with namespace
     *
     * @param string $namespace
     * @param DirectoryLoader $loader
     * @return Environment
     * @throws EnvironmentException
     */
    public function addLoader(string $namespace, DirectoryLoader $loader): Environment
    {
        if ($this->hasLoader($namespace)) {
            throw new EnvironmentException("Loader '$namespace' already exists");
        }
        $this->loaders[$namespace] = $loader;
        return $this;
    }

}