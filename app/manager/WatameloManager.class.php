<?php

namespace Watamelo\Managers;

use PDO;
use PDOException;
use Watamelo\Lib\Application;
use Watamelo\Lib\Manager;
use Watamelo\Utils\SqlGenerator;

/**
 * Abstract manager to handle common tasks
 */
class WatameloManager extends Manager
{
    protected array $tables;
    protected string $prefix;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->prefix = $this->app()->config()->get('db.prefix');

        $sql = sprintf("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%s%%'", $this->prefix);
        $qry = $this->dao->prepare($sql);
        $qry->execute();
        $results = $qry->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $result) {
            $this->tables[substr($result->name, strlen($this->prefix))] = $result->name;
        }
    }

    /**
     * Initialize a SqlGenerator object with the default db access and table list
     * @return SqlGenerator object
     */
    public function newSqlGenerator(): SqlGenerator
    {
        return new SqlGenerator($this->dao, $this->tables);
    }

    /**
     * Execute given SqlGenerator query within a transaction
     * @param SqlGenerator $sql
     * @param bool $returnLastInsertId
     * @return int|bool true or last inserted id on success, false on failure
     */
    public function executeTransaction(SqlGenerator $sql, bool $returnLastInsertId = false)
    {
        $alreadyInTransaction = $sql->inTransaction();
        if (!$alreadyInTransaction) {
            $sql->beginTransaction();
        }
        try {
            $sql->execute();
            if ($returnLastInsertId) {
                $result = $sql->lastInsertId();
            } else {
                $result = true;
            }

            if (!$alreadyInTransaction) {
                $sql->commit();
            }

        } catch (PDOException $e) {
            if (!$alreadyInTransaction) {
                $sql->rollback();
            }
            $this->app()->logException($e);
            return false;
        }

        return $result;
    }
}
