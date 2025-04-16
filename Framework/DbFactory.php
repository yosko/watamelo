<?php

namespace Watamelo\Framework;

use Exception;
use PDO;
use PDOException;
use StdClass;

/**
 * Database manager
 */
class DbFactory
{
    private static PDO $PDOInstance;

    /**
     * Get the connexion object
     * @param string $dbms type of database (sqlite, mysql, etc...)
     * @param StdClass $dbParam database parameters (connection string, etc...)
     * @return PDO|false           connexion object (PDO instance)
     */
    public static function getConnection(string $dbms, StdClass $dbParam)
    {
        //instantiate PDO singleton
        if (!isset(self::$PDOInstance) || !self::$PDOInstance) {
            $db = false;
            if ($dbms == "sqlite") {
                $db = self::startSqliteConnexion($dbParam);
            } elseif ($dbms == "mysql") {
                $db = self::startMysqlConnexion($dbParam);
            } elseif ($dbms == "postgresql") {
                $db = self::startPostgresConnexion($dbParam);
            }

            self::$PDOInstance = $db;
        }

        return self::$PDOInstance;
    }

    /**
     * Start a sqlite connexion
     * @param StdClass $dbParam database parameters (file name under ->dbName)
     * @return PDO|false          connexion object (PDO instance)
     */
    public static function startSqliteConnexion(StdClass $dbParam)
    {
        $db = false;
        try {
            $db = new PDO('sqlite:' . $dbParam->dbPath . $dbParam->dbName, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return $db;
    }

    /**
     * Start a MySQL connexion
     * @param StdClass $dbParam database parameters (connection string, etc...)
     * @return PDO|false          connexion object (PDO instance)
     */
    public static function startMysqlConnexion(StdClass $dbParam)
    {
        $db = new PDO('mysql:host=localhost;dbname=' . $dbParam->dbName, 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $db;
    }

    /**
     * Start a PostgreSQL connexion
     * @param StdClass $dbParam database parameters (connection string, etc...)
     * @return PDO|false          connexion object (PDO instance)
     */
    public static function startPostgresConnexion(StdClass $dbParam)
    {
        throw new Exception("PostgreSQL not implemented yet");
    }
}
