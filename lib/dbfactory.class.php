<?php
namespace Watamelo\App;

/**
 * Database manager
 */
class DbFactory {
    private static $PDOInstance;

    /**
     * Get the connexion object
     * @param  string  $dbms    type of database (sqlite, mysql, etc...)
     * @param  object  $dbParam database parameters (connection string, etc...)
     * @return object           connexion object (PDO instance)
     */
    public static function getConnexion($dbms = "sqlite", $dbParam) {
        //instanciate PDO singleton)
        if(!self::$PDOInstance) {
            $db = false;
            if($dbms == "sqlite") {
                $db = self::startSqliteConnexion($dbParam);
            } elseif($dbms == "mysql") {
                $db = self::startMysqlConnexion($dbParam);
            } elseif($dbms == "postgresql") {
                $db = self::startPostgresConnexion($dbParam);
            }

            self::$PDOInstance = $db;
        }

        return self::$PDOInstance;
    }

    /**
     * Start a sqlite connexion
     * @param  object $dbParam database parameters (file name under ->dbName)
     * @return object          connexion object (PDO instance)
     */
    public static function startSqliteConnexion($dbParam) {
        $db = false;
        try {
            $db = new \PDO('sqlite:'.DB_PATH.$dbParam->dbName);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo $e->getMessage();
        }

        return $db;
    }

    /**
     * Start a MySQL connexion
     * @param  object $dbParam database parameters (connection string, etc...)
     * @return object          connexion object (PDO instance)
     */
    public static function startMysqlConnexion($dbParam) {
        $db = new PDO('mysql:host=localhost;dbname='.$dbParam->dbName, 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $db;
    }

    /**
     * Start a PostgreSQL connexion
     * @param  object $dbParam database parameters (connection string, etc...)
     * @return object          connexion object (PDO instance)
     */
    public static function startPostgresConnexion() {
        return false;
    }
}
