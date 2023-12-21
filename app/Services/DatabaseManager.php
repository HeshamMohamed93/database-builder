<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseManager
{
    private static $instance;
    private $connection;
    private $connected = false;

    private function __construct()
    {
        // Private constructor to prevent instantiation
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function connect($host, $username, $password, $database)
    {
        try {
            config([
                'database.connections.dynamic' => [
                    'driver' => 'mysql',
                    'host' => $host,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ],
            ]);

            $this->connection = DB::connection('dynamic');
            $this->connection->reconnect();
            // test connection
            $this->connection->select('SELECT 1');
            $this->connected = true;
        } catch (\Exception $e) {
            $this->connected = false;
            Log::error('Database connection error: ' . $e->getMessage());
        }
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function getConnection()
    {
        if (!$this->connected) {
            $this->connect(session('host'), session('username'), session('password'), session('database'));
        }

        return $this->connection;
    }
}
