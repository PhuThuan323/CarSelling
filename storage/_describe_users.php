 <?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_NAME'] ?? ''),
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? ''
);

echo "=== users columns ===\n";

foreach ($pdo->query('DESCRIBE users') as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . "\n";
}

echo "\n=== users by role ===\n";

foreach ($pdo->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role") as $row) {
    echo $row['role'] . ': ' . $row['total'] . "\n";
}
