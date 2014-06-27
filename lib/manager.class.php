<?php

/**
 * Abstract class
 * Base for all data managers
 */
abstract class Manager extends ApplicationComponent {
    protected $dao;

    public function __construct(Application $app, $dao) {
        parent::__construct($app);
		$this->dao = $dao;
	}
}

?>