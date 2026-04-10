<?php
require_once dirname(__FILE__) . '/../config/db.php';

try {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT,
        role TEXT CHECK( role IN ('admin','owner','customer') ) NOT NULL,
        firebase_uid TEXT UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Shops Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS shops (
        shop_id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_id INTEGER NOT NULL,
        shop_name TEXT NOT NULL,
        phone TEXT,
        address TEXT,
        status TEXT CHECK( status IN ('inactive','active') ) DEFAULT 'inactive',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(user_id)
    )");

    // 3. Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        order_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        shop_id INTEGER NOT NULL,
        file_name TEXT,
        pages INTEGER DEFAULT 1,
        print_type TEXT CHECK( print_type IN ('bw','color') ) DEFAULT 'bw',
        binding TEXT CHECK( binding IN ('no','yes') ) DEFAULT 'no',
        status TEXT CHECK( status IN ('pending','printing','ready','completed') ) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
    )");

    // 4. Subscriptions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        sub_id INTEGER PRIMARY KEY AUTOINCREMENT,
        shop_id INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_status TEXT CHECK( payment_status IN ('pending','completed','failed') ) DEFAULT 'pending',
        end_date DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shop_id) REFERENCES shops(shop_id)
    )");

    // 5. Create Default Admin
    if (file_exists(dirname(__FILE__) . '/../config/setup_config.php')) {
        require_once dirname(__FILE__) . '/../config/setup_config.php';
        $admin_email = ADMIN_EMAIL;
        $admin_pass  = ADMIN_PASSWORD;
        $admin_name  = ADMIN_NAME;

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$admin_email]);
        if (!$stmt->fetch()) {
            $stmt_insert = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$admin_name, $admin_email, $admin_pass, 'admin']);
        } else {
            // Update existing admin password to match new requirement
            $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt_update->execute([$admin_pass, $admin_email]);
        }
    }

    echo "Local Test Database Initialized Successfully.";
} catch (PDOException $e) {
    die("Database Initialization Failed: " . $e->getMessage());
}
