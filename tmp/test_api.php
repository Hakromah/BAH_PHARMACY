<?php
session_start();
$_SESSION['user_id'] = 1;
$_GET['q'] = 'tablet';
$_GET['cat'] = '';
$_GET['stock'] = '';
require_once 'modules/sales/product_search_api.php';
