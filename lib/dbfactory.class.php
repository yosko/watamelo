<?php

/**
 * Database manager
 */
class DbFactory {
    private static $PDOInstance;
    
    /**
     * Get the connexion object
     * @param  string $dbms   type of database (sqlite, mysql, etc...)
     * @param  string $dbName database name (ignored for sqlite)
     * @return object         connexion object (PDO instance)
     */
    public static function getConnexion($dbms = "sqlite", $dbName = "database") {
        //instanciate PDO singleton)
        if(!self::$PDOInstance) {
            $db = false;
            if($dbms == "sqlite") {
                $db = self::startSqliteConnexion($dbName);
            } elseif($dbms == "mysql") {
                $db = self::startMysqlConnexion($dbName);
            } elseif($dbms == "postgresql") {
                $db = self::startPostgresConnexion($dbName);
            }
            
            self::$PDOInstance = $db;
        }
        
        return self::$PDOInstance;
	}

    /**
     * Start a sqlite connexion
     * @param  string $dbName sqlite database file name (without its ".db" extension)
     * @return object         connexion object (PDO instance)
     */
    public static function startSqliteConnexion($dbName) {
        $db = false;
		try {
			$db = new PDO('sqlite:'.ROOT.'/data/db/'.$dbName.'.db');
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		
		return $db;
	}
    
    /**
     * Start a MySQL connexion
     * @param  string $dbName database name
     * @return object         connexion object (PDO instance)
     */
    public static function startMysqlConnexion($dbName) {
		$db = new PDO('mysql:host=localhost;dbname='.$dbName, 'root', '');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
		return $db;
	}
    
    /**
     * Start a PostgreSQL connexion
     * @param  string $dbName database name
     * @return object         connexion object (PDO instance)
     */
	public static function startPostgresConnexion() {
		return false;
	}
}

?>