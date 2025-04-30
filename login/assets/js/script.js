<?php
// Incluir archivos de configuración y funciones
include 'includes/config.php';
include 'includes/db.php';
include 'includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Conectar a la base de datos
    $pdo = db_connect();

    // Verificar usuario
    $usuario = verificarUsuario($username, $pdo);

    if ($usuario) {
        // Verificar contraseña
        if (password_verify($password, $usuario['password'])) {
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            // Registrar último acceso
            registrarUltimoAcceso($usuario['id'], $pdo);

            // Regenerar ID de sesión
            session_regenerate_id(true);

            // Redirigir al dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Contraseña incorrecta
            registrarIntentoFallido($username, $pdo);
            $_SESSION['error_message'] = "Contraseña incorrecta.";
            header("Location: index.php");
            exit;
        }
    } else {
        // Usuario no encontrado
        $_SESSION['error_message'] = "Usuario no encontrado.";
        header("Location: index.php");
        exit;
    }
}
?>