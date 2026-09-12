<?php

namespace App\Core;
use PDO;
use PDOException;
/**
 * Database Connection Handler
 */

class Database {
    private static ?PDO $connection = null;
    public static function connection(): PDO{
        if(self::$connection === null){
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $db = $_ENV['DB_NAME'] ?? '';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$connection;
    }
}
