<?php
require 'C:/xampp/htdocs/BAH_PHARMACY/core/Database.php';
require 'C:/xampp/htdocs/BAH_PHARMACY/config/config.php';

$pdo = Database::getInstance();

try {
    $pdo->exec("ALTER TABLE `users` 
        ADD COLUMN `role` ENUM('ADMIN', 'USER') NOT NULL DEFAULT 'USER' AFTER `password`,
        ADD COLUMN `email` VARCHAR(255) NULL UNIQUE AFTER `role`,
        ADD COLUMN `phone` VARCHAR(50) NULL UNIQUE AFTER `email`");
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    echo "Columns might already exist or error: " . $e->getMessage() . "\n";
}

// Check if admin user exists, if not create, if exists update role and password to 123456
$hash = password_hash('123456', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    $pdo->prepare("UPDATE users SET role = 'ADMIN', password = :pw WHERE username = 'admin'")->execute([':pw' => $hash]);
    echo "Admin user updated.\n";
} else {
    $pdo->prepare("INSERT INTO `users` (`username`, `password`, `role`, `first_name`, `last_name`) VALUES ('admin', :pw, 'ADMIN', 'Yönetici', 'Admin')")
        ->execute([':pw' => $hash]);
    echo "Admin user created.\n";
}

// Make Hassan an admin too so we don't lock him out for testing
$pdo->exec("UPDATE users SET role = 'ADMIN' WHERE username = 'Hassan'");

echo "Done.\n";
