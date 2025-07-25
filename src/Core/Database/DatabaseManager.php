<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\AppConfig;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Manages the PDO connection to the database.
 */
class DatabaseManager
{
    private ?PDO $pdo = null;
    private array $config;

    /**
     * @param AppConfig $config The application configuration service.
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config->get('database'); // Loads the 'database' section from the configuration
    }

    /**
     * Get the PDO instance for the default connection.
     *
     * @return PDO
     * @throws RuntimeException If the connection fails.
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Establishes the PDO connection.
     *
     * @throws RuntimeException If the connection fails.
     */
    private function connect(): void
    {
        $defaultConnection = $this->config['default_connection'];
        $connectionConfig = $this->config['connections'][$defaultConnection];

        $driver = $connectionConfig['driver'];
        $host = $connectionConfig['host'];
        $port = $connectionConfig['port'];
        $database = $connectionConfig['database'];
        $username = $connectionConfig['username'];
        $password = $connectionConfig['password'];
        $options = $connectionConfig['options'] ?? [];

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException("Could not connect to the database: " . $e->getMessage(), 0, $e);
        }
    }
}