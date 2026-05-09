<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require 'config/database.php';
$db = Flight::get('db');
$hash = password_hash('secret123', PASSWORD_BCRYPT);
$db->exec("UPDATE usuarios SET contrasena = '$hash'");
echo "Updated passwords successfully!";
