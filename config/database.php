<?php

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    Flight::set('db', $pdo);
} catch (\PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "data" => null,
        "error" => [
            "code" => "DATABASE_ERROR",
            "message" => "Error al conectar a la base de datos",
            "details" => null 
        ]
    ]);
    exit;
}
