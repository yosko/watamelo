<?php

namespace Watamelo\Data;

class UserLevel extends Data
{
    public ?int $id = null;
    public string $nom = '';
    public int $level = 0;

    public function getTitle(): string
    {
        return sprintf("[%d] %s", $this->level, $this->name);
    }
}