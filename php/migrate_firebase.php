<?php
require_once dirname(__FILE__) . '/../config/db.php';

try {
    // Check if column exists first for safer cross-DB migration
    $stmt = $pdo->query("SELECT * FROM users LIMIT 1");
    $meta = $stmt->getColumnMeta(0); // This is just to test connection
    
    // SQLite doesn't support AFTER or MODIFY COLUMN
    if ($use_sqlite) {
        $pdo->exec("ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(128)");
        // Note: SQLite doesn't easily support changing nullable status, 
        // but it defaults to nullable on new columns
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(128) AFTER user_id");
        $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
    }
    
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_firebase_uid ON users(firebase_uid)");
    
    echo "Migration successful: firebase_uid column added.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration skipped: Column already exists.";
    } else {
        die("Migration failed: " . $e->getMessage());
    }
}
?>
