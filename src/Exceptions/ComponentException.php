<?php

namespace MkyEngine\Exceptions;

use Exception;

class ComponentException extends Exception
{
    static public function VariableNotFound(string $type, string $value, string $view = ''): static
    {
        if ($type == 'array') {
            $type = 'array key';
        } elseif ($type == 'object') {
            $type = 'object property';
        }
        $message = "Undefined $type \"$value\" in bind parameters";
        if($view){
            $message .= " for \"$view\" component file";
        }
        return new static($message . ".");
    }

    static public function ScopeNotFound(string $scope, string $component): static
    {
        return new static("Scope \"$scope\" not found in \"$component\" component.");
    }
}
