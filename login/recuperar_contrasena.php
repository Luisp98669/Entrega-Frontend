<?php
// Incluir el manejador del sistema
include 'system_handler.php';

// Verificar si ya está logueado
if(isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
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
    <title>Recuperar Contraseña | Delicias Paisanas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8B4513; /* Café terroso */
            --primary-light: #A0522D;
            --secondary: #D2B48C; /* Café claro */
            --light: #FFF8DC; /* Beige claro */
            --dark: #362312;
            --gold: #D4AF37; /* Dorado para acentos */
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/img/fondo-paisano.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--dark);
        }
        
        .recovery-container {
            background: rgba(255, 248, 220, 0.95);
            width: 90%;
            max-width: 450px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border: 1px solid var(--secondary);
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: var(--primary-light);
            font-style: italic;
            font-size: 0.9rem;
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary);
            font-weight: 500;
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
        
        button {
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
        
        button:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .back-to-login {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-to-login:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 480px) {
            .recovery-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="logo">
        <img src="assets/img/logo.png" alt="Delicias Paisanas" style="width: 200px; height: auto;">
            <h1>Delicias Paisanas</h1>
            <p>Comida Gourmet para tus gustos</p>
        </div>
        
        <h2><i class="fas fa-key"></i> Recuperar Contraseña</h2>
        
        <?php if (isset($error_recuperacion)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_recuperacion); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_recuperacion)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <div>
                    <p><strong>¡Contraseña recuperada con éxito!</strong></p>
                    <p>Tu nueva contraseña es: <strong><?php echo htmlspecialchars($nueva_contrasena); ?></strong></p>
                    <p>Por favor, anótala en un lugar seguro o cámbiala después de iniciar sesión.</p>
                </div>
            </div>
        <?php else: ?>
            <form method="post" action="recuperar_contrasena.php">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Ingresa tu nombre de usuario" required>
                </div>
                
                <div class="form-group">
                    <label for="respuesta_seguridad"><i class="fas fa-shield-alt"></i> Respuesta de Seguridad</label>
                    <input type="text" id="respuesta_seguridad" name="respuesta_seguridad" placeholder="Respuesta a tu pregunta de seguridad" required>
                </div>
                
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Recuperar Contraseña
                </button>
            </form>
        <?php endif; ?>
        
        <a href="index.php" class="back-to-login">
            <i class="fas fa-sign-in-alt"></i> Volver al inicio de sesión
        </a>
    </div>
</body>
</html>