<?php

namespace Rocksfort\WinterCleanup\entities;

use PDO, PDOStatement;
use Rocksfort\WinterCleanup\services\QueryLimiter;

/**
 * Abstract CRUD repository for entities.
 *
 * The abstraction of the implementation of CRUD methods in the database for
 * entities.
 * The abstract Repository knows how to reach out to the database when issuing
 * basic SELECT, CREATE/UPDATE and DELETE statements. The concrete Repository
 * provides the exact SQL statements.
 * To create a concrete repository, inherit this abstract class and call its
 * constructor, providing three basic SQL statements for the CRUD operations
 * (SELECT, CREATE/UPDATE and DELETE).
 *
 * Class EntityRepository
 * @package Rocksfort\WinterCleanup\entities
 * @author edavhaj
 */
abstract class EntityRepository
{
    protected $fetchClass;
    protected $selectStatement;
    protected $saveStatement;
    protected $deleteStatement;

    /**
     * EntityRepository constructor.
     *
     * The Repository expect three basic SQL statements to be defined on the
     * Model, and has respective fields to prepare those statements.
     * If the SQL is missing, the statement will not be prepared.
     *
     * @param PDO $databaseInstance The database instance (from the CI
     * container)
     * @param class $modelClass The class that represents a record of the entity
     * that the concrete Repository handles. The actual argument can be the
     * EntityModel::class, or a class that extends the EntityModel class
     * (e.g. MyModel::class)
     */
    public function __construct(PDO $databaseInstance, $modelClass)
    {
        $this->fetchClass = $modelClass;

        $this->selectStatement = defined("$modelClass::SELECT_SQL") ? $databaseInstance->prepare($modelClass::SELECT_SQL) : null;
        $this->saveStatement =   defined("$modelClass::SAVE_SQL"  ) ? $databaseInstance->prepare($modelClass::SAVE_SQL  ) : null;
        $this->deleteStatement = defined("$modelClass::DELETE_SQL") ? $databaseInstance->prepare($modelClass::DELETE_SQL) : null;
    }

    /* API */

    /* API GET */

    /**
     * Execute the basic select statement with the captured URL path segments as
     * parameters and retrieve Entity records.
     *
     * @param EntityModel|array $entityInstance The key attribute values to the entity from the URL path
     * @param array $query optional query parameters from the URL query parameters.
     * @return array[EntityModel] The retrieved Entity records
     */
    public function get($entityInstance, $query = [])
    {
        $this->window($this->selectStatement, $query);
        return $this->dispatchGet($this->selectStatement, $entityInstance, $query);
    }

    /* API SAVE */

    /**
     * Substitute statement parameters and execute the {@link $saveStatement}.
     *
     * @param EntityModel|array $entityInstance - array/object representing the Entity
     * @return int The number of affected records
     */
    public function save($entityInstance)
    {
        return $this->executeStatement($this->saveStatement, $entityInstance)->rowCount();
    }

    /* API DELETE */

    /**
     * Substitute statement parameters and execute the {@link $deleteStatement}.
     *
     * @param EntityModel|array $instanceentityInstance - array/object representing the Entity
     * @return int the number of affected records
     */
    public function delete($instanceentityInstance)
    {
        return $this->executeStatement($this->deleteStatement, $instanceentityInstance)->rowCount();
    }

    /* Implementation details */

    /* Implementation details GET */

    /**
     * Substitute statement parameters and execute the {@link $statement}.
     *
     * @param PDOStatement $statement The statement to execute
     * @param $instance The key attribute values to the entity from the URL path
     * @param $query optional query parameters from the URL query parameters
     * @return array[EntityModel] The retrieved Entity records
     */
    protected function dispatchGet(PDOStatement $statement, $instance, $query)
    {
        $this->executeStatement($statement, $instance, $query);
        return $statement->fetchAll(PDO::FETCH_CLASS, $this->fetchClass);
    }

    /**
     * Substitue the 'limit' and 'offset' statement parameters from the URL
     * {@link query}.
     *
     * @param PDOStatement $statement The statement in which to substitue
     * @param array $query The URL query parameters
     * @sideEffect The 'limit' and 'offset' parameters are removed from the query
     */
    protected function window(PDOStatement $statement, &$query = []) {
        QueryLimiter::fixLimits($query);

        $statement->bindParam(':limit', $query['limit'], PDO::PARAM_INT);
        $statement->bindParam(':offset', $query['offset'], PDO::PARAM_INT);

        unset($query['limit']);
        unset($query['offset']);
    }

    /* Implementation details GET */

    /**
     * Substitute statement parameters specified by {@link $attributeValues} into and execute the prepared {@link $statement}.
     *
     * @param PDOStatement $statement the prepared statement that is to execute
     * @param array $attributeValues the bind variables to substitute into the {@link $statement} template
     * @param $query The URL query parameters
     * @return PDOStatement $statement the executed {@link $statement}
     */
    protected function executeStatement(PDOStatement $statement, $attributeValues = [], $query = [])
    {
        foreach ((array) $attributeValues as $attribute => $value) {
            $statement->bindParam(":$attribute", $attributeValues[$attribute]); // cannot use $value for some reason.
        }
        foreach ($query as $parameter => $value) {
            $statement->bindParam(":$parameter", $query[$parameter]); // cannot use $value for some reason.
        }

        $statement->execute();

        return $statement;
    }
}
