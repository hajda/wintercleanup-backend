<?php

namespace Rocksfort\WinterCleanup\entities;

/**
 * Representation of an Entity (table) record in the database.
 *
 * A concrete Model class is a representation of a record of a concrete  Entity
 * (table) in the database.
 * The instance of a concrete Model class is an object that corresponds to a
 * certain record in the database.
 * The values of a record are stored as fields of the object, whereas the field
 * names and their values equal to the attribute names and the relevant values
 * respectively in the record.
 * Because these fields are dynamically declared, the concrete Model class is
 * only required to inherit this class, and any further extesion is optional.
 *
 * Class EntityModel
 * @package Rocksfort\WinterCleanup\entities
 * @author edavhaj
 */
abstract class EntityModel
{
    /**
     * The list of the attributes of the relevant database relation that make
     * the key of the relation i.e. the table key column names.
     */
    protected static $keyAttributeSet = [];
    protected static $validationClass;

    public function __construct($attributes = [])
    {
        $this->fillAttributes($attributes);
    }

    /**
     * Create an instance of the concrete Model class.
     *
     * This function will determine the Dynamic class then instantiate that
     * class, initialize the instance with the specified {@$attributes}, and
     * return it.
     * The attributes are stored in dynamically declared fields.
     *
     * @param $attributes The attribute values of the database record
     * @return EntityModel An instance of the concrete Model class
     */
    protected static function constructModel($attributes)
    {
        $instance = new static();
        $instance->fillAttributes($attributes);
        return $instance;
    }

    /**
     * Initialize the Model instance with the specified {@link attributes}.
     *
     * Store all attribute values as dynamically declared fields.
     *
     * @param array $attributes The attribute values of the database record
     */
    protected function fillAttributes($attributes = [])
    {
        forEach($attributes as $attributeName => $value) {
            $this->$attributeName = $value;
        }
    }

    public static function getKeyAttributeSet() {
        return static::$keyAttributeSet;
    }

    public static function getvalidationClass() {
        return static::$validationClass;
    }
}
