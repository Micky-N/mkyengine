<?php

namespace MkyEngine\Test;

class User
{
    public function getName(): string
    {
        return 'Micky';
    }

    public function getAddress(): Address
    {
        return new Address;
    }

    public function getAgeText(): string
    {
        return 30;
    }

    public function __get(string $prop)
    {
        $pascalProp = 'get'.ucfirst($prop.'Text');
        return $this->{$pascalProp}();
    }
}
