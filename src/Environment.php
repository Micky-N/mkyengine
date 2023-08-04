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
    const EXTENSION = '.php';
    /**
     * @var array<string, DirectoryLoader>
     */
    private array $loaders;

    /**
     * @param DirectoryLoader $loader
     * @param array<string, mixed> $context
     */
    public function __construct(
        DirectoryLoader        $loader,
        private readonly array $context = []
    )
    {
        $this->loaders['root'] = $loader;
    }

    /**
     * Get all loaders
     *
     * @return array<string, DirectoryLoader>
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
        return self::EXTENSION;
    }

    /**
     * Get context variables
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * Check if view file exists
     *
     * @param string $view '@namespace:path/view' or 'path/view' for root namespace
     * @param DirectoryType $type type be view, layout or component
     * @return bool
     */
    public function fileExists(string $view, DirectoryType $type = DirectoryType::VIEW): bool
    {
        try {
            return file_exists($this->view($view, $type));
        } catch (EnvironmentException $exception) {
            return false;
        }
    }

    /**
     * Get file from namespace and type
     *
     * @param string $view '@namespace:path/view' or 'path/view' for root namespace
     * @param DirectoryType $type type be view, layout or component
     * @return string
     * @throws EnvironmentException
     */
    public function view(string $view, DirectoryType $type = DirectoryType::VIEW): string
    {
        if ($view[0] !== '@') {
            $namespace = 'root';
        } else {
            $parts = explode(':', $view);
            $namespace = str_replace('@', '', $parts[0]);
            $view = $parts[1];
        }
        $loader = $this->loader($namespace);
        return rtrim($loader->{$type->value . 'Dir'}(), '\/') . DIRECTORY_SEPARATOR . $view . self::EXTENSION;
    }

    /**
     * Get loader from namespace
     *
     * @param string $namespace
     * @return DirectoryLoader
     * @throws EnvironmentException
     */
    public function loader(string $namespace = 'root'): DirectoryLoader
    {
        if ($this->hasLoader($namespace)) {
            return $this->loaders[$namespace];
        }
        throw EnvironmentException::NamespaceNotFound($namespace);
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
            throw EnvironmentException::NamespaceAlreadyExists($namespace);
        }
        $this->loaders[$namespace] = $loader;
        return $this;
    }
}
