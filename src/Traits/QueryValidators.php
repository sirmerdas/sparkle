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

    /**
     * Validates the given SQL INSERT query string.
     *
     * This method ensures that the provided query string adheres to the
     * expected structure and syntax for a INSERT statement. If the query
     * is invalid, an exception may be thrown or appropriate handling
     * will occur.
     *
     * @param string $query The SQL INSERT query string to validate.
     */
    private function validateInsertQuery(string $query): void
    {
        $query = strtoupper($query);
        if (str_contains($query, 'DELETE') || str_contains($query, 'SELECT')) {
            throw new Exception('Entered query is not valid INSERT query.');
        }
    }

    /**
     * Validates the given SQL DELETE query string.
     *
     * This method ensures that the provided query string adheres to the
     * expected structure and syntax for a DELETE statement. If the query
     * is invalid, an exception may be thrown or appropriate handling
     * will occur.
     *
     * @param string $query The SQL DELETE query string to validate.
     */
    private function validateDeleteQuery(string $query): void
    {
        $query = strtoupper($query);
        if (str_contains($query, 'INSERT') || str_contains($query, 'SELECT')) {
            throw new Exception('Entered query is not valid DELETE query.');
        }
    }

    /**
     * Validates the given SQL UPDATE query string.
     *
     * This method ensures that the provided query string adheres to the
     * expected structure and syntax for a UPDATE statement. If the query
     * is invalid, an exception may be thrown or appropriate handling
     * will occur.
     *
     * @param string $query The SQL UPDATE query string to validate.
     */
    private function validateUpdateQuery(string $query): void
    {
        $query = strtoupper($query);
        if (str_contains($query, 'INSERT') || str_contains($query, 'SELECT')  || str_contains($query, 'DELETE')) {
            throw new Exception('Entered query is not valid UPDATE query.');
        }
    }
}
