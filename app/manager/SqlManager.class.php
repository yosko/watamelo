<?php

namespace Watamelo\Managers;

use LogicException;
use PDO;
use PDOException;
use Watamelo\Utils\SqlGenerator;

/**
 * Typical data manager
 */
class SqlManager extends WatameloManager
{
    public const SET_BY_ID = 0;
    public const SET_FOR_INSERT = 1;
    public const SET_FOR_UPDATE = 2;

    /**
     * @var array filters and settings currently active for the next query
     */
    public array $filters;

    public int $quantity;
    public int $offset;
    public array $orderBy;
    public bool $performCount;

    /**
     * Gives the number of result
     * @param array $filters
     * @return int number of news
     */
    public function count(array $filters = [])
    {
        if (!empty($filters)) {
            $this->filters = $filters;
        }

        $this->performCount = true;
        return $this->getList(false, false);
    }

    /**
     * Gives a list of rows from the table
     * @param bool $initializeFilters make sure there are no filters active
     * @param bool $initializeParams force initialization of other settings to their default
     * @return array|int array of items - each item is an \StdClass (or defined class) object or number of results (for a count)
     */
    public function getList($initializeFilters = true, $initializeParams = true)
    {
        if ($initializeParams || !isset($this->quantity)) {
            $this->setListParams();
        }
        if ($initializeFilters || !isset($this->filters)) {
            $this->filters = [];
        }

        $qry = $this->newSqlGenerator();
        if ($this->performCount) {
            $qry->select($this->tableName, $this->tableAlias, array('count' => 'count(*)'));
            $qry = $this->settingsForGetAndList($qry, true);
        } else {
            $qry->select($this->tableName, $this->tableAlias, array($this->tableAlias . '.*'));
            $qry = $this->settingsForGetAndList($qry, false);

            if (empty($this->orderBy) || empty(key($this->orderBy)) && empty(current($this->orderBy))) {
                $this->orderBy = $this->defaultOrderBy;
            }

            $firstField = key($this->orderBy);
            if (empty($firstField)) {
                $firstField = key($this->defaultOrderBy);
                $firstOrder = array_shift($this->orderBy);
                $this->orderBy = array($firstField => $firstOrder) + $this->orderBy;
            }

            foreach ($this->orderBy as $field => $order) {
                // add default alias if not already set
                if (strpos($field, '.') === false) {
                    $field = $this->tableAlias . '.' . $field;
                }

                $qry->orderBy($field, $order);
            }

            $qry->limit($this->quantity, $this->offset);
        }

        $qry = $this->applyFilters($qry);

        $rows = $qry->execute(
            $this->performCount ? 'fetchColumn' : 'fetchAll',
            empty($this->fetchClass) ? PDO::FETCH_OBJ : PDO::FETCH_CLASS,
            empty($this->fetchClass) ? null : $this->fetchClass
        );

        // result has an id: use it as array keys
        if (is_array($rows) && isset(current($rows)->id)) {
            $result = array();
            foreach ($rows as $row) {
                $result[$row->id] = $row;
            }
        } else {
            $result = $rows;
        }

        return $result;
    }

    /**
     * Initialize settings for the next query
     * @param int $quantity number of results
     * @param int $offset starting position for results
     * @param array $orderBy sort order (key: field, value: asc|desc
     * @param bool $performCount perform a count instead of a regular select
     */
    public function setListParams($quantity = 0, $offset = 0, $orderBy = array(), $performCount = false)
    {
        $this->quantity = $quantity;
        $this->offset = $offset;
        $this->orderBy = $orderBy;
        $this->performCount = $performCount;
    }

    /**
     * Handle advanced settings for the query in get() or getLis() such as
     * additional join and fields
     * @param SqlGenerator $qry Query object to modify
     * @param bool $isCount whether this is a count query
     * @return SqlGenerator          Query object modified
     */
    protected function settingsForGetAndList(SqlGenerator $qry, bool $isCount): SqlGenerator
    {
        return $qry;
    }

    /**
     * Handle filters to use in getBy() or getList();
     *
     * Example of implementation:
     * foreach ($this->filters as $field => $value) {
     * if($field == 'myField') {
     * $qry->where($this->tableAlias.'.myField = :myKey', 'myKey', $value, \PDO::PARAM_INT);
     * }
     * }
     * return $qry;
     *
     * @param SqlGenerator $qry Query object to modify
     * @param string $context list or single
     * @return SqlGenerator          Query object modified
     */
    protected function applyFilters(SqlGenerator $qry, $context = 'list'): SqlGenerator
    {
        return $qry;
    }

    /**
     * Gives a specific row from its id
     * @param object|bool $primary primary key (this default version uses a single int value for a field named id)
     * @return object the data row/object
     */
    public function get($primary)
    {
        $qry = $this->prepareGetStatement();
        $qry = $this->setPrimaryKeyFilter($qry, ['id' => ['value' => $primary, 'type' => PDO::PARAM_INT]]);

        return $qry->execute(
            'fetchAll',
            empty($this->fetchClass) ? PDO::FETCH_OBJ : PDO::FETCH_CLASS,
            empty($this->fetchClass) ? null : $this->fetchClass
        );
    }

    /**
     * Initialise query for get() or getBy()
     * @return SqlGenerator base select query
     */
    final protected function prepareGetStatement(): SqlGenerator
    {
        $qry = $this->newSqlGenerator();
        $qry->select($this->tableName, $this->tableAlias, array($this->tableAlias . '.*'));
        $qry = $this->settingsForGetAndList($qry, false);

        return $qry;
    }

    /**
     * @param SqlGenerator $qry
     * @param array $primary array with format ['<PK name>' => ['value' => <value>, 'type' => <PDO type>], ...]
     * @param bool $useAlias
     * @return SqlGenerator
     */
    protected function setPrimaryKeyFilter(SqlGenerator $qry, array $primary, bool $useAlias = true): SqlGenerator
    {
        //write conditions for each field of the primary key
        foreach ($primary as $key => $param) {
            $str = sprintf('%s = :%s', $key, $key);
            if ($useAlias) {
                $str = sprintf('%s.%s', $this->tableAlias, $str);
            }

            $qry->where($str, $key, $param['value'], $this->typeToPdo($param['type']));
        }
        return $qry;
    }

    /**
     * Gives the first row based on a custom criteria
     * @param string $filter filtering method name
     * @param mixed $value parameters required for method
     * @return object the data row/object
     */
    public function getBy(string $filter, $value): object
    {
        $qry = $this->prepareGetStatement();

        $this->filters = array($filter => $value);
        $qry = $this->applyFilters($qry);

        $qry->limit(1, 0);

        $values = $qry->execute(
            'fetch',
            empty($this->fetchClass) ? PDO::FETCH_OBJ : PDO::FETCH_CLASS,
            empty($this->fetchClass) ? null : $this->fetchClass
        );
        $values = $this->postGet($values);
        return $values;
    }

    /**
     * post get() treatment
     * @param object $values data returned by get()
     * @return object         same data after treatment
     */
    protected function postGet(object $values): object
    {
        return $values;
    }

    /**
     * Add a new row to the database
     * @param object $values row data (if present, id will be removed)
     * @return false|string id of newly inserted row or false on error
     */
    public function add(object $values)
    {
        return $this->set($values, self::SET_FOR_INSERT);
    }

    /**
     * Add or update a row
     * @param object $values row data
     * @param int $flag indicates if the insert/update explicitely (SET_FOR_INSERT / SET_FOR_UPDATE) or
     * based on an id presence (by default: SET_BY_ID)
     * @return false|string id of newly inserted row or false on error (TODO : what happens for an update?)
     */
    protected function set(object $values, int $flag = self::SET_BY_ID)
    {
        if ($flag == self::SET_BY_ID) {
            $isUpdate = isset($values->id) && $values->id !== false;
        } elseif (in_array($flag, [self::SET_FOR_INSERT, self::SET_FOR_UPDATE])) {
            $isUpdate = $flag == self::SET_FOR_UPDATE;
        } else {
            throw new LogicException('Unknown set flag.');
        }

        /**
         * PREPARE STATEMENTS
         */

        $values = $this->prepareDataForSet($values, $isUpdate);

        $qry = $this->newSqlGenerator();
        if ($isUpdate) {
            $fields = $this->getUpdateProperties();
            $qry->update($this->tableName);
            $qry = $this->setFilterForUpdate($qry, $values);
        } else {
            $fields = $this->getInsertProperties();
            $qry->insert($this->tableName);
        }

        foreach ($fields as $fieldName) {
            $qry->setField($fieldName, $values->$fieldName, $this->getPdoType($fieldName));
        }

        /**
         * EXECUTE
         */

        $this->dao->beginTransaction();
        try {
            $values = $this->preSet($values, $isUpdate);
            $result = $qry->execute();

            if (!$isUpdate) {
                $values->id = $this->dao->lastInsertId();
                $result = $values->id;
            }

            $this->postSet($values, $isUpdate);

            $this->dao->commit();

        } catch (PDOException $e) {
            $this->dao->rollback();
            return false;
        }

        return $result;
    }

    /**
     * Specific action done before a set
     *  - will be called within a transaction
     *  - should throw exceptions/PDOException
     * @param object $values data
     * @param bool $isUpdate indicates if it is an add or update
     * @return object return updated data from $values
     */
    protected function prepareDataForSet(object $values, bool $isUpdate = false): object
    {
        return $values;
    }

    /**
     * Defines the WHERE clause for an UPDATE query (by default based on the id field)
     * @param SqlGenerator $qry
     * @param object $object
     * @return SqlGenerator
     */
    public function setFilterForUpdate(SqlGenerator $qry, object $object): SqlGenerator
    {
        return $this->setPrimaryKeyFilter($qry, ['id' => ['value' => $object->id, 'type' => PDO::PARAM_INT]], false);
    }

    /**
     * Specific action done at the beginning of a set
     *  - will be called within a transaction
     *  - should throw exceptions/PDOException
     * @param mixed $values data
     * @param bool $isUpdate indicates if it is an add or update
     * @return mixed              return updated data from $values
     */
    protected function preSet($values, $isUpdate = false)
    {
        return $values;
    }

    /**
     * Specific action done after a set
     *  - will be called within a transaction
     *  - should throw exceptions/PDOException
     *  - the ->id should be set even for and add() call
     * @param mixed $values data
     * @param bool $isUpdate indicates if it is an add or update
     */
    protected function postSet($values, $isUpdate = false)
    {
    }

    /**
     * Update an existing row
     * @param object $values row data (including id)
     * @return bool success or error
     */
    public function update(object $values): bool
    {
        return (bool)$this->set($values, self::SET_FOR_UPDATE);
    }

    /**
     * Delete an existing row
     * @param array $primary row primary key values (usually an id)
     * @return bool     whether the deletion was a success
     */
    public function delete(array $primary): bool
    {
        /**
         * PREPARE STATEMENTS
         */

        $result = true;
        $qry = $this->newSqlGenerator();
        $qry->delete($this->tableName);
        foreach ($primary as $key => $value) {
            $qry->where($key . ' = :' . $key, $key, $primary[$key], PDO::PARAM_INT);
        }

        /**
         * EXECUTE
         */

        $this->dao->beginTransaction();
        try {
            $this->preDelete($primary);
            $qry->execute();
            $this->postDelete($primary);
            $this->dao->commit();
        } catch (PDOException $e) {
            $this->dao->rollback();
            $result = false;
        }

        return $result;
    }

    /**
     * Specific action done before a delete
     *  - will be called within a transaction
     *  - should throw exceptions/PDOException
     * @param array $primary row primary key
     */
    protected function preDelete(array $primary)
    {
    }

    /**
     * Specific action done after a delete
     *  - will be called within a transaction
     *  - should throw exceptions/PDOException
     * @param array $primary row primary keys
     */
    protected function postDelete(array $primary)
    {
    }
}
