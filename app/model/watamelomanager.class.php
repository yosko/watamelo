<?php

/**
 * Abstract manager to handle common tasks
 */
class WatameloManager extends Manager  {
    protected $tables;

    public function __construct(Application $app, $dao) {
        parent::__construct($app, $dao);

        $prefix = $this->app()->config()->get('db.prefix');

        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '".$prefix."%'";
        $qry = $this->dao->prepare( $sql );
        $qry->execute();
        $result = $qry->fetchAll(PDO::FETCH_OBJ);

        foreach ($result as $result) {
            $this->tables[substr($result->name, strlen($prefix))] = $result->name;
        }
    }
}

?>