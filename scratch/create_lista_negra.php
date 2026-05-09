<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require 'config/database.php';
$db = Flight::get('db');
$db->exec("
CREATE TABLE IF NOT EXISTS lista_negra (
  id              SERIAL PRIMARY KEY,
  token           TEXT NOT NULL UNIQUE,
  fecha_expiracion TIMESTAMP WITH TIME ZONE NOT NULL,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
");
echo "lista_negra table created successfully!";
