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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userId']) && isset($_POST['newPassword'])) {
        $userId = $_POST['userId'];
        $newPassword = trim($_POST['newPassword']); // Новый пароль без хеширования

        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->bindParam(':password', $newPassword);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        echo json_encode(['status' => 'success']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>