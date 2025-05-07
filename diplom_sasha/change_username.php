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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userId']) && isset($_POST['newUsername'])) {
        $userId = $_POST['userId'];
        $newUsername = trim($_POST['newUsername']);

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :id");
        $checkStmt->bindParam(':username', $newUsername);
        $checkStmt->bindParam(':id', $userId);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Имя пользователя уже занято.']);
            exit();
        }

        $stmt = $pdo->prepare("UPDATE users SET username = :username WHERE id = :id");
        $stmt->bindParam(':username', $newUsername);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        echo json_encode(['status' => 'success']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>