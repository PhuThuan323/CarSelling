<?php

namespace App\Models;

use App\Core\Database;

/**
 * Product Model
 */
class Product {
    private $db;

    public function __construct() {
        $dbConfig = new Database();
        $this->db = $dbConfig->connect();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM products");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function search($term) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
        $searchTerm = "%$term%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create($name, $description, $price, $stock) {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $description, $price, $stock]);
    }

    public function update($id, $name, $description, $price, $stock) {
        $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $price, $stock, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
