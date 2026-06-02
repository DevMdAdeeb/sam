<?php
include 'db.php';

function init_db($pdo) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            session_id VARCHAR(255) PRIMARY KEY,
            last_active DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            supplier VARCHAR(255) DEFAULT 'غير محدد',
            sizes VARCHAR(255),
            price DECIMAL(10, 2) NOT NULL,
            cost_price DECIMAL(10, 2) DEFAULT 0,
            discount_price DECIMAL(10, 2) DEFAULT NULL,
            quantity INT DEFAULT NULL,
            image VARCHAR(255),
            admin_note TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255),
            product_id INT,
            qty INT DEFAULT 1,
            size VARCHAR(50),
            FOREIGN KEY (session_id) REFERENCES users(session_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_code VARCHAR(20) NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(20),
            address TEXT NOT NULL,
            notes TEXT,
            total_amount DECIMAL(10, 2) NOT NULL,
            status ENUM('new', 'processing', 'shipping', 'delivered', 'canceled') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            product_id INT,
            product_name VARCHAR(255),
            price DECIMAL(10, 2),
            cost_price DECIMAL(10, 2) DEFAULT 0,
            qty INT,
            size VARCHAR(50),
            item_status ENUM('valid', 'returned') DEFAULT 'valid',
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255),
            product_id INT,
            FOREIGN KEY (session_id) REFERENCES users(session_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY,
            store_name VARCHAR(255),
            store_name_2 VARCHAR(255),
            whatsapp_number VARCHAR(20),
            maintenance_mode TINYINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_super TINYINT DEFAULT 0,
            must_change_password TINYINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255),
            auth VARCHAR(255)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }

    // Insert default settings if table is empty
    $checkSettings = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($checkSettings == 0) {
        $pdo->exec("INSERT INTO settings (id, store_name, store_name_2, whatsapp_number, maintenance_mode)
                    VALUES (1, 'SAM', 'STORE', '967738183179', 0)");
    }

    // Insert default admin if table is empty
    $checkAdmins = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($checkAdmins == 0) {
        $pdo->exec("INSERT INTO admins (email, password, is_super, must_change_password)
                    VALUES ('admin@sam.com', '$2y$10$7rEB97S8.H8Zp6i0V/o7ueL7U9p.v/n9Q/bH/qYvI/N6I6yY2w6m.', 1, 0)");
    }

    // Check for missing columns (migration)
    $missingColumns = [
        'products' => [
            'cost_price' => "ALTER TABLE products ADD COLUMN cost_price DECIMAL(10, 2) DEFAULT 0 AFTER price"
        ],
        'order_items' => [
            'cost_price' => "ALTER TABLE order_items ADD COLUMN cost_price DECIMAL(10, 2) DEFAULT 0 AFTER price"
        ],
        'settings' => [
            'store_name_2' => "ALTER TABLE settings ADD COLUMN store_name_2 VARCHAR(255) AFTER store_name"
        ]
    ];

    foreach ($missingColumns as $table => $columns) {
        foreach ($columns as $column => $alterQuery) {
            try {
                $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
                if (!$check) {
                    $pdo->exec($alterQuery);
                }
            } catch (Exception $e) {}
        }
    }
}

try {
    init_db($pdo);
    echo "Database initialized successfully.";
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage();
}
?>
