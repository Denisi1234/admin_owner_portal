<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/dz.php';

// Handle AJAX actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $api_token = $_SESSION['api_token'] ?? '';
    
    if ($action === 'create_property') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Send request to Laravel API to create property
        $ch = curl_init("http://127.0.0.1:8000/api/properties");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'name' => $input['name'] ?? '',
            'description' => $input['description'] ?? '',
            'address' => $input['address'] ?? '',
            'city' => $input['city'] ?? '',
            'area' => $input['area'] ?? '',
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
            'price_per_night' => $input['price_per_night'] ?? 0,
            'image_url' => $input['image_url'] ?? '',
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$api_token}"
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resData = json_decode($response, true);
        $db = getDbConnection();
        $userId = $_SESSION['user_id'] ?? 2;

        if ($httpCode === 201 && isset($resData['id'])) {
            // Also insert / sync locally so web portal displays instantly
            try {
                $stmt = $db->prepare("INSERT INTO properties (id, name, description, address, city, area, price_per_night, latitude, longitude, host_id, image_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', datetime('now'), datetime('now'))");
                $stmt->execute([
                    $resData['id'],
                    $input['name'] ?? '',
                    $input['description'] ?? '',
                    $input['address'] ?? '',
                    $input['city'] ?? '',
                    $input['area'] ?? '',
                    $input['price_per_night'] ?? 0,
                    $input['latitude'] ?? null,
                    $input['longitude'] ?? null,
                    $userId,
                    $input['image_url'] ?? '',
                ]);
            } catch (Exception $e) {}
        } else {
            // Local fallback creation
            try {
                $stmt = $db->prepare("INSERT INTO properties (name, description, address, city, area, price_per_night, latitude, longitude, host_id, image_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', datetime('now'), datetime('now'))");
                $stmt->execute([
                    $input['name'] ?? '',
                    $input['description'] ?? '',
                    $input['address'] ?? '',
                    $input['city'] ?? '',
                    $input['area'] ?? '',
                    $input['price_per_night'] ?? 0,
                    $input['latitude'] ?? null,
                    $input['longitude'] ?? null,
                    $userId,
                    $input['image_url'] ?? '',
                ]);
                $lastId = $db->lastInsertId();
                $resData = [
                    'id' => $lastId,
                    'name' => $input['name'] ?? '',
                    'status' => 'Pending'
                ];
                $httpCode = 201;
            } catch (Exception $e) {}
        }
        
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($resData);
        exit;
    }
}
