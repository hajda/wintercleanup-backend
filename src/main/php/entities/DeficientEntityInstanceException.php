<?php

namespace Rocksfort\WinterCleanup\entities;

use Exception;

/**
 * Exception for missing attributes for a prepared statement.
 *
 * {@see EntityResource}
 *
 * Class DeficientEntityInstanceException
 * @package Rocksfort\WinterCleanup\entities
 * @author edavhaj
 */
class DeficientEntityInstanceException extends Exception
{
    public static function validateKeyAttributes($entityInstance, $keyAttributeSet)
    {
        if (count($missingKeyAttributes = static::verifyKeyAttributes($entityInstance, $keyAttributeSet))) {
            $missingKeyAttributeErrorMessage = static::getMissingKeyAttributesErrorMessage($missingKeyAttributes);
            throw new static($missingKeyAttributeErrorMessage);
        }
    }

    /**
     * Determine if the Entity record specifies the key attributes to
     * this Entity that are mandatory for Requests that can have side effect
     * (POST, DELETE).
     *
     * @param array $entityInstance The Entity record
     * @param array $keyAttributeSet the names of the mandatory key attributes
     * @return array[string] Results the list of the missing key attributes
     */
    private static function verifyKeyAttributes($entityInstance, $keyAttributeSet)
    {
        $missingKeyAttributes = [];

        foreach ($keyAttributeSet as $keyAttribute) {
            if (!isset($entityInstance[$keyAttribute]) || $entityInstance[$keyAttribute] == null) {
                array_push($missingKeyAttributes, $keyAttribute);
                $result = false;
            }
        }

        return $missingKeyAttributes;
    }

    /**
     * Generate error message from a list of attribute names.
     *
     * @param a list of attribute names array $missingKeyAttributes
     * @return string the error message
     */
    private static function getMissingKeyAttributesErrorMessage($missingKeyAttributes) {
        $s = '';
        $substantiveVerb = 'is';

        $keyAttributeSet = $missingKeyAttributes[0];

        for ($i = 1; $i < count($missingKeyAttributes); $i++) {
            $keyAttributeSet .= ", {$missingKeyAttributes[$i]}";
            $s = 's';
            $substantiveVerb = 'are';
        }

        return "Key attribute$s \"$keyAttributeSet\" $substantiveVerb missing from the request body";
    }
}
