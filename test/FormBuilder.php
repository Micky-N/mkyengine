<?php

namespace MkyEngine\Test;

class FormBuilder
{
    public function open(array $attr): string
    {
        return "<form method='{$attr['method']}' action='{$attr['action']}'>";
    }

    public function input(string $type, string $name, string $value = ''): string
    {
        return "<input type='$type' name='$name' value='$value'>";
    }

    public function submit(string $message, string $name = ''): string
    {
        return "<button type='submit'" . ($name ? " name='$name'" : '') . ">$message</button>";
    }

    public function close(): string
    {
        return "</form>";
    }
}