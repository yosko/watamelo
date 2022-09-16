<?php

namespace Watamelo\Lib;

use PDO;

/**
 * Abstract class
 * Base for all data managers
 */
abstract class Manager extends ApplicationComponent
{
    protected ?PDO $dao;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->dao = $app->dao();
    }
}
