<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Load database credentials from environment variables
$host = "DATABASE";
$db   = "test";
$user = "admin";
$pass = "PASSWORD";
$charset = 'utf8mb4';

// PDO setup
$dsn = "mysql:host=$host;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


// Connect to DB
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("DB connection failed: $host" . $e->getMessage());
}

// initilize if the table is not created
$initFlag ='/tmp/.db_initialized';

if (!file_exists($initFlag)) {
    $tableSql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";
    
    try {
        $pdo->exec($tableSql);
        // Create the flag file to skip next time
        file_put_contents($initFlag, "initialized");
    } catch (PDOException $e) {
        die(" Failed to create table: " . $e->getMessage());
    }
}

// Handle Form Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = $_POST['id'] ?? null;
    $name  = $_POST['name'] ?? null;
    $email = $_POST['email'] ?? null;

    if (isset($_POST['create'])) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute([$name, $email]);
    } elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $id]);
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>
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
