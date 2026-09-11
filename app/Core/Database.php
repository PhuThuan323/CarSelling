<?php

namespace App\Core;

/**
 * Database Connection Handler
 */
class Database {
    private $connection;
    private $host;
    private $user;
    private $password;
    private $database;

    public function __construct($host = 'localhost', $user = 'root', $password = '', $database = 'car_sales') {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    public function connect() {
        try {
            $this->connection = new \PDO(
                "mysql:host={$this->host};dbname={$this->database}",
                $this->user,
                $this->password
            );
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->connection;
        } catch (\PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function getConnection() {
        return $this->connection;
    }
}
