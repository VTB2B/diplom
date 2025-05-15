<?php
session_start();
if (!isset($_SESSION['user'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$dbFilePath = __DIR__ . '/users.db';
$subjectId = $_POST['id'];

try {
    $pdo = new PDO("sqlite:$dbFilePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = :id");
    $stmt->execute([':id' => $subjectId]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>