<?php

namespace Yosko\Watamelo;

use PDO;

/**
 * Abstract class
 * Base for all data managers
 */
abstract class AbstractManager extends AbstractComponent
{
    protected ?PDO $dao;

    public function __construct(AbstractApplication $app)
    {
        parent::__construct($app);
        $this->dao = $app->dao();
    }
}
