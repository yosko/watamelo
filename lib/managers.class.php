<?php

/**
 * Manager of all managers
 * Used to load them when needed
 */
class Managers {
	protected $dao = null;
	protected $managers = array();

	public function __construct($dbType, $dbName) {
        $this->dao = DbFactory::getConnexion($dbType, $dbName);
	}

	/**
	 * Returns a manager (loads it if not already loaded)
	 * @param  string $module manager name (case insensitive)
	 * @return object         manager
	 */
	public function getManagerOf($module) {
		if (!is_string($module) || empty($module)) {
			throw new InvalidArgumentException('Invalid module');
		}
		
		if (!isset($this->managers[$module])) {
			$manager = $module.'manager';
			$this->managers[$module] = new $manager($this->dao);
		}
		
		return $this->managers[$module];
	}
}

?>