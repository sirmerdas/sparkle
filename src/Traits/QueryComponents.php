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
}
