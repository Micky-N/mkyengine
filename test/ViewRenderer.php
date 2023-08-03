<?php

namespace MkyEngine\Test;

use MkyEngine\Environment;

class ViewRenderer
{
    private Environment $environment;

    public function __construct(string $viewDir)
    {
        $this->environment = new Environment(new \MkyEngine\DirectoryLoader($viewDir), []);
    }


    public function render(string $view, array $variables = [])
    {
        return new \MkyEngine\ViewCompiler($this->environment, $view, $variables);
    }

    public function getEnvironment()
    {
        return $this->environment;
    }
}
