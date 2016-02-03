<?php
namespace Watamelo\Utils;

/**
 * Generate sql queries
 */
class SqlGenerator
{
    protected
        $app,
        $dao,
        $tables,
        $type,          //type of query: select, insert, update, delete, ...
        $select,        //table to select
        $alias,         //table alias
        $selectFields,  //(array) fields to select
        $selectDistinct,
        $insert,
        $update,
        $setFields,     //(array) fields to insert or update
        $delete,
        $joins,
        $where,         //(array) where clauses
        $params,        //(array) values to bind
        $groupBy,
        $orderBy,       //(array) fields to order by
        $having,
        $limitQuantity,
        $limitOffset;

    /**
     * Initialize a generic sql object
     * @param object $app    application object
     * @param object $dao    PDO data access object
     * @param array  $tables list of tables (keys without prefix, value with prefix)
     */
    public function __construct($app, $dao, $tables)
    {
        $this->app = $app;
        $this->dao = $dao;
        $this->tables = $tables;

        $this->selectFields = array();
        $this->setFields = array();
        $this->joins = array();
        $this->where = array();
        $this->params = array();
        $this->groupBy = array();
        $this->orderBy = array();
        $this->having = array();
    }

    /**
     * Initialize a select statement
     * @param  string $table   name of main table to select
     * @param  array  $fields  fields and formulas to select
     *                         - associative array item: key used as alias
     *                         - classic array item: no alias
     * @return void
     */
    public function select($table, $alias, $fields, $distinct = false)
    {
        $this->type = 'select';
        $this->table = $table;
        $this->alias = $alias;
        $this->selectDistinct = $distinct;
        if (!empty($fieldAliases)) {
            //TODO array_merge $fields and $aliases?
        } else {
            $this->selectFields = $fields;
        }
    }

    /**
     * Add fields to the select result
     * @param array $fields fields and formulas to select
     *                      - associative array item: key used as alias
     *                      - classic array item: no alias
     * @return void
     */
    public function addSelectFields($fields)
    {
        $this->selectFields = array_merge($this->selectFields, $fields);
    }

    public function insert($table, $params = array())
    {
        $this->type = 'insert';
        $this->table = $table;
        //TODO
    }

    /**
     * Initialize an update statement
     * @param  string $table   name of main table to select
     * @return void
     */
    public function update($table)
    {
        $this->type = 'update';
        $this->table = $table;
    }

    /**
     * on an insert/update statement: set the given field with the given value
     * @param  string $field field name
     * @param  misc   $value value to assign
     * @param  int    $type  type of parameter handled by PDO (such as PDO::PARAM_INT,  PDO::PARAM_STR, PDO::PARAM_NULL)
     * @return void
     */
    public function setField($field, $value, $type = \PDO::PARAM_STR)
    {
        $this->setFields[] = $field;
        $this->bindParam($field, $value, $type);
    }


    public function delete($table)
    {
        $this->type = 'delete';
        $this->table = $table;
        //TODO
    }

    public function join($table, $alias, $clause, $fields = array(), $type = '')
    {
        $this->joins[] = array(
            'type' => $type,
            'table' => $table,
            'alias' => $alias,
            'clause' => $clause
        );
        $this->addSelectFields($fields);
    }

    public function innerJoin($table, $alias, $clause, $fields = array())
    {
        $this->join($table, $alias, $clause, $fields, 'INNER');
    }

    public function outerJoin($table, $alias, $clause, $fields = array())
    {
        $this->join($table, $alias, $clause, $fields, 'FULL OUTER');
    }

    public function leftJoin($table, $alias, $clause, $fields = array())
    {
        $this->join($table, $alias, $clause, $fields, 'LEFT');
    }

    public function rightJoin($table, $alias, $clause, $fields = array())
    {
        $this->join($table, $alias, $clause, $fields, 'RIGHT');
    }

    /**
     * Add a where clause to the query
     * @param  string $clause Sql where clause. It might contain a PDO assignation name ( field = :value ).
     * @return void
     */
    public function where($clause, $name = null, $value = null, $type = null)
    {
        $this->where[] = $clause;
        if (!is_null($name)) {
            $this->bindParam($name, $value, $type);
        }
    }

    public function whereArray($clauseArray)
    {
        //TODO
    }

    public function whereArrayOr($clauseArray)
    {
        //TODO
    }

    public function groupBy($field)
    {
        $this->groupBy[] = $field;
    }

    /**
     * Add an order by clause
     * @param  string $sort  field to sort on
     * @param  string $order order (asc or desc, case insensitive)
     * @return void
     */
    public function orderBy($sort, $order = 'asc')
    {

        // 'asc' by default, 'desc' if explicitely requested
        $order = strtolower($order);
        if ($order != 'desc') {
            $order = 'asc';
        }

        $this->orderBy[] = array(
            'sort' => $sort,
            'order' => $order
        );
    }

    public function orderByArray($clauseArray)
    {
        //TODO
    }

    public function having()
    {
        //TODO
    }

    public function limit($quantity = 0, $offset = 0)
    {
        $this->limitQuantity = $quantity;
        $this->limitOffset = $offset;
    }

    /**
     * Bind a value to use inside the query.
     * Will be assigned via the PDO bindParam method during the execute()
     * @param  string $name  name of the value (a ':' will be appended) like the one used in any clause (such as where)
     * @param  misc   $value value to use in query
     * @param  int    $type  type of parameter handled by PDO (such as PDO::PARAM_INT,  PDO::PARAM_STR, PDO::PARAM_NULL)
     * @return void
     */
    public function bindParam($name, $value, $type = \PDO::PARAM_STR)
    {
        $this->params[$name] = array(
            'bind' => 'param',
            'value' => $value,
            'type' => is_null($value)?\PDO::PARAM_NULL:$type
        );
    }

    /**
     * Build and execute the query
     * @param  boolean $fetchMethod choice between fetch, fetchAll and fetchColumn
     * @param  int     $fetchParam  PDO fetch constant
     * @return boolean              execution state
     * @throws LogicException If undefined type of statement
     */
    public function execute($fetchMethod = 'fetchAll', $fetchParam = \PDO::FETCH_OBJ)
    {
        if (empty($this->type)) {
            throw new LogicException('No query to execute');
        }

        $sql = $this->buildQuery();
        $qry = $this->dao->prepare( $sql );

        //bind parameters to query
        foreach ($this->params as $name => $param) {
            $qry->bindParam(':'.$name, $param['value'], $param['type']);
        }

        $result = $qry->execute();

        if ($this->type == 'select') {
            if ($fetchMethod == 'fetchColumn')
                $result = $qry->$fetchMethod();
            else
                $result = $qry->$fetchMethod($fetchParam);
            //TODO check that fetchColumn can receive a \PDO::FETCH_OBJ as parameter...
            // if ($fetchColumn)
            //     $result = $qry->fetchColumn();
            // else
            //     $result = $qry->fetchAll($fetchParam);
        }

        return $result;
    }

    public function toString($replaceValues = true)
    {
        $sql = $this->buildQuery();
        if ($replaceValues) {
            foreach ($this->params as $name => $param) {
                if (is_null($param['value'])) {
                    $sql = str_replace(':'.$name, 'NULL', $sql);
                } elseif ($param['type'] == \PDO::PARAM_STR) {
                    $sql = str_replace(':'.$name, '"'.$param['value'].'"', $sql);
                } else {
                    $sql = str_replace(':'.$name, $param['value'], $sql);
                }
            }
        }
        return $sql;
    }

    public function getParams()
    {
        return $this->params;
    }

    protected function buildQuery()
    {
        if ($this->type == 'select') {
            $sql = 'SELECT ';
            if ($this->selectDistinct)
                $sql .= 'DISTINCT ';
            foreach ($this->selectFields as $alias => $field) {
                if (Tools::isInt($alias)) {
                    $sql .= $field.', ';
                } else {
                    $sql .= $field.' as '.$alias.', ';
                }
            }
            $sql = rtrim($sql, ', ');
            $sql .= "\nFROM ";
            //select using a subquery made with SqlGenerator
            if (is_object($this->table) && get_class($this->table) == get_class($this)) {
                $sql .= '('.$this->table->toString(false).')';

            //join using table name
            } else {
                $sql .= $this->tables[$this->table];
            }

            if (!empty($this->alias))
                $sql .= ' '.$this->alias;
        } elseif ($this->type == 'insert') {
            $sql = 'INSERT INTO '.$this->tables[$this->table]."\n(".implode(', ', $this->setFields).")\nVALUES (";
            foreach ($this->setFields as $field) {
                $sql .= ':'.$field.', ';
            }
            $sql = rtrim($sql, ', ');
            $sql .= ')';
        } elseif ($this->type == 'update') {
            $sql = 'UPDATE '.$this->tables[$this->table]."\nSET ";
            foreach ($this->setFields as $field) {
                $sql .= $field.' = :'.$field.', ';
            }
            $sql = rtrim($sql, ', ');
        } elseif ($this->type == 'delete') {
            //TODO
        }

        //joins
        if (!empty($this->joins) && in_array($this->type, array('select')) ) {
            foreach ($this->joins as $join) {
                $sql .= "\n";
                if (!empty($join['type']))
                    $sql .= $join['type'].' ';
                $sql .= ' JOIN ';

                //join using a subquery made with SqlGenerator
                if (is_object($join['table']) && get_class($join['table']) == get_class($this)) {
                    $sql .= '('.$join['table']->toString(false).')';

                //join using table name
                } else {
                    $sql .= $this->tables[$join['table']];
                }

                $sql .= ' '.$join['alias'].' ON '.$join['clause'];
            }
        }

        //where clauses
        if (!empty($this->where) && in_array($this->type, array('select', 'update', 'delete'))) {
            $sql .= "\nWHERE ".implode(' AND ', $this->where);
        }

        //group by
        if (!empty($this->groupBy) && $this->type == 'select') {
            $sql .= "\nGROUP BY ".implode(', ', $this->groupBy);
        }

        //order by
        if (!empty($this->orderBy) && $this->type == 'select') {
            $sql .= "\nORDER BY ";
            foreach ($this->orderBy as $value) {
                $sql .= $value['sort'].' '.$value['order'].', ';
            }
            $sql = rtrim($sql, ', ');
        }

        //having
        if (!empty($this->having) && $this->type == 'select') {
            //TODO
        }

        //limit
        if ((!empty($this->limitQuantity) || !empty($this->limitOffset)) && $this->type == 'select') {
            if ($this->limitQuantity > 0) {
                $sql .= "\nLIMIT ";
                if ($this->limitOffset > 0) {
                    $sql .= $this->limitOffset.', '.$this->limitQuantity;
                } else {
                    $sql .= $this->limitQuantity;
                }
            }
        }

        return $sql;
    }

    /**
     * Execute an SQL file
     * @param string $filePath path to SQL file
     */
    public function executeFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception('SQL file not found');
        }

        $sql = file_get_contents($filePath);
        $this->dao->beginTransaction();
        $result = $this->dao->exec($sql);
        $this->dao->commit();

        return $result;
    }

    /**
     * Call to PDO methods
     */
    public function beginTransaction()
    {
        return $this->dao->beginTransaction();
    }
    public function commit()
    {
        return $this->dao->commit();
    }
    public function rollback()
    {
        return $this->dao->rollback();
    }
    public function lastInsertId()
    {
        return $this->dao->lastInsertId();
    }
}
