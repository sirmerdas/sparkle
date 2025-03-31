<?php

namespace Sirmerdas\Sparkle\Model;

use Sirmerdas\Sparkle\Database\QueryBuilder\{Builder, QueryResult};
use Sirmerdas\Sparkle\Enums\{ComparisonOperator, JoinType};

abstract class Model
{
    protected string $table;

    protected string $primaryKey = 'id';

    private static string $primary_key;

    private static Builder $builder;

    final public function __construct()
    {
        self::$builder = new Builder($this->table);
        self::$primary_key = $this->primaryKey;
    }

    public static function __callStatic($method, $args)
    {
        new static();
        return self::$builder->$method(...$args);
    }

    /**
     * Retrieve a single record by its primary key.
     *
     * @param int   $id       The primary key value of the record to retrieve.
     * @param array $columns  The columns to select. Defaults to all columns ['*'].
     *
     * @return bool|object Returns the found record as an object, or false if no record is found.
     */
    public static function find(int $id, array $columns = ['*']): bool|object
    {
        new static();
        return self::$builder->where(static::$primary_key, ComparisonOperator::NOT_EQUAL, $id)->first($columns);
    }

    /**
     * Set the LIMIT clause for the query.
     *
     * @param int $limit The maximum number of rows to return.
     */
    public static function limit(int $limit): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }


    /**
     * Set the OFFSET clause for the query.
     *
     * @param int $offset The number of rows to skip.
     */
    public static function offset(int $offset): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param string $column The name of the column to sort by.
     * @param string $order  The sorting direction, either 'asc' for ascending or 'desc' for descending. Defaults to 'asc'.
     */
    public static function orderBy(string $column, string $direction = 'asc'): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Adds a GROUP BY clause to the query.
     *
     * @param string $column The name of the column to group the results by.
     */
    public static function groupBy(string $column): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Add a "HAVING" clause to the query.
     *
     * @param string $column   The name of the column to apply the condition on.
     * @param string $operator The comparison operator (e.g., '=', '>', '<', etc.).
     * @param string $value    The value to compare the column against.
     */
    public static function having(string $column, string $operator, string $value): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * @param string $column The name of the column to apply the condition on.
     * @param ComparisonOperator $comparisonOperator The operator to use for comparison (e.g., '=', '>', '<').
     * @param string $value The value to compare the column against.
     */
    public static function where(string $column, ComparisonOperator $comparisonOperator, string $value): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Add a condition to check if a column is NULL.
     *
     * @param string $column The column name.
     */
    public static function whereNull(string $column): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Adds a "WHERE IN" clause to the query.
     *
     * @param string $column The name of the column to apply the "WHERE IN" condition on.
     * @param array $values An array of values to match against the column.
     */
    public static function whereIn(string $column, array $values): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Adds an "WHERE NOT IN" clause to the query.
     *
     * @param string $column The name of the column to apply the condition to.
     * @param array $values An array of values to exclude from the column.
     */
    public static function whereNotIn(string $column, array $values): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Add a condition to check if a column is NOT NULL.
     *
     * @param string $column The column name.
     */
    public static function whereNotNull(string $column): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }


    /**
     * Adds a join clause to the query.
     *
     * @param string $targetTable The name of the table to join with.
     * @param string $leftColumn The column from the current table to use in the join condition.
     * @param ComparisonOperator $comparisonOperator The operator to use for comparison (e.g., '=', '>', '<').
     * @param string $rightColumn The column from the target table to use in the join condition.
     * @param JoinType $joinType The type of join to perform (e.g., INNER, LEFT, RIGHT). Defaults to INNER.
     */
    public static function join(string $targetTable, string $leftColumn, ComparisonOperator $comparisonOperator, string $rightColumn, JoinType $joinType = JoinType::INNER): Builder
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Execute the query and retrieve all matching rows.
     *
     * @param array $columns The columns to select (default is all columns).
     * @return QueryResult The result of the query.
     */
    public static function all(array $columns = ['*']): QueryResult
    {
        return static::__callStatic('get', func_get_args());
    }

    /**
     * Execute the query and retrieve the first matching row.
     *
     * @param array $columns The columns to select (default is all columns).
     * @return bool|object The first matching row as an object, or false if no rows match.
     */
    public static function first(array $columns = ['*']): bool|object
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Get the total count of records for the current query.
     *
     * @return int The number of records matching the query.
     */
    public static function count(): int
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Determine if any records exist for the current query.
     *
     * @return bool True if records exist, false otherwise.
     */
    public static function exists(): bool
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Inserts a new record into the database.
     *
     * @param array $data An associative array where keys are column names and values are the corresponding data to insert.
     * @return bool Returns `true` if at least one row was inserted successfully, otherwise `false`.
     */
    public static function create(array $data): bool
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Inserts a new record into the database and returns the last inserted ID.
     *
     * @param array $data An associative array where keys are column names and values are the corresponding data to insert.
     * @return int The ID of the last inserted row.
     */
    public static function createGetId(array $data): int
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }


    /**
     * Updates records in the database with the provided data.
     *
     * @param array $data An associative array of column-value pairs to update.
     * @return int The number of affected rows.
     */
    public static function update(array $data): int
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
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
    public static function delete(array $delete = []): int
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }


    /**
     * Executes a raw SQL query with optional parameters and returns the results.
     *
     * @param string $query The SQL query to be executed.
     * @param array $params Optional parameters to bind to the query.
     *
     * @return array Contains:
     *               - 'items' (array): The fetched results as objects.
     *               - 'rowCount' (int): The number of affected rows.
     *               - 'lastInsertId' (string|bool): The last inserted ID if applicable.
     *
     * @throws \Sirmerdas\Sparkle\Exceptions\SqlExecuteException If the query execution fails.
     */
    public static function raw(string $query, array $params = []): array
    {
        return static::__callStatic(__FUNCTION__, func_get_args());
    }

    /**
     * Executes a set of database operations within a transaction.
     *
     * @param callable $transactions A callback function that receives a copied instance of the builder
     *                               and executes database operations within the transaction.
     */
    public static function transaction(callable $transactions): void
    {
        static::__callStatic(__FUNCTION__, func_get_args());
    }
}
