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
    $subjectName = $_POST['name'];

    // Попробуем добавить предмет
    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, group_id) VALUES (:name, :groupId)");
        $stmt->execute([':name' => $subjectName, ':groupId' => $groupId]);
        echo json_encode(['status' => 'success', 'message' => 'Subject added']);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            echo json_encode(['status' => 'error', 'message' => 'Этот предмет уже существует для вашей группы.']);
        } else {
            throw $e; // Перебросим другие ошибки
        }
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>