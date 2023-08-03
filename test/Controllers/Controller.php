<?php

namespace MkyEngine\Test\Controllers;


class Controller
{

    protected \MkyEngine\Test\ViewRenderer $view;

    public function __construct()
    {
        $this->view = new \MkyEngine\Test\ViewRenderer();
    }
}
