<?php
require_once __DIR__ . '/../src/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$userId = $_SESSION['user']['id'];

if (verifyUserAccount($pdo, $userId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Verification failed.']);
}
