<?php
session_start();

// Registrar actividad de logout si el usuario estaba logueado
if (isset($_SESSION['usuario_id'])) {
    require 'includes/config.php';
    require 'includes/db.php';
    require 'includes/functions.php';
    
    try {
        $pdo = db_connect();
        registrarActividad($_SESSION['usuario_id'], "Cierre de sesión", $pdo);
    } catch (PDOException $e) {
        error_log("Error al registrar actividad de logout: " . $e->getMessage());
    }
}

// Destruir la sesión
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<!-- Favicon - Versión compatible con todos navegadores -->
<link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
<link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">

<!-- Para dispositivos Apple -->
<link rel="apple-touch-icon" href="assets/img/favicon.png">

<!-- Para Android/Chrome -->
<link rel="icon" sizes="192x192" href="assets/img/favicon.png">

<!-- Para Windows 8/10 -->
<meta name="msapplication-TileImage" content="assets/img/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de sesión | Delicias Paisanas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8B4513;
            --primary-light: #A0522D;
            --secondary: #D2B48C;
            --light: #FFF8DC;
            --dark: #362312;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/img/fondo-paisano.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: var(--dark);
        }
        
        .logout-container {
            background: rgba(255, 248, 220, 0.95);
            width: 90%;
            max-width: 500px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--secondary);
            text-align: center;
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: var(--primary-light);
            font-style: italic;
            font-size: 1rem;
            margin: 0;
        }
        
        .message {
            margin: 25px 0;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .btn-login {
            display: inline-block;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logo">
<img src="assets/img/logo.png" alt="Delicias Paisanas" style="width: 200px; height: auto;">
            <h1>Delicias Paisanas</h1>
            <p>Comida Gourmet para tus gustos</p>
        </div>
        
        <div class="icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <div class="message">
            <p>Has cerrado sesión correctamente. Esperamos verte pronto de nuevo.</p>
            <p>¡Gracias por elegirnos!</p>
        </div>
        
        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Volver a iniciar sesión
        </a>
    </div>
</body>
</html>