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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = trim($_POST['username']);

        if (empty($username)) {
            echo json_encode(['status' => 'error', 'message' => 'Имя пользователя не может быть пустым.']);
            exit();
        }

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();
        $userCount = $checkStmt->fetchColumn();

        if ($userCount > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Имя пользователя уже занято.']);
            exit();
        } else {
            // Create a new group
            $groupStmt = $pdo->prepare("INSERT INTO groups (name) VALUES (:name)");
            $groupStmt->bindParam(':name', $username);
            $groupStmt->execute();

            // Get the last inserted group ID
            $groupId = $pdo->lastInsertId();

            // Create a random password
            $password = bin2hex(random_bytes(4));
            $createdAt = date('Y-m-d H:i:s'); // Current date and time

            // Insert the new user with the group ID
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_at, group_id) VALUES (:username, :password, 'teacher', :createdAt, :groupId)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':createdAt', $createdAt);
            $stmt->bindParam(':groupId', $groupId);
            $stmt->execute();

            $lastId = $pdo->lastInsertId();

            echo json_encode(['status' => 'success', 'id' => $lastId, 'password' => $password, 'created_at' => $createdAt, 'group_id' => $groupId]);
            exit();
        }
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>