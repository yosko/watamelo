<?php

namespace Watamelo\Managers;

use Watamelo\Data\UserLevel;

class UserLevelManager extends DataManager
{
    protected string $fetchClass = UserLevel::class;
    protected string $tableName = 'user_level';
    protected string $tableAlias = 'ul';
    protected array $defaultOrderBy = ['level' => 'desc'];

    protected array $properties = [
        'id' => ['type' => self::TYPE_INT, 'primary' => true],
        'name' => ['type' => self::TYPE_TEXT, 'insert' => true, 'update' => true, 'required' => true],
        'level' => ['type' => self::TYPE_INT, 'insert' => true, 'update' => true, 'required' => true]
    ];
}
