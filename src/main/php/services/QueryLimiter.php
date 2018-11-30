<?php
namespace Rocksfort\WinterCleanup\services;

/**
 * Class QueryLimiter
 * @package Rocksfort\WinterCleanup\services
 */
class QueryLimiter
{
    /**
     * Initialize 'limit' and 'offset' if null for an SQL SELECT statement.
     *
     * @sideEffect The 'limit' element is set to 999999 if it is missing or is
     *
     * @param array $query The URL query parameters
     * null. The 'offset' element is set to 0 if it is missing or is null.
     */
    public static function fixLimits(&$query)
    {
        if (!isset($query['limit']) || $query['limit'] == null) {
            $query['limit'] = 999999;
        }
        if (!isset($query['offset']) || $query['offset'] == null) {
            $query['offset'] = 0;
        }
    }
}
