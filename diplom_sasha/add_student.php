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
    $pdo->exec("PRAGMA busy_timeout = 5000");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = trim($_POST['username']);
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $family = trim($_POST['family']);
        $groupId = trim($_POST['groupId']);

        if (empty($username) || empty($firstname) || empty($lastname) || empty($family)) {
            echo json_encode(['status' => 'error', 'message' => 'Все поля обязательны для заполнения.']);
            exit();
        }

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

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_at, firstname, lastname, family, group_id) VALUES (:username, :password, 'student', :createdAt, :firstname, :lastname, :family, :groupId)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':createdAt', $createdAt);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':family', $family);
            $stmt->bindParam(':groupId', $groupId);
            $stmt->execute();

            $lastId = $pdo->lastInsertId();
            $pdo->commit();

            echo json_encode(['status' => 'success', 'id' => $lastId, 'password' => $password]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>