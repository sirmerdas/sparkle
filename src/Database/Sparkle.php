<?php

namespace Sirmerdas\Sparkle\Database;

use Sirmerdas\Sparkle\Database\QueryBuilder\Builder;

class Sparkle
{
    /**
     * Changes the database connection to the specified connection name.
     *
     * @param string $connectionName The name of the database connection to switch to.
     * @return self A new instance of the current class.
     */
    public static function connection(string $connectionName): self
    {
        Manager::boot($connectionName);
        return new self();
    }

    /**
     * Create a new Builder instance for the specified table.
     *
     * @param string $table The name of the database table.
     * @param string|null $as An optional alias for the table.
     * @return Builder A new Builder instance for the specified table.
     */
    public static function table(string $table, string|null $as = null): Builder
    {
        return new Builder($table, $as);
    }
}
