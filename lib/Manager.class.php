<?php
namespace Watamelo\Lib;

/**
 * Abstract class
 * Base for all data managers
 */
abstract class Manager extends \Watamelo\Lib\ApplicationComponent
{
    protected $dao;

    public function __construct(\Watamelo\Lib\Application $app)
    {
        parent::__construct($app);
        $this->dao = $app->dao();
    }
}
