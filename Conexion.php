<?php
$host = 'localhost'; 
$db = 'PJalit'; 
$user = 'root'; 
$pass = 'Akaroki.15'; 

try { 
    $conexion = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4", 
        $user, 
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error de conexión: " . $e->getMessage());
    exit("Error al conectar con la base de datos. Por favor intente más tarde.");
}
?>