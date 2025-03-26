<?php

namespace Sirmerdas\Sparkle\Database\QueryBuilder;

use Exception;
use PDO;
use PDOStatement;
use Sirmerdas\Sparkle\Database\Manager;
use Sirmerdas\Sparkle\Exceptions\SqlExecuteException;

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
    private PDO $connection;

    /**
     * @var array The conditions for the WHERE clause.
     */
    private array $wheres;

    /**
     * @var array The conditions for the OR WHERE clause.
     */
    private array $orWheres;

    /**
     * @var array The values to bind to the query placeholders.
     */
    private array $queryValue;

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
        $this->connection = Manager::$connection;
        $this->limit(0);
        $this->offset(0);
        $this->wheres = [];
        $this->orWheres = [];
        $this->queryValue = [];
        $tableAs = $as ? "AS $as" : '';
        $this->from = "FROM $table $tableAs";
    }

    /**
     * Set the LIMIT clause for the query.
     *
     * @param int $limit The maximum number of rows to return.
     * @return self
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
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Add a condition to the WHERE clause.
     *
     * @param string $column The column name.
     * @param string $operator The comparison operator (e.g., '=', '<', '>').
     * @param string $value The value to compare against.
     * @return self
     */
    public function where(string $column, string $operator, string $value): self
    {
        $this->queryValue[] = $value;
        $this->wheres[] = "`$column`$operator?";
        return $this;
    }

    /**
     * Add a condition to the OR WHERE clause.
     *
     * @param string $column The column name.
     * @param string $operator The comparison operator (e.g., '=', '<', '>').
     * @param string $value The value to compare against.
     * @return self
     */
    public function orWhere(string $column, string $operator, string $value): self
    {
        if (!empty($this->wheres)) {
            $this->queryValue[] = $value;
            $this->orWheres[] = "`$column`$operator?";
        }
        return $this;
    }

    /**
     * Add a condition to check if a column is NULL.
     *
     * @param string $column The column name.
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }


    /**
     * Add a condition to check if a column is NOT NULL.
     *
     * @param string $column The column name.
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "$column IS NOT NULL";
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
     * Set the columns to be selected in the query.
     *
     * @param array $columns The columns to select.
     * @return self
     */
    private function select(array $columns = ['*']): self
    {
        $this->selectColumns = implode(', ', $columns);
        return $this;
    }

    /**
     * Build the complete SELECT query string.
     *
     * @return void
     */
    private function buildSelectQuery()
    {
        $query = sprintf(
            "SELECT %s %s %s %s;",
            $this->selectColumns,
            $this->from,
            $this->buildWhereQuery(),
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
        if (empty($this->wheres)) {
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
        if (empty($this->orWheres)) {
            return null;
        }

        $orWhereRaw = implode(" OR ", $this->orWheres);

        return " OR $orWhereRaw";
    }

    /**
     * Prepare the SQL query for execution.
     *
     * @return PDOStatement The prepared statement.
     */
    private function prepareQuery(): PDOStatement
    {
        return $this->execute($this->connection->prepare($this->query));
    }

    /**
     * Execute the prepared statement with the bound values.
     *
     * @param PDOStatement $statement The prepared statement.
     * @return PDOStatement The executed statement.
     * @throws SqlExecuteException If the query execution fails.
     */
    private function execute(PDOStatement $statement): PDOStatement
    {
        try {
            $statement->execute($this->queryValue);
            return $statement;
        } catch (Exception $e) {
            Manager::$fileLogger && Manager::$logger->error($e->getMessage(), ['trace' => $e->getTrace()]);
            throw new SqlExecuteException($e->getMessage());
        }
    }

    /**
     * Retrieve the first row from the executed statement.
     *
     * @param PDOStatement $statement The executed statement.
     * @return object|false The first row as an object, or false if no rows exist.
     */
    private function getFirst(PDOStatement $statement)
    {
        return $statement->fetchObject();
    }

    /**
     * Retrieve all rows from the executed statement.
     *
     * @param PDOStatement $statement The executed statement.
     * @return QueryResult The result of the query.
     */
    private function getAll(PDOStatement $statement)
    {
        return $this->formatResult($statement->fetchAll(PDO::FETCH_OBJ), $statement->rowCount());
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
