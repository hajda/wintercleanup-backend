<?php

namespace Rocksfort\WinterCleanup\entities;

use \Slim\Container;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * REST API end-points for an Entity.
 *
 * entities/<entityName>
 *
 * Class EntityResource
 * @package Rocksfort\WinterCleanup\entities
 * @author edavhaj
 */
abstract class EntityResource
{
    protected $repository;
    /**
     * The primary key set of the Entity relation.
     *
     * e.g. in case of ImsiRange: ['mcc_mnc_imsi', 'imsi_s_l']
     * Required to sanitize GET requests and to validate UPDATE and DELETE
     * requests.
     *
     * TODO In PHP7 this field can be abandoned and
     * TODO "$this->modelClass::getValidationClass()" can be used in the code
     * TODO instead.
     *
     * @var
     */
    protected $keyAttributeSet;
    protected $validationClass; // In PHP7 this field can be abandoned and "$this->modelClass::getKeyAttributeSet()" can be used in the code instead
    protected $modelClass;

    protected $user;
    protected $root;
    private $ci;

    public function __construct(Container $ci, $repositoryClass, $modelClass)
    {
        $request = $ci->get('request');
        $this->root = $request->getUri()->getBasePath();

        $this->ci = $ci;
        $this->repository = new $repositoryClass($ci->get('database'), $modelClass);
        $this->keyAttributeSet = $modelClass::getKeyAttributeSet();
        $this->validationClass = $modelClass::getValidationClass();
        $this->modelClass = $modelClass;
    }

    /* API */

    /* API GET */

    /**
     * Get the list of Entity records or the specified Entity record.
     * GET end-point request handler
     *
     * @param Request $request The HTTP request object containing the following
     *     query parameters:
     *         limit: Integer The maximum number of records to retrieve
     *         offset: Integer Specifies the sequence number of the record where
     *             the list starts at
     *
     * @param Response $response The HTTP response object that will be returned
     *     Status:
     *         200 if the specified Entity record was found or if requested
     *             the whole list
     *         404 if the specified Entity record is not found
     *     Headers: not added
     *     Body:
     *         Object The specified Entity record if the {@param $args}
     *             specifies the mandatory key attributes of the Entity
     *         Array The list of all Entity records otherwise
     *
     * @param $args The values of the captured segments in the URL path.
     * The captured segments specify key attributes of the Entity.
     *
     * @return Response The HTTP {@param $response} object
     */
    public function get(Request $request, Response $response, $args)
    {
        return $this->dispatchGet($request, $response, $args, 'get');
    }

    /* API SAVE */

    /**
     * Create or modify an Entity record.
     * POST end-point request handler
     *
     * - If there is no record with the specified keys, then a new record is
     * created with the specified values (201)
     * - If a record with the specified keys exists, its values are updated by
     * the specified ones (204)
     *   If the record already has the same values as those specified, then no
     * actual update takes place, but towards the user this case is still
     * considered a successful update identically with an actual update (204)
     *
     * @param Request $request HTTP request object
     *     Body: JSON representation of the Entity record to save
     *
     * @param Response $response HTTP response object
     *     Status:
     *         201 if created
     *         204 if updated
     *     Headers:
     *         Location:
     *              URL path for the created Entity record in case of creation
     *              No header if update took place
     *         Body: none
     *
     * @param array $args Not used
     *
     * @return Response The HTTP {@param $response} object
     * @throws \ReflectionException unfortunately
     */
    public function createOrUpdateOne(Request $request, Response $response, $args)
    {
        $entityInstance = $request->getParsedBody();

        try {
            /* validate request */
            $this->validateEntityKeyAttributes($entityInstance);
            $this->validateEntityIntegrity($entityInstance); // TODO this must be done in the Repository

            /* execute requested activity */

            $affectedRowCount = $this->repository->save($entityInstance);

            /* If the record already has the specified values, it is considered a
             * successful update (in this case case rowCount is 0)
             */
            $httpStatus = $affectedRowCount == 1 ? 201 : 204;

            $response = $response->withStatus($httpStatus);
            if ($httpStatus == 201) {
                $sourceLocationUrl = $this->generateSourceLocationUrl($request, $entityInstance);
                $response = $response->withHeader('Location', $sourceLocationUrl);
            }

            // TODO DETECT if provisioning is needed and Provision IMSIs.

            return $response;
        } catch (DeficientEntityInstanceException $e) {
            return $response->withStatus(400, "Bad request. {$e->getMessage()}");
        } catch (EntityValidationException $e) {
            return $response->withStatus(400, "Bad request. {$e->getMessage()}");
        }
    }

    /* API DELETE */

    /**
     * Delete the specified Entity record.
     * DELETE end-point request handler
     *
     * @param Request $request HTTP request object
     *     Body: JSON representation of the Entity record to save
     *
     * @param Response$response the HTTP response object
     *     Status:
     *         204 if the Entity record has been deleted successfully
     *         404 if the Entity record was not found
     *     Headers: not added
     *     Body: none
     *
     * @param $args
     *     specifies the values to the key attributes of the Entity
     *
     * @return Response The HTTP {@param $response} object
     * @throws \ReflectionException
     */
    public function deleteOne(Request $request, Response $response, $args)
    {
        $entityInstance = $request->getParsedBody();

        if ($entityInstance[0] != null) { // TODO remove this ugly procedural bulk delete?
            $multiStatus = [];

            foreach ($entityInstance as $instance) {
                $individualResponse = $this->deleteOne($request->withParsedBody($instance), $response, $args);$individualResponse->getStatusCode();
                array_push($multiStatus, [
                    'status'=>$individualResponse->getStatusCode(),
                    'reasonPhrase'=>$individualResponse->getReasonPhrase(),
                    'protocolVersion'=>$individualResponse->getProtocolVersion(),
                    'headers'=>$individualResponse->getHeaders(),
                    'body'=>$individualResponse->getBody(),
                    'href' => $instance
                ]);
            }

            return $response->withJSON($multiStatus, 207);
        } else {
            try {
                $this->validateEntityKeyAttributes($entityInstance);

                $affectedRowCount = $this->repository->delete($entityInstance);

                $httpStatus = $affectedRowCount == 1 ? 204 : 404;
                $response = $response->withStatus($httpStatus);

                return $response;
            } catch (DeficientEntityInstanceException $e) {
                return $response->withStatus(400, "Bad request. {$e->getMessage()}");
            }
        }
    }

    /* Implementation details */

    /* Implementation details GET */

    /**
     * Serve the GET request with the repository function specified by
     * {@link $repositoryFunctionName}.
     *
     * This is useful when in an inherited resource wants to serve the GET
     * request with a special function of its relevant Repository (not the
     * default {@link get}() Repository function is used).
     *
     * @param Request $request TODO
     * @param Response $response TODO
     * @param $args TODO
     * @param string $repositoryFunctionName The name of the Repository function
     * to get the requested Entity resource
     * @return Response TODO
     */
    protected function dispatchGet(Request $request, Response $response, $args, $repositoryFunctionName)
    {
        $initializedKeyAttributes = $this->initializeKeyAttributes($args); // Repository requires omitted key attributes to be explicitly specified as NULL
        $resultSet = $this->repository->$repositoryFunctionName($initializedKeyAttributes, $request->getQueryParams());
        $resultSet = count($args) == count($this->keyAttributeSet) ? $resultSet[0] : $resultSet; // If all key attributes were specified, that SELECTs a single specific record

        return $resultSet ? $response->withJSON($resultSet, 200) : $response->withStatus(404);
    }

    /**
     * Initialize the key attribute values.
     *
     * The Repository requires omitted key attributes to be explicitly specified
     * as NULL.
     * Look for the key attributes (specified in the Model) in the captured URL
     * path segments and collect them into a new array. Initialize to null those
     * that are omitted.
     *
     * @param array $args The captured URL path segments
     * @sideEffect The key attributes are set to null where not set
     * @return array The initialized key attributes
     */
    protected function initializeKeyAttributes($args)
    {
        foreach ($this->keyAttributeSet as $keyAttribute) {
            $args[$keyAttribute] = isset($args[$keyAttribute]) ? $args[$keyAttribute] : null;
        }

        return $args;
    }

    /* Implementation details SAVE DELETE */

    /**
     * Acquire the Model Class and execute its {@function validate} function
     * that executes all the validations that protect the data integrity.
     *
     * @param array $entityInstance The array representation of the Entity
     * instance from the Request body
     * @throws \ReflectionException
     */
    public function validateEntityIntegrity($entityInstance)
    {
        /*
         * This code is manifested in inherited classes as -- e.g. in case of
         * ImsiRangeResource:
         *
         *      ImsiRangeValidationException::validate($entityInstance, $this->repository);
         */
        call_user_func(
            [$this->validationClass, 'validate'],
            $entityInstance,
            $this->repository
        );
    }

    /**
     * Validate the {@link $entityInstance} for containing all the mandatory
     * key attributes essential for a SELECT/DELET statement towards an Entity.
     *
     * @param array $entityInstance the Entity instance selected in a statement
     * @throws DeficientEntityInstanceException if at least one key attribute is
     * missing
     * @throws \ReflectionException
     */
    private function validateEntityKeyAttributes($entityInstance)
    {
        return DeficientEntityInstanceException::validateKeyAttributes(
            $entityInstance,
            $this->keyAttributeSet
        );
    }

    /**
     * Generate the location URL path for an Entity instance.
     *
     * @param Request $request Holds the base part of the URL path
     * @param $entityInstance Holds the identifiers to lace into the URL path
     * @return string The source location URL path
     * @throws \ReflectionException
     */
    private function generateSourceLocationUrl(Request $request, $entityInstance)
    {
        $basePath = $request->getUri()->getPath();
        $slash = substr($basePath, -1) != '/' ? '/' : '';
        $locationUrl = "{$basePath}$slash{$this->generateUrlIdArguments($entityInstance)}";
        return $locationUrl;
    }

    /**
     * Generate the identification part of the URL path for an Entity instance.
     *
     * The identification part is the list of key attribute values separated by
     * "/"
     *
     * @param $entityInstance The instance to identify
     * @return string The list of key attribute values separated by "/"
     * @throws \ReflectionException
     */
    protected function generateUrlIdArguments($entityInstance)
    {
        $urlIdArguments = '';
        foreach ($this->keyAttributeSet as $keyAttribute) {
            $urlIdArguments .= "{$entityInstance[$keyAttribute]}/";
        }

        return $urlIdArguments;
    }
}
