<?php

namespace Rocksfort\WinterCleanup\entities;

use Exception;

/**
 * Abstraction of Exceptions for different integrity validation errors on an
 * Entity record.
 *
 * The inherited Exceptions should validate an Entity instance against the rules
 * of the data model and the existing records in the database for integrity
 * errors when the corresponding Entity record is to be created or updated and
 * throw self when the error occurs.
 *
 * Class EntityValidationException
 * @package Ericsson\EdaGui\entities
 * @author edavhaj
 */
abstract class EntityValidationException extends Exception
{
    /**
     * Execute the concrete implementation of the validation and throw self if
     * validation error occurs.
     *
     * @param array $entityInstance The Entity instance to be validated for
     * integrity errors
     * @param EntityRepository $repository An instance of the Repository that
     * relates to the concrete Entity
     * @throws EntityValidationException if integrity error occurs during
     * validation
     */
    public static final function validate($entityInstance, EntityRepository $repository = null)
    {
        $errorMessage = static::executeValidation($entityInstance, $repository);

        if (strlen($errorMessage)) {
            throw new static($errorMessage);
        }
    }

    /**
     * Abstraction of the implementation of the integrity validation.
     *
     * Validate an Entity instance against the rules
     * of the data model and the existing records in the database for integrity
     * errors.
     *
     * The implementations of this function should execute all the integrity
     * validations that are created for the concrete Entity as following.
     *
     * All Entities implement different validation functions by extending this
     * abstract {@class EntityValidationException} class and implementing its
     * protected static abstract function {@link executeValidation}(...). The
     * validations then can be executed by invoking their public
     * {@link validate}(...) functions.
     *
     * So this function's implementation in turn should always invoke all the
     * validation functions of all of its inheritances in a sequence.
     * This means if you extend this class or one of its descendants, make sure
     * that the new class is called for its {@function validate}(...) function
     * in its direct parent's {@function validate}(...) function!
     *
     * @param array $entityInstance The Entity instance to be validated for
     * integrity errors
     * @param EntityRepository $repository An instance of the Repository
     * corresponding to the concrete Entity
     * @return string $errorMessage The description of the integrity error if
     * occurs, empty string otherwise
     */
    protected static abstract function executeValidation($entityInstance, EntityRepository $repository = null);
}
