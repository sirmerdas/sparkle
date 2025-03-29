<?php

namespace Sirmerdas\Sparkle\Database\QueryBuilder;

use Exception;
use PDO;
use Sirmerdas\Sparkle\Database\{Manager, PdoManager};
use Sirmerdas\Sparkle\Enums\{ComparisonOperator, JoinType};
use Sirmerdas\Sparkle\Exceptions\SqlExecuteException;
use Sirmerdas\Sparkle\Traits\{QueryComponents, QueryValidators};

/**
 * Class Builder
 *
 * This class provides a fluent interface for building and executing SQL queries.
 *
 * @package Sirmerdas\Sparkle
 */
class Builder extends PdoManager
{
    use QueryComponents;
    use QueryValidators;

    private string $table;

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
     * @var array The conditions for the WHERE clause.
     */
    private array $wheres = [];

    /**
     * @var array The conditions for the OR WHERE clause.
     */
    private array $orWheres = [];

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
     * An array which keep new values column name.
     *
     */
    private array $insertKeys = [];

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
        parent::__construct(Manager::$connection);
        $this->limit(0);
        $this->offset(0);
        $tableAs = $as ? "AS $as" : '';
        $this->table = $table;
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
        $this->orders[] = "{$this->quoteColumn($column)} $direction";
        return $this;
    }

    /**
     * Adds a GROUP BY clause to the query.
     *
     * @param string $column The name of the column to group the results by.
     */
    public function groupBy(string $column): self
    {
        $this->groupBy = $this->quoteColumn($column);
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
        $this->setQueryValue($value);
        $this->having[] = "{$this->quoteColumn($column)} $operator ?";
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
        $this->setQueryValue($value);
        $this->wheres[] = "{$this->quoteColumn($column)} {$comparisonOperator->value} ?";
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
            $this->setQueryValue($value);
            $this->orWheres[] = "{$this->quoteColumn($column)} {$comparisonOperator->value} ?";
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
        $this->wheres[] = "{$this->quoteColumn($column)} IS NULL";
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
        $this->setQueryValue($values, true, true);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->wheres[] = "{$this->quoteColumn($column)} IN ($valuesPlaceHolder)";
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
        $this->setQueryValue($values, true, true);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->orWheres[] = "{$this->quoteColumn($column)} IN ($valuesPlaceHolder)";
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
        $this->setQueryValue($values, true, true);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->wheres[] = "{$this->quoteColumn($column)} NOT IN ($valuesPlaceHolder)";
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
        $this->setQueryValue($values, true, true);
        $valuesPlaceHolder = trim(implode(', ', array_pad([], count($values), '?')));
        $this->orWheres[] = "{$this->quoteColumn($column)} NOT IN ($valuesPlaceHolder)";
        return $this;
    }

    /**
     * Add a condition to check if a column is NOT NULL.
     *
     * @param string $column The column name.
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "{$this->quoteColumn($column)} IS NOT NULL";
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
        return $this->getAll($this->prepareAndExecuteQuery($this->select($columns)->buildSelectQuery()));
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
        $prepareStatement = $this->pdoPrepare($query);
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
     * Execute the query and retrieve the first matching row.
     *
     * @param array $columns The columns to select (default is all columns).
     * @return bool|object The first matching row as an object, or false if no rows match.
     */
    public function first(array $columns = ['*']): bool|object
    {
        return $this->getFirst($this->prepareAndExecuteQuery($this->select($columns)->limit(1)->buildSelectQuery()));
    }

    /**
     * Get the total count of records for the current query.
     *
     * @return int The number of records matching the query.
     */
    public function count(): int
    {
        $result = $this->getFirst($this->prepareAndExecuteQuery($this->select(columns: ['COUNT(*) AS count'])->buildSelectQuery()));
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
     * Inserts a new record into the database.
     *
     * @param array $data An associative array where keys are column names and values are the corresponding data to insert.
     * @return bool Returns `true` if at least one row was inserted successfully, otherwise `false`.
     */
    public function create(array $data): bool
    {
        $this->insertKeys = array_keys($data);
        $this->setQueryValue(array_values($data), false);
        return boolval($this->prepareAndExecuteQuery($this->buildInsertQuery())->rowCount() > 0);
    }


    /**
     * Execute a raw SQL insert query with optional parameters.
     *
     * @param string $query The raw SQL query string to execute.
     * @param array $params An array of parameters to bind to the query.
     * @return mixed The result of the query execution.
     */
    public function createRaw(string $query, array $params): int
    {
        $this->validateInsertQuery($query);
        $prepareStatement = $this->pdoPrepare($query);
        try {
            $prepareStatement->execute($params);
            return intval($this->pdoLastInsertId());
        } catch (Exception $e) {
            if (Manager::$fileLogger) {
                Manager::$logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            }
            throw new SqlExecuteException($e->getMessage());
        }
    }

    /**
     * Inserts a new record into the database and returns the last inserted ID.
     *
     * @param array $data An associative array where keys are column names and values are the corresponding data to insert.
     * @return int The ID of the last inserted row.
     */
    public function createGetId(array $data): int
    {
        $this->insertKeys = array_keys($data);
        $this->setQueryValue(array_values($data), false);
        $this->prepareAndExecuteQuery($this->buildInsertQuery());
        return intval($this->pdoLastInsertId());
    }

    /**
     * Updates records in the database with the provided data.
     *
     * @param array $data An associative array of column-value pairs to update.
     * @return int The number of affected rows.
     */
    public function update(array $data): int
    {
        $this->setQueryValue([...array_values($data), ...$this->getQueryValue()], false);
        return $this->prepareAndExecuteQuery($this->buildUpdateQuery($data))->rowCount();
    }

    /**
     * Execute a raw SQL update query with optional parameters.
     *
     * @param string $query The raw SQL query string to execute.
     * @param array $params An array of parameters to bind to the query.
     * @return int The result of the query execution.
     */
    public function updateRaw(string $query, array $params): int
    {
        $this->validateUpdateQuery($query);
        $prepareStatement = $this->pdoPrepare($query);
        try {
            $prepareStatement->execute($params);
            return $prepareStatement->rowCount();
        } catch (Exception $e) {
            if (Manager::$fileLogger) {
                Manager::$logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            }
            throw new SqlExecuteException($e->getMessage());
        }
    }

    /**
     * Deletes records from the database based on the provided conditions.
     *
     * @param array $delete  An array of column names to delete from the table.
     *                       Required when using joins.
     *
     * @return int The number of rows affected by the delete operation.
     *
     */
    public function delete(array $delete = []): int
    {
        $delete = array_map(fn ($item): string => $this->quoteColumn($item), $delete);
        return $this->prepareAndExecuteQuery($this->buildDeleteQuery(implode(', ', $delete)))->rowCount();
    }


    /**
     * Execute a raw SQL delete query with optional parameters.
     *
     * @param string $query The raw SQL query string to execute.
     * @param array $params An array of parameters to bind to the query.
     * @return int The result of the query execution.
     */
    public function deleteRaw(string $query, array $params): int
    {
        $this->validateDeleteQuery($query);
        $prepareStatement = $this->pdoPrepare($query);
        try {
            $prepareStatement->execute($params);
            return $prepareStatement->rowCount();
        } catch (Exception $e) {
            if (Manager::$fileLogger) {
                Manager::$logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            }
            throw new SqlExecuteException($e->getMessage());
        }
    }


    /**
     * Creates a copy of the current Builder instance.
     *
     * @return Builder A new instance of the Builder class with the same property values.
     */
    public function copy(): Builder
    {
        return clone $this;
    }

    /**
     * Set the columns to be selected in the query.
     *
     * @param array $columns The columns to select.
     */
    private function select(array $columns = ['*']): self
    {
        $columns = array_map(fn ($item): string => $this->quoteColumn($item), $columns);
        $this->selectColumns = implode(', ', $columns);
        return $this;
    }

    /**
     * Build the complete SELECT query string.
     */
    private function buildSelectQuery(): string
    {
        return sprintf(
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
    }

    /**
     * Build the complete INSERT query string.
     */
    private function buildInsertQuery(): string
    {
        return sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s);",
            $this->table,
            $this->buildInsertColumnsQuery($this->insertKeys),
            $this->buildInsertPlaceholder($this->insertKeys)
        );
    }

    /**
     * Build the complete UPDATE query string.
     */
    private function buildUpdateQuery(array $updateData): string
    {
        return sprintf(
            "UPDATE `%s` %s %s %s %s %s;",
            $this->table,
            $this->buildJoinQuery($this->joins),
            $this->buildUpdateQueryPlaceholder($updateData),
            $this->buildWhereQuery($this->wheres, $this->orWheres),
            $this->buildOrderByQuery($this->orders),
            $this->buildLimitOffsetQuery($this->limit, $this->offset),
        );
    }


    /**
     * Build the complete DELETE query string.
     */
    private function buildDeleteQuery(string $delete): string
    {
        return sprintf(
            "DELETE %s FROM %s %s %s %s %s;",
            $delete,
            $this->table,
            $this->buildJoinQuery($this->joins),
            $this->buildWhereQuery($this->wheres, $this->orWheres),
            $this->buildOrderByQuery($this->orders),
            $delete === '' || $delete === '0' ? $this->buildLimitForDeleteQuery($this->limit) : null,
        );
    }
}
