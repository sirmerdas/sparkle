<?php

namespace Sirmerdas\Sparkle\Database\QueryBuilder;

use Exception;
use PDO;
use PDOStatement;
use Sirmerdas\Sparkle\Database\Manager;
use Sirmerdas\Sparkle\Enums\ComparisonOperator;
use Sirmerdas\Sparkle\Enums\JoinType;
use Sirmerdas\Sparkle\Exceptions\SqlExecuteException;
use Sirmerdas\Sparkle\Traits\{QueryComponents,QueryValidators};

/**
 * Class Builder
 *
 * This class provides a fluent interface for building and executing SQL queries.
 *
 * @package Sirmerdas\Sparkle
 */
class Builder
{
    use QueryComponents;
    use QueryValidators;

    /**
     * @var string The columns to be selected in the query.
     */
    private string $selectColumns;

    /**
     * @var string The table name and optional alias for the query.
     */
    private string $from;

    /**
     * @var int The maximum number of rows to return.
     */
    private int $limit;

    /**
     * @var int The number of rows to skip before starting to return rows.
     */
    private int $offset;

    /**
     * @var string The complete SQL query string.
     */
    private string $query;

    /**
     * @var PDO The PDO connection instance.
     */
    private PDO $pdo;

    /**
     * @var array The conditions for the WHERE clause.
     */
    private array $wheres = [];

    /**
     * @var array The conditions for the OR WHERE clause.
     */
    private array $orWheres = [];

    /**
     * @var array The values to bind to the query placeholders.
     */
    private array $queryValue = [];

    /**
     * @var array An array to store the order clauses for the query.
     */
    private array $orders = [];

    /**
     * @var string The column or expression used to group the query results.
     */
    private ?string $groupBy = null;

    /**
     * An array to store the conditions for the HAVING clause in a database query.
     */
    private array $having = [];

    /**
     * An array that stores the join clauses for the query.
     *
     * Each element in the array represents a join clause, which can be used
     * to combine rows from two or more tables based on a related column.
     */
    private array $joins = [];

    /**
     * Builder constructor.
     *
     * Initializes the query builder with the specified table and optional alias.
     *
     * @param string $table The name of the table.
     * @param string|null $as An optional alias for the table.
     */
    public function __construct(string $table, string|null $as = null)
    {
        $this->pdo = Manager::$connection;
        $this->limit(0);
        $this->offset(0);
        $tableAs = $as ? "AS $as" : '';
        $this->from = "FROM $table $tableAs";
    }

    /**
     * Set the LIMIT clause for the query.
     *
     * @param int $limit The maximum number of rows to return.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the OFFSET clause for the query.
     *
     * @param int $offset The number of rows to skip.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param string $column The name of the column to sort by.
     * @param string $order  The sorting direction, either 'asc' for ascending or 'desc' for descending. Defaults to 'asc'.
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = "`$column` $direction";
        return $this;
    }

    /**
     * Adds a GROUP BY clause to the query.
     *
     * @param string $column The name of the column to group the results by.
     */
    public function groupBy(string $column): self
    {
        $this->groupBy = $column;
        return $this;
    }

    /**
     * Add a "HAVING" clause to the query.
     *
     * @param string $column   The name of the column to apply the condition on.
     * @param string $operator The comparison operator (e.g., '=', '>', '<', etc.).
     * @param string $value    The value to compare the column against.
     */
    public function having(string $column, string $operator, string $value): self
    {
        $this->queryValue[] = $value;
        $this->having[] = "`$column` $operator ?";
        return $this;
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * This method allows you to specify a condition for filtering results
     * based on a column, a comparison operator, and a value.
     *
     * @param string $column The name of the column to apply the condition on.
     * @param ComparisonOperator $comparisonOperator The operator to use for comparison (e.g., '=', '>', '<').
     * @param string $value The value to compare the column against.
     *
     * @return self Returns the current instance of the query builder for method chaining.
     */
    public function where(string $column, ComparisonOperator $comparisonOperator, string $value): self
    {
        $this->queryValue[] = $value;
        $this->wheres[] = "`$column` {$comparisonOperator->value} ?";
        return $this;
    }

    /**
     * Adds an "OR WHERE" condition to the query.
     *
     * This method appends a condition to the query using the logical "OR" operator.
     * It allows specifying a column, a comparison operator, and a value to filter the results.
     *
     * @param string $column The name of the column to apply the condition on.
     * @param ComparisonOperator $comparisonOperator The comparison operator to use (e.g., '=', '!=', '<', '>').
     * @param string $value The value to compare the column against.
     *
     * @return self Returns the current instance of the query builder for method chaining.
     */
    public function orWhere(string $column, ComparisonOperator $comparisonOperator, string $value): self
    {
        if ($this->wheres !== []) {
            $this->queryValue[] = $value;
            $this->orWheres[] = "`$column` {$comparisonOperator->value} ?";
        }
        return $this;
    }

    /**
     * Add a condition to check if a column is NULL.
     *
     * @param string $column The column name.
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    /**
     * Adds a "WHERE IN" clause to the query.
     *
     * @param string $column The name of the column to apply the "WHERE IN" condition on.
     * @param array $values An array of values to match against the column.
     */
    public function whereIn(string $column, array $values): self
    {
        array_push($this->queryValue, ...$values);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->wheres[] = "`$column` IN ($valuesPlaceHolder)";
        return $this;
    }

    /**
     * Adds a "OR WHERE IN" clause to the query.
     *
     * @param string $column The name of the column to apply the "WHERE IN" condition on.
     * @param array $values An array of values to match against the column.
     */
    public function orWhereIn(string $column, array $values): self
    {
        array_push($this->queryValue, ...$values);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->orWheres[] = "`$column` IN ($valuesPlaceHolder)";
        return $this;
    }

    /**
     * Adds an "WHERE NOT IN" clause to the query.
     *
     * This method appends a condition to the query that checks if the values
     * in the specified column are not within the given array of values, using
     * an "OR" logical operator.
     *
     * @param string $column The name of the column to apply the condition to.
     * @param array $values An array of values to exclude from the column.
     */
    public function whereNotIn(string $column, array $values): self
    {
        array_push($this->queryValue, ...$values);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->wheres[] = "`$column` NOT IN ($valuesPlaceHolder)";
        return $this;
    }

    /**
     * Adds an "OR WHERE NOT IN" clause to the query.
     *
     * This method appends a condition to the query that checks if the values
     * in the specified column are not within the given array of values, using
     * an "OR" logical operator.
     *
     * @param string $column The name of the column to apply the condition to.
     * @param array $values An array of values to exclude from the column.
     */
    public function orWhereNotIn(string $column, array $values): self
    {
        array_push($this->queryValue, ...$values);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->orWheres[] = "`$column` NOT IN ($valuesPlaceHolder)";
        return $this;
    }

    /**
     * Add a condition to check if a column is NOT NULL.
     *
     * @param string $column The column name.
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "$column IS NOT NULL";
        return $this;
    }

    /**
     * Adds a join clause to the query.
     *
     * @param string $targetTable The name of the table to join with.
     * @param string $leftColumn The column from the current table to use in the join condition.
     * @param string $operator The operator to use in the join condition (e.g., '=', '<', '>').
     * @param string $rightColumn The column from the target table to use in the join condition.
     * @param JoinType $joinType The type of join to perform (e.g., INNER, LEFT, RIGHT). Defaults to INNER.
     */
    public function join(string $targetTable, string $leftColumn, string $operator, string $rightColumn, JoinType $joinType = JoinType::INNER): self
    {
        $this->joins[] = "{$joinType->value} JOIN `$targetTable` ON $leftColumn $operator $rightColumn";
        return $this;
    }

    /**
     * Execute the query and retrieve all matching rows.
     *
     * @param array $columns The columns to select (default is all columns).
     * @return QueryResult The result of the query.
     */
    public function get(array $columns = ['*']): QueryResult
    {
        $this->select($columns)->buildSelectQuery();
        return $this->getAll($this->prepareQuery());
    }

    /**
     * Execute the query and retrieve the first matching row.
     *
     * @param array $columns The columns to select (default is all columns).
     * @return bool|object The first matching row as an object, or false if no rows match.
     */
    public function first(array $columns = ['*']): bool|object
    {
        $this->select($columns)->limit(1);
        $this->buildSelectQuery();
        return $this->getFirst($this->prepareQuery());
    }

    /**
     * Get the total count of records for the current query.
     *
     * @return int The number of records matching the query.
     */
    public function count(): int
    {
        $this->select(columns: ['COUNT(*) AS count'])->buildSelectQuery();
        $result = $this->getFirst($this->prepareQuery());
        return intval($result?->count ?? 0);
    }

    /**
     * Determine if any records exist for the current query.
     *
     * @return bool True if records exist, false otherwise.
     */
    public function exists(): bool
    {
        return boolval($this->count() > 0);
    }

    /**
     * Execute a raw SQL select query with optional parameters.
     *
     * @param string $query The raw SQL query string to execute.
     * @param array $params An optional array of parameters to bind to the query.
     *                       Default is an empty array.
     * @return mixed The result of the query execution, typically an array of results.
     */
    public function selectRaw(string $query, array $params = []): array
    {
        $this->validateSelectQuery($query);
        $prepareStatement = $this->pdo->prepare($query);
        try {
            $prepareStatement->execute($params);
            return $prepareStatement->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            if (Manager::$fileLogger) {
                Manager::$logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            }
            throw new SqlExecuteException($e->getMessage());
        }
    }

    /**
     * Set the columns to be selected in the query.
     *
     * @param array $columns The columns to select.
     */
    private function select(array $columns = ['*']): self
    {
        $this->selectColumns = implode(', ', $columns);
        return $this;
    }

    /**
     * Build the complete SELECT query string.
     */
    private function buildSelectQuery(): void
    {
        $query = sprintf(
            "SELECT %s %s %s %s %s %s %s %s;",
            $this->selectColumns,
            $this->from,
            $this->buildJoinQuery($this->joins),
            $this->buildWhereQuery($this->wheres, $this->orWheres),
            $this->buildGroupByQuery($this->groupBy),
            $this->buildHavingQuery($this->having),
            $this->buildOrderByQuery($this->orders),
            $this->buildLimitOffsetQuery($this->limit, $this->offset),
        );

        $this->query = $query;
    }

    /**
     * Prepare the SQL query for execution.
     *
     * @return PDOStatement The prepared statement.
     */
    private function prepareQuery(): PDOStatement
    {
        return $this->execute($this->pdo->prepare($this->query));
    }

    /**
     * Execute the prepared statement with the bound values.
     *
     * @param PDOStatement $pdoStatement The prepared statement.
     * @return PDOStatement The executed statement.
     * @throws SqlExecuteException If the query execution fails.
     */
    private function execute(PDOStatement $pdoStatement): PDOStatement
    {
        try {
            $pdoStatement->execute($this->queryValue);
            return $pdoStatement;
        } catch (Exception $e) {
            if (Manager::$fileLogger) {
                Manager::$logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            }
            throw new SqlExecuteException($e->getMessage());
        }
    }

    /**
     * Retrieve the first row from the executed statement.
     *
     * @param PDOStatement $pdoStatement The executed statement.
     * @return object|false The first row as an object, or false if no rows exist.
     */
    private function getFirst(PDOStatement $pdoStatement): \stdClass|false
    {
        return $pdoStatement->fetchObject();
    }

    /**
     * Retrieve all rows from the executed statement.
     *
     * @param PDOStatement $pdoStatement The executed statement.
     * @return QueryResult The result of the query.
     */
    private function getAll(PDOStatement $pdoStatement): \Sirmerdas\Sparkle\Database\QueryBuilder\QueryResult
    {
        return $this->formatResult($pdoStatement->fetchAll(PDO::FETCH_OBJ), $pdoStatement->rowCount());
    }

    /**
     * Format the query result into a QueryResult object.
     *
     * @param array $queryResult The fetched rows as an array of objects.
     * @param int $rowCount The number of rows returned by the query.
     * @return QueryResult The formatted query result.
     */
    private function formatResult(array $queryResult, int $rowCount): QueryResult
    {
        return new QueryResult($rowCount ?? 0, $queryResult ?? []);
    }

}
