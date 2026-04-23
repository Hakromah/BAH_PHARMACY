<?php
require_once __DIR__ . '/../core/bootstrap.php';
logAction('Logout', "User {$_SESSION['username']} logged out.");
session_unset();
session_destroy();
header('Location: ' . BASE_URL . '/public/login.php');
exit;
