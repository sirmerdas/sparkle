<?php

namespace Sirmerdas\Sparkle\Database\QueryBuilder;

/**
 * Class QueryResult
 *
 * Represents the result of a database query, including the number of rows
 * and the items retrieved.
 *
 * @package Sirmerdas\Sparkle
 */
class QueryResult
{
    /**
     * @var int The number of rows returned by the query.
     */
    private int $rowCount;

    /**
     * @var array|object The items retrieved by the query.
     */
    private array|object $items;

    /**
     * QueryResult constructor.
     *
     * @param int $rowCount The number of rows returned by the query.
     * @param array|object $items The items retrieved by the query.
     */
    public function __construct(int $rowCount, array|object $items)
    {
        $this->rowCount = $rowCount;
        $this->items = $items;
    }

    /**
     * Get the items retrieved by the query.
     *
     * @return array|object The items retrieved by the query.
     */
    public function getItems(): array|object
    {
        return $this->items;
    }

    /**
     * Get the number of rows returned by the query.
     *
     * @return int The number of rows returned by the query.
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Convert the query result to an associative array.
     *
     * @return array An associative array containing the row count and items.
     */
    public function toArray(): array
    {
        return [
            'rowCount' => $this->rowCount,
            'items' => $this->itemsToArray($this->items),
        ];
    }

    /**
     * Check if result has any items or not.
     */
    public function hasItems(): bool
    {
        return $this->rowCount > 0;
    }

    /**
     * Convert the items to an array format.
     *
     * @param array $items The items to be converted.
     * @return array The items converted to an array format.
     */
    private function itemsToArray(array $items): array
    {
        return array_map(fn ($result): array => (array) $result, $items);
    }
}
