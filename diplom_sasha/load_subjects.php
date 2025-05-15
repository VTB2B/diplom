<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$dbFilePath = __DIR__ . '/users.db';
try {
    $pdo = new PDO("sqlite:$dbFilePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получите group_id пользователя
    $stmt = $pdo->prepare("SELECT group_id FROM users WHERE username = :username");
    $stmt->execute([':username' => $_SESSION['user']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit();
    }

    $groupId = $user['group_id'];

    // Получите предметы для данной группы
    $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE group_id = :groupId");
    $stmt->execute([':groupId' => $groupId]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'subjects' => $subjects]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>