<?php

namespace Watamelo\Data;

use ReflectionClass;
use ReflectionException;

class Data
{

    /**
     * Default identifier getter
     * @return int|null
     */
    public function getId(): ?int
    {
        $property = 'id';
        if (property_exists($this, $property)) {
            return $this->{$property};
        }
        return null;
    }

    /**
     * Title of this specific instance used for display
     * @return string
     */
    public function getTitle(): string
    {
        $title = strtolower(self::getClassName());
        if ($this->issetId()) {
            $title .= ' #' . $this->{$property};
        }
        return $title;
    }

    /**
     * Get class child name (without namespace)
     * @return string
     */
    public static function getClassName(): string
    {
        try {
            return (new ReflectionClass(get_called_class()))->getShortName();
        } catch (ReflectionException $e) {
            return substr(strrchr(__CLASS__, "\\"), 1);
        }
    }

    public function issetId(): bool
    {
        $property = 'id';
        return property_exists($this, $property) && isset($this->{$property});
    }

    public function isEven(): bool
    {
        $property = 'id';
        if (property_exists($this, $property)) {
            return $this->{$property} % 2 == 0;
        }

        return false;
    }
}
