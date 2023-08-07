<?php

namespace MkyEngine\Test;

class User
{
    public function __construct(private readonly string $name = 'Micky')
    {
    }

    public function getNameText(): string
    {
        return $this->name;
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
