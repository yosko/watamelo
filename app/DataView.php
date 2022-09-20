<?php

namespace Watamelo\App;

use LogicException;
use Watamelo\Data\Data;
use Watamelo\Lib\ApplicationComponent;
use Watamelo\Managers\DataManager;
use Watamelo\Utils\ViewFormatter;

class DataView extends ApplicationComponent
{

    /**
     * DataManager for the given Data class
     * @param Data $object
     * @return DataManager
     */
    protected function getManager(Data $object): DataManager
    {
        $manager = $this->app->manager($object::getClassName());
        if (!($manager instanceof DataManager)) {
            throw new LogicException('Manager for "' . $object::getClassName() . '" can\'t be used by DataView');
        }
        return $manager;
    }

    /**
     * Is the given property a defined field coming from DB
     * @param Data $object
     * @param string $field
     * @return bool
     */
    public function isPropertyDefined(Data $object, string $field): bool
    {
        return $this->getManager($object)->isPropertyDefined($field);
    }

    /**
     * indicates type (DataManager::TYPE_*) for given field
     * @param Data $object
     * @param string $field
     * @return int
     */
    public function getPropertyType(Data $object, string $field): int
    {
        return $this->getManager($object)->getPropertyType($field);
    }

    /**
     * Determines if instances pre-existed (coming from DB)
     * @param Data $object
     * @return bool
     */
    public function instanceExists(Data $object): bool
    {
        foreach ($this->getManager($object)->getPrimaryKeys() as $pk) {
            if (empty($object->$pk)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get hidden fields of an object to include un a form
     * @param Data $object
     * @return array
     */
    public function getHidden(Data $object): array
    {
        return $this->getManager($object)->getNonForeignPrimaryKeys();
    }

    /**
     * Determine if a field should be displayed or not (and if it should be an <input type="hidden"> in forms
     * @param Data $object
     * @param string $field
     * @return bool
     */
    public function isHidden(Data $object, string $field): bool
    {
        return in_array($field, $this->getHidden($object));
    }

    public function isPropertyWritable(Data $object, string $field): bool
    {
        $manager = $this->getManager($object);
        if ($manager->isPropertyDefined('id') && empty($object->id)) {
            return in_array($field, $manager->getInsertProperties());
        } else {
            return in_array($field, $manager->getUpdateProperties());
        }
    }

    public function isPropertyReadable(Data $object, string $field): bool
    {
        return $this->getManager($object)->isReadable($field);
    }

    public function isPropertySecondary(Data $object, string $field): bool
    {
        return $this->getManager($object)->isPropertySecondary($field);
    }

    /**
     * Formats data based on type (used for date formats, currencies, etc.)
     * @param Data $object
     * @param string $property property name
     * @return string
     */
    public function formated(Data $object, string $property): string
    {
        if (is_null($object->$property)) {
            return '';
        }

        $manager = $this->getManager($object);
        switch ($this->getPropertyType($object, $property)) {
            case $manager::TYPE_DATE:
                $value = ViewFormatter::formatDate($object->$property);
                break;
            case $manager::TYPE_DATETIME:
                $value = ViewFormatter::formatDateTime($object->$property);
                break;
            case $manager::TYPE_MONEY:
                $value = ViewFormatter::formatCurrency($object->$property);
                break;
            case $manager::TYPE_BOOL:
                $value = $object->$property ? 'Oui' : 'Non';
                break;
            default:
                $value = $object->$property;
        }
        return $value;
    }

    public function getForegroundColor(Data $object, string $property): string
    {
        return $this->getManager($object)->getForegroundColor($object, $property);
    }

    public function getBackgroundColor(Data $object, string $property): string
    {
        return $this->getManager($object)->getBackgroundColor($object, $property);
    }

    /**
     * Give a summary for the given list of Data objects
     * @param array $data
     * @param string $context class name for context (some collection are displayed in other Data types context)
     * @return string
     */
    public function getSummary(array $data, string $context): string
    {
        if (empty($data)) {
            return '';
        }

        $firstInstance = reset($data);
        $manager = $this->getManager($firstInstance);
        return $manager->getSummary($data, $context);
    }

    /**
     * Returns a link to corresponding resource (if there is one)
     * @param Data $object
     * @param string $property
     * @return string
     */
    public function getHyperlink(Data $object, string $property): string
    {
        $manager = $this->getManager($object);
        if ($manager->isForeignData($property)) {
            $info = $manager->getForeignData($property);
            $fManager = $this->app->manager($info['class']);
            $keys = $fManager->getPrimaryKeys();
            if (count($keys) == 1) {
                $key = reset($keys);
                $key .= $info['class'];
                if (isset($object->$key)) {
                    return $this->app()->view()->buildRoute('data/%s/%d', $info['class'], $object->$key);
                }
            }
        }
        return '';
    }

    public function modelRoute(string $model, string $additional = ''): string
    {
        return $this->app()->view()->buildRoute('data/%s/%s', $model, $additional);
    }

    public function instanceRoute(Data $object, string $action = ''): string
    {
        $manager = $this->getManager($object);
        $idsStr = $manager->getIdString($object);
        return $this->app()->view()->buildRoute('data/%s/%s/%s', $object::getClassName(), $idsStr, $action);
    }

    /**
     * @param string $model
     * @param Data|null $instance
     * @return array
     */
    public function getCustomActions(string $model, Data $instance = null): array
    {
        $manager = $this->app->manager($model);
        return $manager->getCustomActions($instance);
    }

    public function getAvailableFilters(string $model): array
    {
        $manager = $this->app->manager($model);
        $filters = $manager->getAvailableFilters();
        foreach ($filters as $property => $filterInfo) {
            $propertyInfo = $manager->getProperty($property);

            if (isset($propertyInfo['foreignKey'])) {
                $fClass = $propertyInfo['foreignKey']['class'];
                $fManager = $this->app->manager($fClass);
                $filters[$property]['class'] = $fClass;
                $filters[$property]['data'] = $fManager->getList();
            }
        }

        return $filters;
    }

    public function getForeignKeys($model)
    {
        $manager = $this->app->manager($model);
        return $manager->getForeignKeys();
    }

    public function getInstance($model)
    {
        $manager = $this->app->manager($model);
        $class = $manager->getDataClass();
        return new $class();
    }
}