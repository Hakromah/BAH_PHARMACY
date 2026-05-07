<?php
require 'C:/xampp/htdocs/BAH_PHARMACY/core/Database.php';
require 'C:/xampp/htdocs/BAH_PHARMACY/config/config.php';

$pdo = Database::getInstance();
$stmt = $pdo->prepare("UPDATE users SET password = :pw WHERE username = 'Hassan'");
$stmt->execute([':pw' => password_hash('1234', PASSWORD_DEFAULT)]);
echo "Password updated for Hassan to 1234\n";
