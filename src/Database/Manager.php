<?php

/**
 * Manager class for handling database connections and logging.
 *
 * @package Sirmerdas\Sparkle\Database
 */

namespace Sirmerdas\Sparkle\Database;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PDO;
use Sirmerdas\Sparkle\{Enums\Database, Exceptions\ConnectionException};

class Manager
{
    public static array $connections;

    /**
     * active connection
     */
    public static PDO|null $connection;

    /**
     * @var Logger|null $logger The logger instance.
     */
    public static $logger;

    /**
     * @var bool $fileLogger Indicates whether file logging is enabled.
     */
    public static bool $fileLogger = false;

    /**
     * Determines whether errors should be suppressed instead of throwing exceptions.
     */
    public static bool $failSilently;

    public function __construct(string|null $logPath = null, bool $failSilently = false)
    {
        if ($logPath !== null && is_string($logPath) && is_string($logPath)) {
            static::$fileLogger = true;
            if (!static::$logger instanceof Logger) {
                $logger = new Logger('Database');
                $logger->pushHandler(new StreamHandler($logPath, Level::Warning));
                static::$logger = $logger;
            }
        }
        static::$failSilently = $failSilently;
    }


    /**
     * Adds a new database connection.
     *
     * @param Database $database The database type (e.g., MySQL, PostgreSQL).
     * @param string $host The hostname of the database server.
     * @param string $dbName The name of the database.
     * @param string $username The username for the database connection.
     * @param string $password The password for the database connection.
     * @param string $charset The character set to use for the connection (default is 'utf8').
     * @param array $pdoConnectionOptions Additional PDO connection options (default is an empty array).
     * @param string $connectionName The name to assign to this connection (default is 'default').
     *
     * @throws ConnectionException If the connection to the database fails.
     */
    public function addConnection(Database $database, string $host, string $dbName, string $username, string $password, string $charset = 'utf8', array $pdoConnectionOptions = [], string $connectionName = 'default'): void
    {
        $connectionDsn = sprintf("%s:host=%s;dbname=%s;charset=%s;", $database->value, $host, $dbName, $charset);
        static::$connections[$connectionName] = [
            'dsn' => $connectionDsn,
            'username' => $username,
            'password' => $password,
            'pdoOptions' => $pdoConnectionOptions
        ];
    }

    /**
     * boot a connection
     *
     *
     */
    public static function boot(string $connectionName = 'default'): void
    {
        if (!isset(static::$connections[$connectionName])) {
            if (static::$fileLogger) {
                static::$logger->critical("Connection {$connectionName} does not exists.");
            }
            throw new ConnectionException("Connection {$connectionName} does not exists.");
        }

        try {
            $selectedConnection = static::$connections[$connectionName];
            static::$connection = new PDO($selectedConnection['dsn'], $selectedConnection['username'], $selectedConnection['password'], $selectedConnection['pdoOptions']);
        } catch (ConnectionException $e) {
            if (static::$fileLogger) {
                static::$logger->critical($e->getMessage(), ['trace' => $e->getTrace(), 'prev' => $e->getPrevious()]);
            }
            throw new ConnectionException("Failed to connect to database with provided credentials. Check log for more details.");
        }

    }
}
