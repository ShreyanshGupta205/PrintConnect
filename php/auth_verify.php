<?php
// php/auth_verify.php
require_once dirname(__FILE__) . '/../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$idToken = $data['idToken'] ?? '';
$action = $data['action'] ?? ''; // 'login' or 'register'
$role = $data['role'] ?? 'customer'; // Default role for new users
$name = $data['name'] ?? '';

if (empty($idToken)) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit();
}

// 1. Verify token with Firebase REST API
// TODO: Replace with your actual Firebase Web API Key
$apiKey = "AIzaSyAWDICmDZ8dh1QqApatowrSR9IHrUeAXcA"; 
$url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $errorMsg = 'Token verification failed';
    if ($response) {
        $respData = json_decode($response, true);
        $errorMsg .= ': ' . ($respData['error']['message'] ?? 'Unknown API Error');
    } else {
        $errorMsg .= ': cURL Error ' . curl_error($ch);
    }
    echo json_encode(['success' => false, 'message' => $errorMsg, 'code' => $httpCode]);
    exit();
}

$userData = json_decode($response, true);
$firebaseUid = $userData['users'][0]['localId'];
$email = $userData['users'][0]['email'];

// 2. Sync with local database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE firebase_uid = ? OR email = ?");
    $stmt->execute([$firebaseUid, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update firebase_uid if it was missing (transitioning existing user)
        if (empty($user['firebase_uid'])) {
            $update = $pdo->prepare("UPDATE users SET firebase_uid = ? WHERE user_id = ?");
            $update->execute([$firebaseUid, $user['user_id']]);
        }
    } else {
        // Create new user record
        if ($action === 'register') {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, firebase_uid, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $firebaseUid, $role]);
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$pdo->lastInsertId()]);
            $user = $stmt->fetch();
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found. Please register first.']);
            exit();
        }
    }

    // 3. Start Session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['firebase_uid'] = $firebaseUid;

    echo json_encode([
        'success' => true, 
        'role' => $user['role'],
        'redirect' => ($user['role'] === 'admin' ? 'admin/' : $user['role'] . '/dashboard.php')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
