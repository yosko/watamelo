<?php

namespace Watamelo\Managers;

use PDO;
use Watamelo\Data\Data;
use Watamelo\Utils\SqlGenerator;

/**
 * Typical data manager
 */
class DataManager extends SqlManager
{
    public const TYPE_INT = 1;
    public const TYPE_FLOAT = 2;
    public const TYPE_TEXT = 3;
    public const TYPE_TEXT_MULTI = 4;
    public const TYPE_BOOL = 5;
    public const TYPE_DATE = 6;
    public const TYPE_DATETIME = 7;
    public const TYPE_PASSWORD = 8;
    public const TYPE_MONEY = 10;
    /**
     * @var string class used for PDO::FETCH_CLASS
     */
    protected string $fetchClass = '';
    /**
     * @var string alias used in SQL queries
     */
    protected string $tableAlias = '';
    /**
     * @var string table alias to use in SQL queries
     */
    protected string $tableName = '';
    /**
     * @var array fields to sort by array('field' => 'asc|desc')
     */
    protected array $defaultOrderBy = [];
    /**
     * @var array list of possible filters on collection
     */
    protected array $availableFilters = [];
    /**
     * @var array definition of each property. Only the type is required. Other parameters can be omitted or set to false
     */
    protected array $properties = [
        /*
        'propertyName' => [
            'type' => self::TYPE_*, // REQUIRED type of the property
            'primary' => true, // is a primary key
            'foreignKey' => [ // is a foreign key (causes an automatic join
                'class' => 'Client', // corresponding foreign table
                'key' => 'id', // corresponding field in foreign table
                'fields' => ['name' => 'nom_client'] // fields to retrieve for display instead of the foreign key
            ],
            'foreign' => [ // is a foreign field retrieved through a join (should not be used if 'foreignKey' is
                'class' => 'Client', // corresponding foreign table
                'field' => 'name' // corresponding name of field in foreign table
            ],
            'insert' => true, // set during inserts
            'update' => true, // set during updates
            'required' => true, // cannot be null nor empty
        ],
        */
    ];
    private array $models = [
        'Client' => [
            'children' => ['Promo', 'Commande', 'Paiement']
        ],
        'Promo' => [
            'children' => ['Commande']
        ],
        'Matière' => [],
        'User' => [],
        'UserLevel' => [],
        'Commande' => [
            'children' => ['CommandeDetail', 'Facture']
        ],
        'CommandeDetail' => [],
        'Facture' => [
            'children' => ['FactureDetail', 'PaiementFacture']
        ],
        'FactureDetail' => [],
        'Paiement' => [
            'children' => ['PaiementFacture']
        ],
        'PaiementFacture' => [],
        'Statut' => [],
        'Intervention' => []
    ];
    /**
     * @var array correspondence between DataManager types and PDO types
     */
    private array $typesToPdo = [
        self::TYPE_INT => PDO::PARAM_INT,
        self::TYPE_FLOAT => PDO::PARAM_STR,
        self::TYPE_TEXT => PDO::PARAM_STR,
        self::TYPE_TEXT_MULTI => PDO::PARAM_STR,
        self::TYPE_BOOL => PDO::PARAM_BOOL,
        self::TYPE_DATE => PDO::PARAM_STR,
        self::TYPE_DATETIME => PDO::PARAM_STR,
        self::TYPE_PASSWORD => PDO::PARAM_STR,
        self::TYPE_MONEY => PDO::PARAM_STR
    ];

    /**
     * Gives a list of possible filters on a collection
     * @return array
     */
    public function getAvailableFilters(): array
    {
        return $this->availableFilters;
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getModelChildren(): array
    {
        $class = call_user_func($this->getDataClass() . "::getClassName");
        if (empty($this->models[$class]['children'])) {
            return [];
        }
        return $this->models[$class]['children'];
    }

    /**
     * Get class used for instances (and for PDO fetch)
     * @return string Class name
     */
    public function getDataClass(): string
    {
        return $this->fetchClass;
    }

    /**
     * Return information about a given property
     * @param $property
     * @return array|null
     */
    public function getProperty($property): ?array
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Return PDO type (PDO::PARAM_*) used for the given property
     * @param $property
     * @return int
     */
    public function getPdoType($property): int
    {
        return $this->typeToPdo($this->getPropertyType($property));
    }

    public function typeToPdo($type): int
    {
        return $this->typesToPdo[$type];
    }

    /**
     * Return type of a given property (based on TYPE_* constants
     * @param $property
     * @return int
     */
    public function getPropertyType($property): int
    {
        // TODO handle foreign data by retrieving types from other manager
        return $this->properties[$property]['type'];
    }

    public function getNonForeignPrimaryKeys(): array
    {
        $pkeys = $this->getPropertiesHaving('primary', 'foreignKey');
        foreach ($pkeys as $key => $value) {
            if (!empty($value)) {
                unset($pkeys[$key]);
            }
        }
        return $pkeys;
    }

    protected function getPropertiesHaving(string $param, string $subElement = ''): array
    {
        $properties = [];
        foreach ($this->properties as $name => $info) {
            if (!empty($info[$param])) {
                if (empty($subElement)) {
                    $properties[] = $name;
                } else {
                    $properties[$name] = $info[$subElement] ?? null;
                }
            }
        }
        return $properties;
    }

    public function getIdString(Data $object): string
    {
        $pks = $this->getPrimaryKeys();
        $pkValues = [];
        foreach ($pks as $pk) {
            $pkValues[] = $object->$pk;
        }
        return implode('-', $pkValues);
    }

    public function getPrimaryKeys(): array
    {
        return $this->getPropertiesHaving('primary');
    }

    public function parseIdsString(string $idsString): array
    {
        return array_combine($this->getPrimaryKeys(), explode('-', $idsString));
    }

    public function isPrimaryKeys($property): bool
    {
        return in_array($property, $this->getPrimaryKeys());
    }

    public function getCommonProperties(): array
    {
        return array_unique(array_merge(
            $this->getPropertiesHaving('insert'),
            $this->getPropertiesHaving('update')
        ));
    }

    public function isReadable($property): bool
    {
        return $this->isPropertyDefined($property)
            && !$this->isForeignKey($property)
            && $property != 'id';
    }

    public function isPropertyDefined($property): bool
    {
        return is_string($property) && isset($this->properties[$property]);
    }

    public function isForeignKey(string $property): bool
    {
        return isset($this->getForeignKeys()[$property]);
    }

    /**
     * List properties used during an insert
     * @return array properties list
     */
    public function getForeignKeys(): array
    {
        return $this->getPropertiesHaving('foreignKey', 'foreignKey');
    }

    /**
     * List properties used for display
     * @return array properties list
     */
    public function getReadableProperties(): array
    {
        return array_unique(array_merge(
            $this->getPropertiesHaving('primary'),
            $this->getPropertiesHaving('insert'),
            $this->getPropertiesHaving('update')
        ));
    }

    public function isPropertySecondary($property): int
    {
        return !empty($this->properties[$property]['secondary']);
    }

    public function getColor(Data $object, string $property, $type = 'foreground'): string
    {
        // return '#'.substr(md5($object->$property), 0, 6);

        if ($this->isForeignData($property)) {
            $info = $this->getForeignData($property);
            $name = $info['field'];
            $foreignManager = $this->app()->manager($info['class']);
            //$class = $foreignManager->getDataClass();

            // TODO : remove use of id and find another solution
            $idField = 'id' . $info['class'];
            if (!empty($object->$idField)) {
                $foreignObject = $foreignManager->get(['id' => $object->$idField]);
                if ($type == 'foreground') {
                    return $foreignManager->getForegroundColor($foreignObject, $name);
                } elseif ($type == 'background') {
                    return $foreignManager->getBackgroundColor($foreignObject, $name);
                }
            }
        }
        return '';
    }

    public function isForeignData(string $property): bool
    {
        return isset($this->getForeignDatas()[$property]);
    }

    public function getForeignDatas(): array
    {
        return $this->getPropertiesHaving('foreign', 'foreign');
    }

    public function getForeignData($property): array
    {
        return $this->getForeignDatas()[$property];
    }

    public function getForegroundColor(Data $object, string $property): string
    {
        return $this->getColor($object, $property, 'foreground');
    }

    public function getBackgroundColor(Data $object, string $property): string
    {
        return $this->getColor($object, $property, 'background');
    }

    /**
     * Gives a specific row from its primary key
     * @param ?object $primary primary key (directly the parameter if single field, or an array with field names)
     * @return object the data row/object
     */
    public function get($primary)
    {
        $qry = $this->prepareGetStatement();
        foreach ($primary as $name => $value) {
            $primary[$name] = ['value' => $value, 'type' => $this->getPropertyType($name)];
        }
        $qry = $this->setPrimaryKeyFilter($qry, $primary);

        return $qry->execute(
            'fetch',
            empty($this->fetchClass) ? PDO::FETCH_OBJ : PDO::FETCH_CLASS,
            empty($this->fetchClass) ? null : $this->fetchClass
        ) ?: null;
    }

    /**
     * Defines the WHERE clause for UPDATE based on primary keys
     * @param SqlGenerator $qry
     * @param object $object
     * @return SqlGenerator
     */
    public function setFilterForUpdate(SqlGenerator $qry, object $object): SqlGenerator
    {
        $primaryValues = [];
        foreach ($this->getPrimaryKeys() as $pk) {
            $primaryValues[$pk] = [
                'value' => $object->$pk,
                'type' => $this->getPropertyType($pk)
            ];
        }
        return $this->setPrimaryKeyFilter($qry, $primaryValues, false);
    }

    /**
     * Delete a row based on an instance (object)
     * @param Data $instance
     */
    public function deleteInstance(Data $instance)
    {
        $primaryValues = [];
        foreach ($this->getPrimaryKeys() as $primaryKey) {
            $primaryValues[$primaryKey] = $instance->$primaryKey;
        }
        $this->delete($primaryValues);
    }

    /**
     * Give a summary for the given list of Data objects
     * @param array $data
     * @param string $context
     * @return string
     */
    public function getSummary(array $data, string $context): string
    {
        return '';
    }

    public function getCustomActions(Data $instance = null): array
    {
        $actions = [];
        return $actions;
    }

    /**
     * Handle advanced settings for the query in get() or getList() such as
     * additional join and fields
     * @param SqlGenerator $qry Query object to modify
     * @param bool $isCount whether this is a count query
     * @return SqlGenerator          Query object modified
     */
    protected function settingsForGetAndList(SqlGenerator $qry, bool $isCount): SqlGenerator
    {
        // joint on foreign key tables
        foreach ($this->getForeignKeys() as $foreignKey => $infos) {
            $fields = [];
            $foreignManager = $this->app->manager($infos['class']);
            foreach ($infos['fields'] as $name => $fieldAlias) {
                $fields[$fieldAlias] = $foreignManager->getTableAlias() . '.' . $name;
            }

            $qry->leftJoin(
                $foreignManager->getTableName(),
                $foreignManager->getTableAlias(),
                $foreignManager->getTableAlias() . '.' . $infos['key'] . ' = ' . $this->tableAlias . '.' . $foreignKey,
                $isCount ? [] : $fields
            );
        }
        return $qry;
    }

    /**
     * @return string database alias used for corresponding table in queries
     */
    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    /**
     * @return string database table name corresponding to this manager
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param object $values
     * @param bool $isUpdate
     * @return object
     */
    protected function prepareDataForSet(object $values, bool $isUpdate = false): object
    {
        $values = parent::prepareDataForSet($values, $isUpdate);

        if ($isUpdate) {
            $fields = $this->getUpdateProperties();
        } else {
            $fields = $this->getInsertProperties();
        }
        foreach ($fields as $field) {
            if ($this->isRequired($field)) {
                //TODO:
            } elseif ($values->$field == '') {
                $values->$field = null;
            }
        }

        return $values;
    }

    /**
     * List properties used during an update
     * @return array properties list
     */
    public function getUpdateProperties(): array
    {
        return $this->getPropertiesHaving('update');
    }

    /**
     * List properties used during an insert
     * @return array properties list
     */
    public function getInsertProperties(): array
    {
        return $this->getPropertiesHaving('insert');
    }

    public function isRequired($property): bool
    {
        return in_array($property, $this->getPropertiesHaving('required'));
    }
}
