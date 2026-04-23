<?php
/**
 * BAH Pharmacy — Kök Dizin Yönlendiricisi
 * 
 * Bu dosya ana dizine girildiğinde, sistem kuruluysa public dizinine yönlendirir,
 * sistem kurulu değilse doğrudan kurulum sihirbazına atar.
 */

if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: install/install.php');
} else {
    header('Location: public/index.php');
}
exit;
