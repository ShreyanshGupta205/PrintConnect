<?php
require_once 'config/db.php';

// Generate a correct hash for 'password123'
$newHash = password_hash('password123', PASSWORD_DEFAULT);

// Update all 3 demo users
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email IN ('admin@printconnect.com', 'owner1@printconnect.com', 'customer1@printconnect.com')");
$stmt->execute([$newHash]);

echo "<h2>✅ Password reset successful!</h2>";
echo "<p>Rows updated: " . $stmt->rowCount() . "</p>";
echo "<p>New hash: " . htmlspecialchars($newHash) . "</p>";
echo "<p><strong>All 3 accounts now use password: <code>password123</code></strong></p>";
echo "<p><a href='login.php'>Go to Login</a></p>";
echo "<br><br><strong style='color:red'>⚠️ IMPORTANT: Delete this file from your server after using it!</strong>";
?>
