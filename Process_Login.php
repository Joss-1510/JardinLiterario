<?php
session_start();
require 'Conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Por favor, complete todos los campos.';
        header('Location: Login.php');
        exit();
    }

    try {
        $stmt = $conexion->prepare("SELECT u.*, p.Nombre as nombre_persona, p.Apellido 
                                   FROM tusuario u
                                   JOIN tpersona p ON u.idPersona = p.id_Persona
                                   WHERE u.Nombre = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $usuario = $stmt->fetch();

        if ($usuario) {
            if ($password === $usuario['Contra']) {

                $_SESSION['SISTEMA'] = [
                    'id' => $usuario['id_Usuario'],
                    'nombre' => $usuario['nombre_persona'] . ' ' . $usuario['Apellido'],
                    'usuario' => $usuario['Nombre'],
                    'rol' => $usuario['idRol']
                ];

                header("Location: Inicio.php");
                exit();
            }
        }

        $_SESSION['error'] = 'Nombre de usuario o contraseña incorrectos.';
        header('Location: Login.php');
        exit();

    } catch (PDOException $e) {
        error_log("Error en Login: " . $e->getMessage());
        $_SESSION['error'] = 'Error en el sistema. Por favor intente más tarde.'.$e->getMessage();
        header('Location: Login.php');
        exit();
    }
}

header('Location: Login.php');
exit();
?>