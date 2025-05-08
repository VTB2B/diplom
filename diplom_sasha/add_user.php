<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$dbFilePath = __DIR__ . '/users.db';

try {
    $pdo = new PDO("sqlite:$dbFilePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA busy_timeout = 5000"); // Set busy timeout

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = trim($_POST['username']);

        if (empty($username)) {
            echo json_encode(['status' => 'error', 'message' => 'Имя пользователя не может быть пустым.']);
            exit();
        }

        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();
        $userCount = $checkStmt->fetchColumn();

        if ($userCount > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Имя пользователя уже занято.']);
            exit();
        }

        $password = bin2hex(random_bytes(4));
        $createdAt = date('Y-m-d H:i:s');

        // Start a transaction
        $pdo->beginTransaction();

        try {
            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_at) VALUES (:username, :password, 'teacher', :createdAt)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':createdAt', $createdAt);
            $stmt->execute();

            $lastId = $pdo->lastInsertId();

            // Create a new group
            $groupStmt = $pdo->prepare("INSERT INTO groups (name) VALUES (:groupName)");
            $groupStmt->bindParam(':groupName', $username);
            $groupStmt->execute();

            $groupId = $pdo->lastInsertId();

            // Update the user's group_id
            $updateUserStmt = $pdo->prepare("UPDATE users SET group_id = :groupId WHERE id = :userId");
            $updateUserStmt->bindParam(':groupId', $groupId);
            $updateUserStmt->bindParam(':userId', $lastId);
            $updateUserStmt->execute();

            // Commit the transaction
            $pdo->commit();

            echo json_encode(['status' => 'success', 'id' => $lastId, 'password' => $password, 'created_at' => $createdAt]);
        } catch (Exception $e) {
            $pdo->rollBack(); // Rollback on error
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>