<?php
session_start();
require 'includes/config.php';
require 'includes/db.php';
require 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $pdo = db_connect();
        $usuario = verificarUsuario($username, $pdo);
        
        if ($usuario && verifyPassword($password, $usuario['password']) && !$usuario['cuenta_bloqueada']) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            
            registrarActividad($usuario['id'], 'Inicio de sesión exitoso', $pdo);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas o cuenta bloqueada';
        }
    } catch (PDOException $e) {
        $error = 'Error en el sistema: ' . $e->getMessage();
    }
}
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
    <title>Login | Delicias Paisanas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8B4513;
            --primary-light: #A0522D;
            --secondary: #D2B48C;
            --light: #FFF8DC;
            --dark: #362312;
            --gold: #D4AF37;
        }
        
        body {
            background: url('assets/img/fondo-paisano.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: var(--dark);
        }
        
        .login-container {
            background: rgba(255, 248, 220, 0.95);
            width: 90%;
            max-width: 450px;
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
            font-weight: 700;
        }
        
        .logo p {
            color: var(--primary-light);
            font-style: italic;
            font-size: 1rem;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--secondary);
            border-radius: 5px;
            font-size: 16px;
            background: white;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.2);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-login:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .error-message {
            color: #721c24;
            background: #f8d7da;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-message i {
            margin-right: 8px;
        }
        
        .links {
            margin-top: 25px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        
        .links a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .links a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
        <img src="assets/img/logo.png" alt="Delicias Paisanas" style="width: 200px; height: auto;">
            <h1>Delicias Paisanas</h1>
            <p>Comida Gourmet para tus gustos</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Usuario</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
            
            <div class="links">
                <a href="recuperar_contrasena.php"><i class="fas fa-key"></i> Recuperar contraseña</a>
                <a href="registro.php"><i class="fas fa-user-plus"></i> Registrarse</a>
            </div>
        </form>
    </div>
</body>
</html>