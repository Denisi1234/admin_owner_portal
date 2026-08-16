<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/config/dz.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

$db = getDbConnection();

if ($action === 'forgot-password') {
    $email = $input['email'] ?? '';
    
    // First try the real API
    try {
        $ch = curl_init('http://127.0.0.1:8000/api/forgot-password');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo $response;
            exit;
        }
    } catch (Exception $e) {
        // Fallback to local
    }

    // Local Fallback
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate a real 6-digit random code
        $code = sprintf("%06d", mt_rand(1, 999999));
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_email'] = $email;
        
        // Send the code via Resend API
        $resendApiKey = 'YOUR_RESEND_API_KEY';
        $ch = curl_init('https://api.resend.com/emails');
        $payload = json_encode([
            'from' => 'Fastnet Stays <welcome@fastnetstays.com>',
            'to' => [$email],
            'subject' => 'Your Password Reset Code',
            'html' => "<strong>Hello,</strong><br><br>You requested a password reset. Your 6-digit reset code is: <strong>{$code}</strong><br><br>If you did not request this, please ignore this email."
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $resendApiKey,
            'Content-Type: application/json'
        ]);
        curl_exec($ch);
        curl_close($ch);
        
        echo json_encode(['message' => 'Reset code has been sent to your email.']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Email not found']);
    }
    exit;
}

if ($action === 'reset-password') {
    $email = $input['email'] ?? '';
    $token = $input['token'] ?? '';
    $password = $input['password'] ?? '';
    
    // Try real API first
    try {
        $ch = curl_init('http://127.0.0.1:8000/api/reset-password');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo $response;
            exit;
        }
    } catch (Exception $e) {
        // Fallback to local
    }

    // Local Fallback
    if (isset($_SESSION['reset_code']) && $_SESSION['reset_code'] === $token && $_SESSION['reset_email'] === $email) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $email]);
        
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_email']);
        
        echo json_encode(['message' => 'Password reset successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid or expired token']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['message' => 'Not found']);
