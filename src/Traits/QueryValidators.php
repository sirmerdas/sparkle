<?php

namespace Sirmerdas\Sparkle\Traits;

use Exception;

trait QueryValidators
{
    /**
     * Validates the given SQL SELECT query string.
     *
     * This method ensures that the provided query string adheres to the
     * expected structure and syntax for a SELECT statement. If the query
     * is invalid, an exception may be thrown or appropriate handling
     * will occur.
     *
     * @param string $query The SQL SELECT query string to validate.
     */
    private function validateSelectQuery(string $query): void
    {
        $query = strtoupper($query);
        if (str_contains($query, 'INSERT') || str_contains($query, 'UPDATE')) {
            throw new Exception('Entered query is not valid select query.');
        }
    }
}
