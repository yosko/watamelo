<?php
namespace Watamelo\Managers;

/**
 * Abstract class
 * Base for all data managers
 */
abstract class Manager extends \Watamelo\App\ApplicationComponent {
    protected $dao;

    public function __construct(\Watamelo\App\Application $app) {
        parent::__construct($app);
        $this->dao = $app->dao();
    }
}
