<?php

namespace Sirmerdas\Sparkle\Traits;

trait QueryComponents
{
    /**
     * Build the LIMIT and OFFSET clause for the query.
     *
     * @param int $limit The maximum number of records to return.
     * @param int $offset The starting point for records to return.
     *
     * @return string|null The LIMIT and OFFSET clause, or null if not set.
     */
    public function buildLimitOffsetQuery(int $limit = 0, int $offset = 0): ?string
    {
        if ($limit > 0 && $offset >= 0) {
            return sprintf("LIMIT %s OFFSET %s", $limit, $offset);
        }

        return null;
    }

    /**
     * Build the WHERE clause for the query.
     *
     * @param array $wheres The conditions for the WHERE clause, each condition as a string.
     * @param array $orWheres The conditions for the OR WHERE clause, each condition as a string.
     *
     * @return string|null The WHERE clause, or null if no conditions are set.
     */
    public function buildWhereQuery(array $wheres, array $orWheres): ?string
    {
        if ($wheres === []) {
            return null;
        }

        $whereRaw = implode(" AND ", $wheres);
        $orWhereQuery = $this->buildOrWhereQuery($orWheres);

        return "WHERE $whereRaw $orWhereQuery";
    }

    /**
     * Build the OR WHERE clause for the query.
     *
     * @param array $orWheres The conditions for the OR WHERE clause, each condition as a string.
     *
     * @return string|null The OR WHERE clause, or null if no conditions are set.
     */
    private function buildOrWhereQuery(array $orWheres): ?string
    {
        if ($orWheres === []) {
            return null;
        }

        $orWhereRaw = implode(" OR ", $orWheres);

        return " OR $orWhereRaw";
    }

    /**
     * Build the ORDER BY clause for the query.
     *
     * @param array $orders The sorting conditions for the ORDER BY clause.
     *
     * @return string|null The ORDER BY clause, or null if no conditions are set.
     */
    public function buildOrderByQuery(array $orders): ?string
    {
        if ($orders === []) {
            return null;
        }

        $orders = implode(',', $orders);

        return " ORDER BY $orders";
    }

    /**
     * Build the GROUP BY clause for the query.
     *
     * @param string|null $groupBy The column(s) to group by.
     *
     * @return string|null The GROUP BY clause, or null if no grouping is defined.
     */
    public function buildGroupByQuery(?string $groupBy): ?string
    {
        return $groupBy !== null ? "GROUP BY {$groupBy}" : null;
    }

    /**
     * Build the HAVING clause for the query.
     *
     * @param array $having The conditions for the HAVING clause, each condition as a string.
     *
     * @return string|null The HAVING clause, or null if no conditions are set.
     */
    public function buildHavingQuery(array $having): ?string
    {
        if ($having === []) {
            return null;
        }

        $having = implode(' AND ', $having);

        return " HAVING $having";
    }

    /**
     * Build the JOIN clause for the query.
     *
     * @param array $joins The JOIN clauses to be included in the query.
     *
     * @return string|null The JOIN clause, or null if no JOINs exist.
     */
    public function buildJoinQuery(array $joins): ?string
    {
        if ($joins === []) {
            return null;
        }

        return implode('  ', $joins);
    }

    /**
     * Builds the column names portion of an INSERT query.
     *
     * @param array $insertColumns An array of column names to be inserted.
     * @return string A formatted string of column names, e.g., "`column1`, `column2`, `column3`".
     */
    public function buildInsertColumnsQuery(array $insertColumns): string
    {
        return trim(implode(', ', array_map(fn ($item): string => "`$item`", $insertColumns)));
    }

    /**
     * Builds the placeholder string for an INSERT query.
     *
     * @param array $insertColumns An array of column names to be inserted.
     * @return string A formatted string of placeholders, e.g., "?, ?, ?" for three columns.
     */
    public function buildInsertPlaceholder(array $insertColumns): string
    {
        return trim(implode(', ', array_pad([], count($insertColumns), '?')));
    }

    /**
     * Checks if the given column name contains a reserved SQL keyword
     * that requires backticks for proper quoting.
     *
     * @param string $column The column name (or a dot-separated path to a nested column).
     *
     * @return bool Returns true if the column name contains a reserved keyword that requires backticks; false otherwise.
     */
    private function needsBacktick(string $column): bool
    {
        $reservedKeywords = ["order", "group", "select", "from", "where", "limit", "offset", "join", "inner", "outer", "left", "right", "on", "desc", "asc"];
        $columnParts = explode('.', $column);
        return array_intersect($reservedKeywords, $columnParts) !== [];
    }

    /**
     * Quotes the column name if needed by surrounding reserved SQL keywords
     * with backticks.
     *
     * @param string $column The column name (or a dot-separated path to a nested column) to be quoted.
     *
     * @return string The quoted column name with backticks around any reserved SQL keywords, or the column name unmodified if no backticks are needed.
     */
    public function quoteColumn(string $column): string
    {
        $needsBacktick = $this->needsBacktick($column);
        return implode('.', array_map(fn ($item): string => $needsBacktick ? "`$item`" : $item, explode('.', $column)));
    }

    /**
     * Builds the placeholder SQL string for an UPDATE query.
     *
     * This method generates a SQL `SET` clause with placeholders for prepared statements.
     *
     * @param array $updateColumns An associative array where keys are column names and values are new values.
     * @return string The formatted `SET` clause for an SQL UPDATE statement.
     */
    public function buildUpdateQueryPlaceholder(array $updateColumns): string
    {
        $columns = array_map(fn ($item): string => "{$this->quoteColumn($item)} = ?", array_keys($updateColumns));
        return "SET " . implode(' ,', $columns);
    }

    /**
     * Builds the LIMIT clause for a DELETE query.
     *
     * This method generates the `LIMIT` clause for the `DELETE` query if the
     * provided limit is greater than 0. If the limit is not positive, it returns
     * `null` to omit the `LIMIT` clause from the query.
     *
     * @param int $limit The maximum number of rows to delete.
     *
     * @return string|null The `LIMIT` clause for the DELETE query or `null` if no limit is applied.
     */
    public function buildLimitForDeleteQuery(int $limit): ?string
    {
        if ($limit > 0) {
            return sprintf("LIMIT %s", $limit);
        }

        return null;
    }
}
