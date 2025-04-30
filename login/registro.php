<?php
// Mostrar todos los errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir el manejador del sistema
include 'system_handler.php';

// Verificar si ya hay una sesión activa
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Procesar el formulario si fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar entradas usando alternativas modernas
    $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
    $password = $_POST['password'] ?? ''; // No sanitizar contraseña para no alterarla
    $nombre = isset($_POST['nombre']) ? htmlspecialchars(trim($_POST['nombre'])) : '';
    $apellido = isset($_POST['apellido']) ? htmlspecialchars(trim($_POST['apellido'])) : '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rol = isset($_POST['rol']) ? htmlspecialchars(trim($_POST['rol'])) : 'empleado';
    $pregunta_seguridad = isset($_POST['pregunta_seguridad']) ? htmlspecialchars(trim($_POST['pregunta_seguridad'])) : '';
    $respuesta_seguridad = isset($_POST['respuesta_seguridad']) ? htmlspecialchars(trim($_POST['respuesta_seguridad'])) : '';

    // Validar campos obligatorios
    if (empty($username) || empty($password) || empty($nombre) || empty($email) || empty($pregunta_seguridad) || empty($respuesta_seguridad)) {
        $error_registro = "Todos los campos son obligatorios.";
    } else {
        // Procesar registro
        $resultado = registrarUsuario($username, $password, $nombre, $apellido, $email, $rol, $pregunta_seguridad, $respuesta_seguridad);
        
        if ($resultado['success']) {
            $success_registro = true;
        } else {
            $error_registro = $resultado['message'];
        }
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
    <title>Registro | Delicias Paisanas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #8B4513; /* Café terroso */
            --secondary-color: #D2B48C; /* Café claro */
            --accent-color: #5C3317; /* Café oscuro */
            --light-color: #FFF8DC; /* Beige claro */
            --error-color: #D32F2F;
            --success-color: #388E3C;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-color);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: url('assets/img/bg-pattern.png');
            background-size: cover;
            padding: 20px;
        }
        
        .page-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            width: 100%;
            padding: 40px 20px;
        }
        
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--secondary-color);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            max-width: 150px;
            height: auto;
        }
        
        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--accent-color);
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--secondary-color);
            border-radius: 4px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.2);
        }
        
        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            margin-top: 10px;
            text-transform: uppercase;
        }
        
        button[type="submit"]:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .back-to-login {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .back-to-login:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #FFEBEE;
            color: var(--error-color);
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--error-color);
        }
        
        .success-message {
            background-color: #E8F5E9;
            color: var(--success-color);
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--success-color);
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background-color: #ddd;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .php-errors {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #F8D7DA;
            color: #721C24;
            padding: 15px;
            border-bottom: 1px solid #F5C6CB;
            z-index: 1000;
            font-family: monospace;
        }
        
        @media (max-width: 600px) {
            .page-container {
                padding: 20px 10px;
            }
            
            .login-container {
                padding: 20px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php if (error_reporting() && error_get_last()): ?>
        <div class="php-errors">
            <strong>Errores:</strong><br>
            <?php echo htmlspecialchars(print_r(error_get_last(), true)); ?>
        </div>
    <?php endif; ?>
    
    <div class="page-container">
        <div class="login-container">
            <div class="logo">
                <img src="assets/img/logo.png" alt="Delicias Paisanas">
            </div>
            
            <h2>Registro de Usuario</h2>
            
            <?php if (isset($error_registro)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_registro); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_registro)): ?>
                <div class="success-message">
                    <p>¡Registro completado con éxito!</p>
                    <p>Ahora puedes <a href="index.php">iniciar sesión</a> con tus credenciales.</p>
                </div>
            <?php else: ?>
                <form method="post" action="registro.php">
                    <div class="form-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required
                               oninput="updatePasswordStrength(this.value)">
                        <div class="password-strength">
                            <div class="strength-meter" id="password-strength-meter"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required
                               value="<?php echo htmlspecialchars($nombre ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" required
                               value="<?php echo htmlspecialchars($apellido ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo electrónico:</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol">
                            <option value="empleado" <?php echo ($rol ?? '') == 'empleado' ? 'selected' : ''; ?>>Empleado</option>
                            <option value="Cliente" <?php echo ($rol ?? '') == 'Cliente' ? 'selected' : ''; ?>>Cliente</option>
           <!--             <option value="admin" <?php echo ($rol ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option> Para Android/Chrome -->   
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="pregunta_seguridad">Pregunta de seguridad:</label>
                        <input type="text" id="pregunta_seguridad" name="pregunta_seguridad" required
                               value="<?php echo htmlspecialchars($pregunta_seguridad ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="respuesta_seguridad">Respuesta de seguridad:</label>
                        <input type="text" id="respuesta_seguridad" name="respuesta_seguridad" required
                               value="<?php echo htmlspecialchars($respuesta_seguridad ?? ''); ?>">
                    </div>
                    
                    <button type="submit">Registrarse</button>
                </form>
                
                <a href="index.php" class="back-to-login">¿Ya tienes cuenta? Inicia sesión</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updatePasswordStrength(password) {
            const meter = document.getElementById('password-strength-meter');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            const width = strength * 25;
            let color = '#ff0000';
            
            if (strength >= 2) color = '#ffcc00';
            if (strength >= 3) color = '#66cc00';
            if (strength >= 4) color = '#00aa00';
            
            meter.style.width = width + '%';
            meter.style.backgroundColor = color;
        }
    </script>
</body>
</html>