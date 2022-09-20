<?php /** @noinspection SqlWithoutWhere */

namespace Watamelo\Utils;

use BadMethodCallException;
use LogicException;
use PDO;
use RuntimeException;

/**
 * Generate sql queries
 */
class SqlGenerator
{
    private const TYPE_SELECT = 'select';
    private const TYPE_INSERT = 'insert';
    private const TYPE_UPDATE = 'update';
    private const TYPE_DELETE = 'delete';

    protected PDO $dao;

    /**
     * @var mixed table to query (might be a table name as string, or another subquery as SqlGenerator object
     */
    protected $table;
    protected array $tables = [];

    /**
     * @var string type of query: select, insert, update, delete, ...
     */
    protected string $type;

    /**
     * @var string table alias
     */
    protected string $alias;

    /**
     * @var array fields to select
     */
    protected array $selectFields = [];

    protected bool $selectDistinct;

    /**
     * @var array other SqlGenerator select object to make a UNION SELECT query
     */
    protected array $selectUnions = [];

    /**
     * @var array fields to insert or update
     */
    protected array $setFields = [];

    protected array $joins = [];

    /**
     * @var array where clauses
     */
    protected array $where = [];

    /**
     * @var array values to bind
     */
    protected array $params = [];
    protected array $groupBy = [];

    /**
     * @var array fields to order by
     */
    protected array $orderBy = [];
    protected array $having = [];
    protected int $limitQuantity;
    protected int $limitOffset;

    /**
     * Initialize a generic sql object
     * @param PDO $dao PDO data access object
     * @param array $tables list of tables (keys without prefix, value with prefix)
     */
    public function __construct(PDO $dao, array $tables)
    {
        $this->dao = $dao;
        $this->tables = $tables;

        $this->selectFields = [];
        $this->setFields = [];
        $this->joins = [];
        $this->where = [];
        $this->params = [];
        $this->groupBy = [];
        $this->orderBy = [];
        $this->having = [];
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * Initialize a select statement
     * @param string $table name of main table to select
     * @param string $alias
     * @param array $fields fields and formulas to select
     *                         - associative array item: key used as alias
     *                         - classic array item: no alias
     * @param bool $distinct
     * @return SqlGenerator
     */
    public function select(string $table, string $alias, array $fields, bool $distinct = false): SqlGenerator
    {
        $this->type = self::TYPE_SELECT;
        $this->table = $table;
        $this->alias = $alias;
        $this->selectDistinct = $distinct;
        if (!empty($fieldAliases)) {
            //TODO array_merge $fields and $aliases?
        } else {
            $this->selectFields = $fields;
        }
        return $this;
    }

    /**
     * Add another query in the form of a UNION SELECT (can be done multiple times)
     * @param SqlGenerator $qry query to selection in union with the current one
     * @return SqlGenerator
     */
    public function unionSelect(SqlGenerator $qry): SqlGenerator
    {
        $this->selectUnions[] = $qry;
        return $this;
    }

    public function insert($table): SqlGenerator
    {
        $this->type = self::TYPE_INSERT;
        $this->table = $table;
        return $this;
    }

    /**
     * Initialize an update statement
     * @param string $table name of main table to select
     * @return SqlGenerator
     */
    public function update(string $table): SqlGenerator
    {
        $this->type = self::TYPE_UPDATE;
        $this->table = $table;
        return $this;
    }

    /**
     * on an insert/update statement: set the given field with the given value
     * @param string $field field name
     * @param mixed $value value to assign
     * @param int $type type of parameter handled by PDO (such as \PDO::PARAM_INT,  \PDO::PARAM_STR, \PDO::PARAM_NULL)
     * @return SqlGenerator
     */
    public function setField(string $field, $value, int $type = PDO::PARAM_STR): SqlGenerator
    {
        $this->setFields[] = $field;
        return $this->bindParam($field, $value, $type);
    }

    /**
     * Bind a value to use inside the query.
     * Will be assigned via the PDO bindParam method during the execute()
     * @param string $name name of the value (a ':' will be appended) like the one used in any clause (such as where)
     * @param mixed $value value to use in query
     * @param int $type type of parameter handled by PDO (such as \PDO::PARAM_INT,  \PDO::PARAM_STR, \PDO::PARAM_NULL)
     * @return SqlGenerator
     */
    public function bindParam(string $name, $value, int $type = PDO::PARAM_STR): SqlGenerator
    {
        $this->params[$name] = array(
            'bind' => 'param',
            'value' => $value,
            'type' => is_null($value) ? PDO::PARAM_NULL : $type
        );
        return $this;
    }

    public function delete($table): SqlGenerator
    {
        $this->type = self::TYPE_DELETE;
        $this->table = $table;
        return $this;
    }

    public function innerJoin($table, $alias, $clause, $fields = array()): SqlGenerator
    {
        return $this->join($table, $alias, $clause, $fields, 'INNER');
    }

    public function join($table, $alias, $clause, $fields = array(), $type = ''): SqlGenerator
    {
        $this->joins[] = array(
            'type' => $type,
            'table' => $table,
            'alias' => $alias,
            'clause' => $clause
        );
        return $this->addSelectFields($fields);
    }

    /**
     * Add fields to the select result
     * @param array $fields fields and formulas to select
     *                      - associative array item: key used as alias
     *                      - classic array item: no alias
     * @return SqlGenerator
     */
    public function addSelectFields(array $fields): SqlGenerator
    {
        $this->selectFields = array_merge($this->selectFields, $fields);
        return $this;
    }

    public function outerJoin($table, $alias, $clause, $fields = array()): SqlGenerator
    {
        return $this->join($table, $alias, $clause, $fields, 'FULL OUTER');
    }

    public function leftJoin($table, $alias, $clause, $fields = array()): SqlGenerator
    {
        return $this->join($table, $alias, $clause, $fields, 'LEFT');
    }

    public function rightJoin($table, $alias, $clause, $fields = array()): SqlGenerator
    {
        return $this->join($table, $alias, $clause, $fields, 'RIGHT');
    }

    /**
     * Add a where clause to the query
     * @param string $clause Sql where clause. It might contain a PDO assignation name ( field = :value ).
     * @param string|null $name
     * @param mixed|null $value
     * @param int|null $type PDO types
     * @return SqlGenerator
     */
    public function where(string $clause, string $name = null, $value = null, int $type = null): SqlGenerator
    {
        $this->where[] = $clause;
        if (!is_null($name)) {
            $this->bindParam($name, $value, $type);
        }
        return $this;
    }

    public function groupBy($field): SqlGenerator
    {
        $this->groupBy[] = $field;
        return $this;
    }

    /**
     * Add an order by clause
     * @param string $sort field to sort on
     * @param string $order order (asc or desc, case insensitive)
     * @return SqlGenerator
     */
    public function orderBy(string $sort, string $order = 'asc'): SqlGenerator
    {

        // 'asc' by default, 'desc' if explicitly requested
        $order = strtolower($order);
        if ($order != 'desc') {
            $order = 'asc';
        }

        $this->orderBy[] = array(
            'sort' => $sort,
            'order' => $order
        );
        return $this;
    }

    public function having(): SqlGenerator
    {
        //TODO
        throw new BadMethodCallException('Method "having()" not yet implemented');
        //return $this;
    }

    public function limit(int $quantity = 0, int $offset = 0): SqlGenerator
    {
        $this->limitQuantity = $quantity;
        $this->limitOffset = $offset;
        return $this;
    }

    /**
     * Build and execute the query
     * @param string $fetchMethod choice between fetch, fetchAll and fetchColumn
     * @param int $fetchParam PDO fetch
     * @param null $className class to use when $fetchParam is \PDO::FETCH_CLASS
     * @return bool|object execution state or result
     */
    public function execute($fetchMethod = 'fetchAll', $fetchParam = PDO::FETCH_OBJ, $className = null)
    {
        if (empty($this->type)) {
            throw new LogicException('No query to execute');
        }

        $sql = $this->buildQuery();
        $qry = $this->dao->prepare($sql);

        //bind parameters to query
        foreach ($this->params as $name => $param) {
            $qry->bindParam(':' . $name, $param['value'], $param['type']);
        }

        $result = $qry->execute();

        if ($this->type == self::TYPE_SELECT) {
            //define fetching mode
            if ($fetchParam == PDO::FETCH_CLASS && !empty($className)) {
                //PDO::FETCH_PROPS_LATE assigns properties AFTER executing object constructor
                $qry->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $className);
            } else {
                $qry->setFetchMode($fetchParam);
            }

            //fetch data using the method requested
            $result = $qry->$fetchMethod();
            //if ($result === false) {
                //throw new SqlGeneratorException('PDO fetch returned false: ' . implode(', ',$qry->errorInfo()));
            //}
        }

        return $result;
    }

    protected function buildQuery(): string
    {
        $sql = '';
        if ($this->type == self::TYPE_SELECT) {
            $sql = 'SELECT ';
            if ($this->selectDistinct) {
                $sql .= 'DISTINCT ';
            }
            foreach ($this->selectFields as $alias => $field) {
                if (Tools::isInt($alias)) {
                    $sql .= $field . ', ';
                } else {
                    $sql .= $field . ' as ' . $alias . ', ';
                }
            }
            $sql = rtrim($sql, ', ');
            $sql .= "\nFROM ";
            //select using a subquery made with SqlGenerator
            if (is_object($this->table) && $this->table instanceof $this) {
                $sql .= '(' . $this->table->buildQuery() . ')';

                //join using table name
            } else {
                $sql .= $this->tables[$this->table];
            }

            if (!empty($this->alias)) {
                $sql .= ' ' . $this->alias;
            }
        } elseif ($this->type == self::TYPE_INSERT) {
            $sql = 'INSERT INTO ' . $this->tables[$this->table] . "\n(" . implode(', ',
                    $this->setFields) . ")\nVALUES (";
            foreach ($this->setFields as $field) {
                $sql .= ':' . $field . ', ';
            }
            $sql = rtrim($sql, ', ');
            $sql .= ')';
        } elseif ($this->type == self::TYPE_UPDATE) {
            $sql = 'UPDATE ' . $this->tables[$this->table] . "\nSET ";
            foreach ($this->setFields as $field) {
                $sql .= $field . ' = :' . $field . ', ';
            }
            $sql = rtrim($sql, ', ');
        } elseif ($this->type == self::TYPE_DELETE) {
            $sql = 'DELETE FROM ' . $this->tables[$this->table];
        }

        //joins
        if (!empty($this->joins) && in_array($this->type, array(self::TYPE_SELECT))) {
            foreach ($this->joins as $join) {
                $sql .= "\n";
                if (!empty($join['type'])) {
                    $sql .= $join['type'] . ' ';
                }
                $sql .= ' JOIN ';

                //join using a subquery made with SqlGenerator
                if (is_object($join['table']) && $join['table'] instanceof SqlGenerator) {
                    $sql .= '(' . $join['table']->buildQuery() . ')';

                    //join using table name
                } else {
                    $sql .= $this->tables[$join['table']];
                }

                $sql .= ' ' . $join['alias'] . ' ON ' . $join['clause'];
            }
        }

        //where clauses
        if (!empty($this->where) && in_array($this->type,
                array(self::TYPE_SELECT, self::TYPE_UPDATE, self::TYPE_DELETE))) {
            $sql .= "\nWHERE " . implode(' AND ', $this->where);
        }

        //group by
        if (!empty($this->groupBy) && $this->type == self::TYPE_SELECT) {
            $sql .= "\nGROUP BY " . implode(', ', $this->groupBy);
        }

        //order by
        if (!empty($this->orderBy) && $this->type == self::TYPE_SELECT) {
            $sql .= "\nORDER BY ";
            foreach ($this->orderBy as $value) {
                $sql .= $value['sort'] . ' ' . $value['order'] . ', ';
            }
            $sql = rtrim($sql, ', ');
        }

        //having
        if (!empty($this->having) && $this->type == self::TYPE_SELECT) {
            //TODO
        }

        //limit
        if ((!empty($this->limitQuantity) || !empty($this->limitOffset)) && $this->type == self::TYPE_SELECT) {
            if ($this->limitQuantity > 0) {
                $sql .= "\nLIMIT ";
                if ($this->limitOffset > 0) {
                    $sql .= $this->limitOffset . ', ' . $this->limitQuantity;
                } else {
                    $sql .= $this->limitQuantity;
                }
            }
        }

        //union
        if (!empty($this->selectUnions)) {
            foreach ($this->selectUnions as $unionQry) {
                if ($unionQry->type() == self::TYPE_SELECT) {
                    $sql .= "\nUNION " . $unionQry->buildQuery();
                } else {
                    throw new LogicException('Union must all be select queries.');
                }
            }
        }

        return $sql;
    }

    public function toString(bool $replaceValues = true): string
    {
        $sql = $this->buildQuery();
        if ($replaceValues) {
            foreach ($this->params as $name => $param) {
                if (is_null($param['value'])) {
                    $sql = str_replace(':' . $name, 'NULL', $sql);
                } elseif ($param['type'] == PDO::PARAM_STR) {
                    $sql = str_replace(':' . $name, '"' . $param['value'] . '"', $sql);
                } else {
                    $sql = str_replace(':' . $name, $param['value'], $sql);
                }
            }
        }
        return $sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Execute an SQL file
     * @param string $filePath path to SQL file
     * @return int number of lines affected
     */
    public function executeFile(string $filePath): int
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException('SQL file not found');
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
    public function inTransaction(): bool
    {
        return $this->dao->inTransaction();
    }

    public function beginTransaction(): bool
    {
        return $this->dao->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->dao->commit();
    }

    public function rollback(): bool
    {
        return $this->dao->rollback();
    }

    public function lastInsertId(): string
    {
        return $this->dao->lastInsertId();
    }
}

class SqlGeneratorException extends RuntimeException {}