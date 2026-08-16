<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/config/dz.php';

$db = getDbConnection();

// Try to fetch from backend API first
try {
    $adminToken = $_SESSION['api_token'] ?? '';
    $ch = curl_init('http://127.0.0.1:8000/api/admin/reviews');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $adminToken,
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo $response;
        exit;
    }
} catch (Exception $e) {
    // Fallback below
}

// Local Fallback: Fetch from database directly
try {
    $userId = $_SESSION['user_id'] ?? 0;
    $userRole = $_SESSION['user_role'] ?? 'owner';

    if ($userRole === 'admin') {
        $stmt = $db->query("SELECT r.*, u.name as user_name, p.name as property_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN properties p ON r.property_id = p.id ORDER BY r.created_at DESC");
    } else {
        $stmt = $db->prepare("SELECT r.*, u.name as user_name, p.name as property_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN properties p ON r.property_id = p.id WHERE p.host_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$userId]);
    }

    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = [];
    foreach ($reviews as $r) {
        $output[] = [
            'id' => $r['id'],
            'user_name' => $r['user_name'] ?? ('Guest #' . ($r['user_id'] ?? '')),
            'property_name' => $r['property_name'] ?? 'Lodge Stay',
            'rating' => (int)($r['rating'] ?? 5),
            'comment' => $r['comment'] ?? '',
            'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    echo json_encode($output);
    exit;
} catch (Exception $e) {
    echo json_encode([]);
}
