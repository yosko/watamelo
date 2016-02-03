<?php

namespace Watamelo\Managers;

/**
 * Abstract manager to handle common tasks
 */
class WatameloManager extends Manager  {
    protected $tables, $prefix;

    public function __construct(\Watamelo\App\Application $app) {
        parent::__construct($app);

        $this->prefix = $this->app()->config()->get('db.prefix');

        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '".$this->prefix."%'";
        $qry = $this->dao->prepare( $sql );
        $qry->execute();
        $result = $qry->fetchAll(\PDO::FETCH_OBJ);

        foreach ($result as $result) {
            $this->tables[substr($result->name, strlen($this->prefix))] = $result->name;
        }
    }

    /**
     * Initialize a SqlGenerator object with the default db access and table list
     * @return SqlGenerator object
     */
    public function newSqlGenerator() {
        return new \Watamelo\Utils\SqlGenerator($this->app, $this->dao, $this->tables);
    }

    /**
     * Execute given SqlGenerator query within a transaction
     * @return misc true or last inserted id on success, false on failure
     */
    public function executeTransaction($sql, $returnLastInsertId = false) {
        $sql->beginTransaction();
        try {
            $result = $sql->execute();
            if($returnLastInsertId) {
                $result = $sql->lastInsertId();
            } else {
                $result = true;
            }

            $sql->commit();

        } catch (PDOException $e) {
            $sql->rollback();
            $this->app()->logException($e);
            return false;
        }

        return $result;
    }
}
