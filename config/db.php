<?php
// config/db.php
// ==========================================
// INFINITYFREE DEPLOYMENT CONFIGURATION
// ==========================================
// 1. Change $use_sqlite to false
// 2. Fill in the MySQL credentials provided by InfinityFree (vPanel)
$use_sqlite = false; 

$mysql_host = 'sql100.byetcluster.com'; // InfinityFree MySQL Hostname
$mysql_db   = 'if0_41320767_printconnect'; // InfinityFree Database Name
$mysql_user = 'if0_41320767'; // InfinityFree MySQL Username
$mysql_pass = 'vcn7ikK7WDcTBCY'; // InfinityFree MySQL Password

try {
    if ($use_sqlite) {
        // Local Testing Mode
        $db_file = __DIR__ . '/../printconnect.db';
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->exec('PRAGMA foreign_keys = ON;');
    } else {
        // Server Mode (InfinityFree / Hostinger)
        $dsn = "mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8mb4";
        $pdo = new PDO($dsn, $mysql_user, $mysql_pass);
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed. Please check your config/db.php credentials. Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// CSRF Protection Helpers
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}
?>
