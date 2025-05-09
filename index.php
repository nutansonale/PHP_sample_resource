<?php
require 'vendor/autoload.php'; // if using Composer for dotenv

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load env vars
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Connect to RDS
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Handle Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute([$_POST['name'], $_POST['email']]);
    } elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['id']]);
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
}

// Fetch Users
$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>RDS CRUD</title>
</head>
<body>
    <h2>Create / Update User</h2>
    <form method="POST">
        <input type="hidden" name="id" placeholder="ID (for update/delete)">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit" name="create">Create</button>
        <button type="submit" name="update">Update</button>
    </form>

    <h2>Delete User</h2>
    <form method="POST">
        <input type="number" name="id" placeholder="User ID" required>
        <button type="submit" name="delete">Delete</button>
    </form>

    <h2>All Users</h2>
    <ul>
        <?php foreach ($users as $user): ?>
            <li>ID: <?= $user['id'] ?> | <?= $user['name'] ?> (<?= $user['email'] ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
