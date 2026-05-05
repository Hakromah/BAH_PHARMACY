<?php
require 'config/config.php';
require 'core/Database.php';
$pdo = Database::getInstance();
$stmt = $pdo->query("SHOW CREATE TABLE payments");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
