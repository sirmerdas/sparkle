<?php

namespace Sirmerdas\Sparkle\Database;

use Sirmerdas\Sparkle\Database\{Manager, QueryBuilder\QueryResult};
use Sirmerdas\Sparkle\Exceptions\SqlExecuteException;
use Exception;
use PDO;
use PDOStatement;

class PdoManager
{
    /**
     * @var PDO The PDO connection instance.
     */
    private PDO $pdo;

    /**
     * @var array The values to bind to the query placeholders.
     */
    private array $queryValue = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Prepare the SQL query for execution.
     *
     * @return PDOStatement The prepared statement.
     */
    protected function prepareAndExecuteQuery(string $query): PDOStatement
    {
        return $this->execute($this->pdo->prepare($query));
    }

    /**
     * Execute the prepared statement with the bound values.
     *
     * @param PDOStatement $pdoStatement The prepared statement.
     * @return PDOStatement The executed statement.
     * @throws SqlExecuteException If the query execution fails.
     */
    protected function execute(PDOStatement $pdoStatement): PDOStatement
    {
        try {
            $pdoStatement->execute($this->getQueryValue());
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
    protected function getFirst(PDOStatement $pdoStatement): \stdClass|false
    {
        return $pdoStatement->fetchObject();
    }

    /**
     * Retrieve all rows from the executed statement.
     *
     * @param PDOStatement $pdoStatement The executed statement.
     * @return QueryResult The result of the query.
     */
    protected function getAll(PDOStatement $pdoStatement): QueryResult
    {
        return $this->formatResult($pdoStatement->fetchAll(PDO::FETCH_OBJ), $pdoStatement->rowCount());
    }

    /**
     * Get the value of queryValue
     */
    protected function getQueryValue(): array
    {
        return $this->queryValue;
    }

    /**
     * Set the value of queryValue
     */
    protected function setQueryValue(string|array $queryValue, bool $append = true, bool $expandArray = false): self
    {
        if ($append) {
            if ($expandArray) {
                array_push($this->queryValue, ...$queryValue);
            } else {
                $this->queryValue[] = $queryValue;
            }
        } else {
            $this->queryValue = $queryValue;
        }

        return $this;
    }

    /**
     * Prepares an SQL statement for execution using PDO.
     *
     * @param string $query The SQL query to prepare.
     * @return bool|PDOStatement Returns a PDOStatement object if successful, or false on failure.
     */
    protected function pdoPrepare(string $query): bool|PDOStatement
    {
        return $this->pdo->prepare($query);
    }

    /**
     * Retrieves the ID of the last inserted row.
     */
    protected function pdoLastInsertId(): bool|string
    {
        return $this->pdo->lastInsertId();
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
