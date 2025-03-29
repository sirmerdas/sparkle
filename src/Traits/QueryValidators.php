<?php

// This approach is not very safe and it's temporary until I find better approach.

namespace Sirmerdas\Sparkle\Traits;

use Exception;

trait QueryValidators
{
    protected function validateSelectQuery(string $query): void
    {
        $this->validateQuery($query, 'SELECT');
    }

    protected function validateInsertQuery(string $query): void
    {
        $this->validateQuery($query, 'INSERT');
    }

    protected function validateDeleteQuery(string $query): void
    {
        $this->validateQuery($query, 'DELETE');
    }

    protected function validateUpdateQuery(string $query): void
    {
        $this->validateQuery($query, 'UPDATE');
    }

    /**
     * Validates that the given SQL query starts with the correct type and does not contain other operations.
     *
     * @param string $query The SQL query to validate.
     * @param string $currentType The expected query type (SELECT, INSERT, DELETE, UPDATE).
     *
     */
    private function validateQuery(string $query, string $currentType): void
    {
        $operations = "INSERT DELETE SELECT UPDATE";
        $operations = preg_replace('/\s+/', ' ', trim(str_replace($currentType, '', $operations)));
        $query = strtoupper($query);

        $operations = explode(' ', $operations);

        if (strpos($query, $currentType) !== 0) {
            throw new Exception("Entered query is not valid $currentType query.");
        }

        foreach ($operations as $operation) {
            if (str_contains($query, $operation)) {
                throw new Exception("Entered query is not valid $currentType query.");
            }
        }

    }

}
