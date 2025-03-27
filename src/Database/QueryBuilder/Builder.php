<?php

namespace Sirmerdas\Sparkle\Database\QueryBuilder;

use Exception;
use PDO;
use PDOStatement;
use Sirmerdas\Sparkle\Database\Manager;
use Sirmerdas\Sparkle\Enums\JoinType;
use Sirmerdas\Sparkle\Exceptions\SqlExecuteException;
use Sirmerdas\Sparkle\Exceptions\WhereOperatorException;

/**
 * Class Builder
 *
 * This class provides a fluent interface for building and executing SQL queries.
 *
 * @package Sirmerdas\Sparkle
 */
class Builder
{
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
     * List of allowed where operator
     */
    private array $allowedWhereOperators = ['=', '>', '>', '!=', '<>', '>=', '<=', 'BETWEEN', 'LIKE', 'IN', 'ALL', 'AND', 'ANY', 'EXISTS', 'NOT', 'OR', 'SOME'];

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
     * Add a condition to the WHERE clause.
     *
     * @param string $column The column name.
     * @param string $operator The comparison operator (e.g., '=', '<', '>').
     * @param string $value The value to compare against.
     */
    public function where(string $column, string $operator, string $value): self
    {
        $this->validateWhereOperator($operator);
        $this->queryValue[] = $value;
        $this->wheres[] = "`$column` $operator ?";
        return $this;
    }

    /**
     * Add a condition to the OR WHERE clause.
     *
     * @param string $column The column name.
     * @param string $operator The comparison operator (e.g., '=', '<', '>').
     * @param string $value The value to compare against.
     */
    public function orWhere(string $column, string $operator, string $value): self
    {
        if ($this->wheres !== []) {
            $this->validateWhereOperator($operator);
            $this->queryValue[] = $value;
            $this->orWheres[] = "`$column` $operator ?";
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
     * Execute a raw SQL select query with optional parameters.
     *
     * @param string $query The raw SQL query string to execute.
     * @param array $params An optional array of parameters to bind to the query.
     *                       Default is an empty array.
     * @return mixed The result of the query execution, typically an array of results.
     */
    public function selectRaw(string $query, array $params = [])
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
     * Validates the given SQL SELECT query string.
     *
     * This method ensures that the provided query string adheres to the
     * expected structure and syntax for a SELECT statement. If the query
     * is invalid, an exception may be thrown or appropriate handling
     * will occur.
     *
     * @param string $query The SQL SELECT query string to validate.
     * 
     * @return void
     */
    private function validateSelectQuery(string $query): void
    {
        $query = strtoupper($query);
        if (str_contains($query, 'INSERT') || str_contains($query, 'UPDATE')) {
            throw new Exception('Entered query is not valid select query.');
        }
    }

    /**
     * Validate of given operator is allowed or not.
     *
     */
    private function validateWhereOperator(string $operator): void
    {
        if (!in_array(trim(strtoupper($operator)), $this->allowedWhereOperators)) {
            throw new WhereOperatorException("Unsupported $operator operator!");
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
            $this->buildJoinQuery(),
            $this->buildWhereQuery(),
            $this->buildGroupByQuery(),
            $this->buildHavingQuery(),
            $this->buildOrderByQuery(),
            $this->buildLimitOffsetQuery(),
        );

        $this->query = $query;
    }

    /**
     * Build the LIMIT and OFFSET clause for the query.
     *
     * @return string|null The LIMIT and OFFSET clause, or null if not set.
     */
    private function buildLimitOffsetQuery(): string|null
    {
        if ($this->limit > 0 && $this->offset >= 0) {
            return sprintf("LIMIT %s OFFSET %s", $this->limit, $this->offset);
        }

        return null;
    }

    /**
     * Build the WHERE clause for the query.
     *
     * @return string|null The WHERE clause, or null if no conditions are set.
     */
    private function buildWhereQuery(): string|null
    {
        if ($this->wheres === []) {
            return null;
        }

        $whereRaw = implode(" AND ", $this->wheres);
        $orWhereQuery = $this->buildOrWhereQuery();

        return "WHERE $whereRaw $orWhereQuery";
    }

    /**
     * Build the OR WHERE clause for the query.
     *
     * @return string|null The OR WHERE clause, or null if no conditions are set.
     */
    private function buildOrWhereQuery(): string|null
    {
        if ($this->orWheres === []) {
            return null;
        }

        $orWhereRaw = implode(" OR ", $this->orWheres);

        return " OR $orWhereRaw";
    }

    /**
     * Builds the SQL query string for the ORDER BY clause.
     *
     * This method constructs and returns the ORDER BY portion of an SQL query
     * based on the current query builder state. If no ORDER BY conditions are
     * specified, it returns null.
     *
     * @return string|null The ORDER BY SQL query string, or null if no conditions are set.
     */
    private function buildOrderByQuery(): ?string
    {
        if ($this->orders === []) {
            return null;
        }

        $orders = implode(',', $this->orders);

        return " ORDER BY $orders";
    }

    /**
     * Builds and returns the SQL query string for the GROUP BY clause.
     *
     * @return string|null The GROUP BY query string if applicable, or null if no grouping is defined.
     */
    private function buildGroupByQuery(): ?string
    {
        return $this->groupBy !== null ? "GROUP BY {$this->groupBy}" : null;
    }

    /**
     * Builds the SQL query string for the HAVING clause.
     *
     * This method constructs and returns the HAVING clause of the SQL query
     * based on the conditions specified in the query builder. If no conditions
     * are set for the HAVING clause, it returns null.
     *
     * @return string|null The constructed HAVING clause as a string, or null if no conditions are set.
     */
    private function buildHavingQuery(): ?string
    {
        if ($this->having === []) {
            return null;
        }

        $having = implode(' AND ', $this->having);

        return " HAVING $having";
    }

    /**
     * Builds and returns the SQL query string for JOIN clauses.
     *
     * This method constructs the JOIN portion of an SQL query based on the
     * current state of the query builder. If no JOIN clauses are defined,
     * it returns null.
     *
     * @return string|null The SQL JOIN query string, or null if no JOIN clauses exist.
     */
    private function buildJoinQuery(): ?string
    {
        if ($this->joins === []) {
            return null;
        }

        return implode('  ', $this->joins);
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
