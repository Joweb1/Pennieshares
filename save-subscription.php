<?php

require_once __DIR__ . '/src/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user']['id'];
$data = json_decode(file_get_contents('php://input'), true);

// Handle Expo push token
if (isset($data['expoPushToken'])) {
    $expoPushToken = $data['expoPushToken'];

    // Check if token already exists for this user to prevent duplicates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM expo_push_tokens WHERE user_id = ? AND token = ?");
    $stmt->execute([$userId, $expoPushToken]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO expo_push_tokens (user_id, token) VALUES (?, ?)");
        $stmt->execute([$userId, $expoPushToken]);
    }

    echo json_encode(['success' => true, 'message' => 'Expo token saved']);
    exit;
}

// Existing logic for web push subscriptions
if (isset($data['endpoint'])) {
    $endpoint = $data['endpoint'];
    $p256dh = $data['keys']['p256dh'];
    $auth = $data['keys']['auth'];

    // Check if subscription already exists for this user to prevent duplicates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
    $stmt->execute([$userId, $endpoint]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth]);
    }

    echo json_encode(['success' => true, 'message' => 'Web subscription saved']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid subscription data']);
}
