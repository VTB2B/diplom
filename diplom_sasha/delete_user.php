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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userId'])) {
        $userId = $_POST['userId'];

        // Проверка существования пользователя
        $checkStmt = $pdo->prepare("SELECT group_id FROM users WHERE id = :id");
        $checkStmt->bindParam(':id', $userId);
        $checkStmt->execute();
        $user = $checkStmt->fetch();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Пользователь не существует.']);
            exit();
        }

        $groupId = $user['group_id'];

        // Удаление пользователя
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        // Удаление группы, если она существует
        if ($groupId) {
            $deleteGroupStmt = $pdo->prepare("DELETE FROM groups WHERE id = :groupId");
            $deleteGroupStmt->bindParam(':groupId', $groupId);
            $deleteGroupStmt->execute();
        }

        echo json_encode(['status' => 'success']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>