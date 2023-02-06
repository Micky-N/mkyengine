<?php

namespace MkyEngine;
/**
 * The directory loader stores view directory
 * views, components and layouts directory
 *
 * @author Mickaël Ndinga <ndingamickael@gmail.com>
 */
class DirectoryLoader
{
    private readonly string $viewDirectory;
    private string $layoutDirectory;
    private string $componentDirectory;

    public function __construct(string $viewDirectory)
    {
        $viewDirectory = rtrim($viewDirectory, '\/');
        $this->viewDirectory = $viewDirectory;
        $this->layoutDirectory = $viewDirectory;
        $this->componentDirectory = $viewDirectory;
    }

    /**
     * Get view global directory
     *
     * @return string
     */
    public function viewDir(): string
    {
        return $this->viewDirectory;
    }

    /**
     * Set layout directory
     *
     * @param string $layoutDirectory
     * @return DirectoryLoader
     */
    public function setLayoutDir(string $layoutDirectory): DirectoryLoader
    {
        $this->layoutDirectory = $this->viewDirectory . DIRECTORY_SEPARATOR . trim($layoutDirectory, '\/');
        return $this;
    }

    /**
     * Get layout directory
     *
     * @return string
     */
    public function layoutDir(): string
    {
        return $this->layoutDirectory;
    }

    /**
     * Get component directory
     * @return string
     */
    public function componentDir(): string
    {
        return $this->componentDirectory;
    }

    /**
     * Set component directory
     *
     * @param string $componentDirectory
     * @return DirectoryLoader
     */
    public function setComponentDir(string $componentDirectory): static
    {
        $this->componentDirectory = $this->viewDirectory . DIRECTORY_SEPARATOR . trim($componentDirectory, '\/');
        return $this;
    }
}