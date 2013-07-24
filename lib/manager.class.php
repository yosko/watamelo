<?php

/**
 * Abstract class
 * Base for all data managers
 */
abstract class Manager {
    protected $dao;

	public function __construct($dao) {
		$this->dao = $dao;
	}
}

?>