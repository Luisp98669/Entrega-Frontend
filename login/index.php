<?php
session_start();
require 'includes/config.php';
require 'includes/db.php';
require 'includes/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Procesar nuevo pedido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_pedido'])) {
    $menu = $_POST['menu'];
    $cantidad = (int)$_POST['cantidad'];
    $metodo_pago = $_POST['metodo_pago'];
    $usuario_id = $_SESSION['usuario_id'];
    
    preg_match('/\$(.*)$/', $menu, $matches);
    $precio = isset($matches[1]) ? (float)str_replace('.', '', $matches[1]) : 0;
    $total = $precio * $cantidad;
    
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, menu, cantidad, precio, total, metodo_pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $menu, $cantidad, $precio, $total, $metodo_pago]);
        
        registrarActividad($usuario_id, "Nuevo pedido: $menu (x$cantidad)", $pdo);
        
        header('Location: index.php?success=1');
        exit;
    } catch (PDOException $e) {
        $error = "Error al guardar el pedido: " . $e->getMessage();
    }
}

// Obtener datos del usuario
try {
    $pdo = db_connect();
    
    // Historial de pedidos
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Datos del usuario
    $stmt = $pdo->prepare("SELECT nombre, apellido, email FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
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
    <title>Panel | Delicias Paisanas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8B4513;
            --primary-light: #A0522D;
            --secondary: #D2B48C;
            --light: #FFF8DC;
            --dark: #362312;
            --gold: #D4AF37;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f5f0;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Header mejorado con glassmorphism */
        .header {
            background: rgba(139, 69, 19, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .header.scrolled {
            padding: 0.5rem 2rem;
            background: rgba(139, 69, 19, 0.95);
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-left: 10px;
            background: linear-gradient(to right, #ffffff, var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .logo img {
            height: 50px;
            transition: var(--transition);
            filter: drop-shadow(0 2px 5px rgba(0,0,0,0.2));
        }
        
        .header.scrolled .logo img {
            height: 40px;
        }
        
        .user-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: bold;
            font-size: 1.2rem;
            transition: var(--transition);
            cursor: pointer;
            border: 2px solid white;
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(210, 180, 140, 0.5);
        }
        
        .user-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 15px;
            width: 200px;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 100;
        }
        
        .user-info:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            top: 50px;
        }
        
        .user-dropdown p {
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .user-dropdown a {
            display: block;
            color: var(--primary);
            text-decoration: none;
            padding: 8px 0;
            transition: var(--transition);
        }
        
        .user-dropdown a:hover {
            color: var(--primary-light);
            padding-left: 5px;
        }
        
        .logout-btn {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 20px;
        }
        
        .logout-btn:hover {
            color: var(--secondary);
            background: rgba(255,255,255,0.1);
        }
        
        .container {
            display: flex;
            min-height: 100vh;
            padding-top: 70px;
        }
        
        /* Sidebar mejorado */
        .sidebar {
            width: 280px;
            background: white;
            padding: 2rem 1.5rem;
            box-shadow: var(--shadow);
            position: fixed;
            height: calc(100vh - 70px);
            overflow-y: auto;
            transition: var(--transition);
            z-index: 900;
        }
        
        .nav-menu {
            list-style: none;
            margin-top: 20px;
        }
        
        .nav-item {
            margin-bottom: 10px;
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .nav-link i {
            margin-right: 15px;
            color: var(--primary);
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }
        
        .nav-link:hover i, .nav-link.active i {
            color: white;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            right: 20px;
            width: 10px;
            height: 10px;
            background-color: var(--gold);
            border-radius: 50%;
            opacity: 0;
            transition: var(--transition);
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            opacity: 1;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px;
            transition: var(--transition);
        }
        
        /* Sección de bienvenida mejorada */
        .welcome-section {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/img/pattern.png') center/cover;
            opacity: 0.05;
            z-index: 0;
        }
        
        .welcome-title {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 2rem;
            position: relative;
        }
        
        .welcome-text {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            position: relative;
            max-width: 600px;
        }
        
        /* Tarjetas mejoradas */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 2rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: none;
        }
        
        .card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--gold));
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card:hover::after {
            height: 8px;
        }
        
        .card-title {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
        }
        
        .card-title i {
            margin-right: 15px;
            color: var(--gold);
            font-size: 1.8rem;
        }
        
        .card p {
            margin-bottom: 1.5rem;
            color: #555;
        }
        
        /* Formulario mejorado */
        .order-form .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }
        
        .order-form label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .order-form select, 
        .order-form input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid var(--secondary);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            appearance: none;
            background-color: white;
        }
        
        .order-form select:focus, 
        .order-form input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.2);
            outline: none;
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--primary);
            pointer-events: none;
        }
        
        .btn-submit {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4);
            background: linear-gradient(to right, var(--primary-light), var(--primary));
        }
        
        /* Tablas mejoradas */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 2rem;
            overflow-x: auto;
            margin-top: 2rem;
            position: relative;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(210, 180, 140, 0.3);
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: rgba(210, 180, 140, 0.05);
        }
        
        .status {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        
        .status-pendiente {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-preparando {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-completado {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-cancelado {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        /* Mensajes mejorados */
        .success-message {
            background-color: #D4EDDA;
            color: #155724;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid #155724;
            animation: fadeIn 0.5s ease-out;
        }
        
        .error-message {
            background-color: #F8D7DA;
            color: #721C24;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid #721C24;
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Footer mejorado */
        footer {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 4rem 2rem;
            margin-top: 50px;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/img/pattern-dark.png') center/cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            position: relative;
            z-index: 1;
        }
        
        .footer-column h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--gold);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            transition: var(--transition);
        }
        
        .social-link:hover {
            transform: translateY(-5px) scale(1.1);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
        }
        
        /* Botón de WhatsApp flotante */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .whatsapp-float a {
            background-color: #25D366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .whatsapp-float a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            top: 0;
            left: -100%;
            transform: skewX(-30deg);
            transition: var(--transition);
        }
        
        .whatsapp-float a:hover {
            transform: scale(1.1);
        }
        
        .whatsapp-float a:hover::after {
            left: 120%;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 1rem;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
            
            .welcome-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header mejorado -->
    <header class="header" id="mainHeader">
        <div class="logo">
            <img src="assets/img/logo.png" alt="Delicias Paisanas" style="width: 50px; height: auto;">
            <h1>Delicias Paisanas</h1>
        </div>
        <div class="user-nav">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                </div>
                <div class="user-dropdown">
                    <p><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></p>
                    <p style="color: #777; font-size: 0.9rem;"><?php echo htmlspecialchars($usuario['email']); ?></p>
                    <a href="#perfil"><i class="fas fa-user"></i> Mi perfil</a>
                    <a href="#configuracion"><i class="fas fa-cog"></i> Configuración</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Salir</span>
            </a>
        </div>
    </header>
    
    <div class="container">
        <!-- Sidebar mejorado -->
        <aside class="sidebar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#nuevo-pedido" class="nav-link">
                        <i class="fas fa-utensils"></i> Nuevo Pedido
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#historial" class="nav-link">
                        <i class="fas fa-history"></i> Historial
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#metodos-pago" class="nav-link">
                        <i class="fas fa-credit-card"></i> Métodos de Pago
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#perfil" class="nav-link">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#ayuda" class="nav-link">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#promociones" class="nav-link">
                        <i class="fas fa-tag"></i> Promociones
                    </a>
                </li>
            </ul>
            
            <div style="margin-top: 30px; padding: 20px; background: rgba(210, 180, 140, 0.1); border-radius: 10px;">
                <h4 style="color: var(--primary); margin-bottom: 15px; font-size: 1.1rem;">
                    <i class="fas fa-bell"></i> Notificaciones
                </h4>
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <div style="width: 8px; height: 8px; background-color: var(--gold); border-radius: 50%; margin-right: 10px;"></div>
                    <span style="font-size: 0.9rem;">Nuevo menú disponible</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 8px; height: 8px; background-color: var(--gold); border-radius: 50%; margin-right: 10px;"></div>
                    <span style="font-size: 0.9rem;">Promoción especial hoy</span>
                </div>
            </div>
        </aside>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message" data-aos="fade-down">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4>¡Pedido realizado con éxito!</h4>
                        <p>Tu pedido ha sido registrado y está siendo preparado.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message" data-aos="fade-down">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h4>Error en el pedido</h4>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Sección de bienvenida con animación -->
            <section class="welcome-section" data-aos="fade-up">
                <h2 class="welcome-title">Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                <p class="welcome-text">Desde tu panel de control puedes realizar nuevos pedidos, consultar tu historial y gestionar tu cuenta. ¡Explora todas las opciones!</p>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <div style="background: rgba(139, 69, 19, 0.1); padding: 10px 15px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-utensils" style="color: var(--primary);"></i>
                        <span>Menú del día disponible</span>
                    </div>
                    <div style="background: rgba(139, 69, 19, 0.1); padding: 10px 15px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-percentage" style="color: var(--primary);"></i>
                        <span>Promociones especiales</span>
                    </div>
                </div>
            </section>
            
            <!-- Tarjetas de acciones principales -->
            <div class="card-container">
                <div class="card" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="card-title"><i class="fas fa-utensils"></i> Realizar Pedido</h3>
                    <p>Explora nuestro menú gourmet y realiza tu pedido en pocos pasos. Disfruta de nuestros platos especiales preparados con ingredientes frescos.</p>
                    <a href="#nuevo-pedido" class="btn-submit" style="display: inline-flex; margin-top: 15px;">
                        <i class="fas fa-plus"></i> Nuevo Pedido
                    </a>
                </div>
                
                <div class="card" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="card-title"><i class="fas fa-history"></i> Historial</h3>
                    <p>Consulta todos tus pedidos anteriores y su estado actual. Revisa lo que has disfrutado y repite tus favoritos.</p>
                    <a href="#historial" class="btn-submit" style="display: inline-flex; margin-top: 15px;">
                        <i class="fas fa-list"></i> Ver Historial
                    </a>
                </div>
                
                <div class="card" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="card-title"><i class="fas fa-user"></i> Mi Perfil</h3>
                    <p>Actualiza tu información personal, preferencias y métodos de pago. Mantén tus datos siempre actualizados.</p>
                    <a href="#perfil" class="btn-submit" style="display: inline-flex; margin-top: 15px;">
                        <i class="fas fa-cog"></i> Configurar
                    </a>
                </div>
            </div>
            
            <!-- Formulario de nuevo pedido mejorado -->
            <section id="nuevo-pedido" class="table-container" data-aos="fade-up">
                <h2 class="card-title"><i class="fas fa-plus-circle"></i> Nuevo Pedido</h2>
                <form method="POST" action="index.php" class="order-form">
                    <input type="hidden" name="realizar_pedido" value="1">
                    
                    <div class="form-group">
                        <label for="menu">Selecciona un plato:</label>
                        <div class="select-wrapper">
                            <select id="menu" name="menu" required>
                                <option value="">-- Selecciona un plato --</option>
                                <option value="AREPA SUPREMA PAISANA - $18.000">AREPA SUPREMA PAISANA - $18.000</option>
                                <option value="CACHAPA REAL CON QUESO Y MIEL DE PAPELÓN - $16.500">CACHAPA REAL CON QUESO Y MIEL DE PAPELÓN - $16.500</option>
                                <option value="ARROZ CHAUFA DE CAMARONES - $65.500">ARROZ CHAUFA DE CAMARONES - $65.500</option>
                                <option value="RAVIOLES BELLENOS DE PLÁTANO MADURO Y QUESO - $52.900">RAVIOLES BELLENOS DE PLÁTANO MADURO Y QUESO - $52.900</option>
                                <option value="MEDALLONES DE LOMITO EN SALSA DE TAMARINDO - $68.000">MEDALLONES DE LOMITO EN SALSA DE TAMARINDO - $68.000</option>
                                <option value="PECHUGA DE POLLO EN SALSA DE MARACUYA - $58.000">PECHUGA DE POLLO EN SALSA DE MARACUYA - $58.000</option>
                                <option value="BENEDICTINOS CRIOLLOS - $16.000">BENEDICTINOS CRIOLLOS - $16.000</option>
                                <option value="BOL DE FRUTAS Y YOGUR ARTESANAL - $16.900">BOL DE FRUTAS Y YOGUR ARTESANAL - $16.900</option>
                                <option value="PESCADO A LA PARRILLA CON SALSA DE MARACUYA - $55.500">PESCADO A LA PARRILLA CON SALSA DE MARACUYA - $55.500</option>
                                <option value="POLLO AL HORNO CON COSTRA DE HIERBAS Y MIEL - $55.990">POLLO AL HORNO CON COSTRA DE HIERBAS Y MIEL - $55.990</option>
                                <option value="SALMÓN EN COSTRA DE HIERBAS - $72.000">SALMÓN EN COSTRA DE HIERBAS - $72.000</option>
                                <option value="RISSOTTO DE HONGOS SILVESTRES - $69.900">RISSOTTO DE HONGOS SILVESTRES - $69.900</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cantidad">Cantidad:</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="metodo_pago">Método de pago:</label>
                        <div class="select-wrapper">
                            <select id="metodo_pago" name="metodo_pago" required>
                                <option value="">-- Selecciona método de pago --</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta de crédito">Tarjeta de crédito</option>
                                <option value="Tarjeta de débito">Tarjeta de débito</option>
                                <option value="Transferencia bancaria">Transferencia bancaria</option>
                                <option value="Nequi">Nequi</option>
                                <option value="Daviplata">Daviplata</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit pulse">
                        <i class="fas fa-paper-plane"></i> Realizar Pedido
                    </button>
                </form>
            </section>
            
            <!-- Historial de pedidos mejorado -->
            <section id="historial" class="table-container" data-aos="fade-up">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="card-title"><i class="fas fa-history"></i> Historial de Pedidos</h2>
                    <div>
                        <button style="background: none; border: none; color: var(--primary); cursor: pointer; margin-right: 15px;">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button style="background: none; border: none; color: var(--primary); cursor: pointer;">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($pedidos)): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Total</th>
                                    <th>Método Pago</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(explode(' - $', $pedido['menu'])[0]); ?></td>
                                    <td><?php echo $pedido['cantidad']; ?></td>
                                    <td>$<?php echo number_format($pedido['precio'], 0, ',', '.'); ?></td>
                                    <td>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($pedido['metodo_pago'] ?? 'No especificado'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower($pedido['estado']); ?>">
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--secondary); margin-bottom: 20px;"></i>
                        <h3 style="color: var(--primary); margin-bottom: 10px;">No hay pedidos registrados</h3>
                        <p>Realiza tu primer pedido y aparecerá aquí</p>
                        <a href="#nuevo-pedido" class="btn-submit" style="display: inline-flex; margin-top: 20px;">
                            <i class="fas fa-plus"></i> Nuevo Pedido
                        </a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Métodos de pago mejorados -->
            <section id="metodos-pago" class="table-container" data-aos="fade-up">
                <h2 class="card-title"><i class="fas fa-credit-card"></i> Métodos de Pago</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: var(--transition);">
                        <div style="background: rgba(139, 69, 19, 0.1); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-money-bill-wave" style="color: var(--primary); font-size: 1.5rem;"></i>
                        </div>
                        <h3 style="color: var(--primary); margin-bottom: 10px;">Efectivo</h3>
                        <p style="color: #555;">Paga en efectivo al momento de recibir tu pedido.</p>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: var(--transition);">
                        <div style="background: rgba(139, 69, 19, 0.1); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-credit-card" style="color: var(--primary); font-size: 1.5rem;"></i>
                        </div>
                        <h3 style="color: var(--primary); margin-bottom: 10px;">Tarjeta</h3>
                        <p style="color: #555;">Aceptamos todas las tarjetas de crédito y débito.</p>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: var(--transition);">
                        <div style="background: rgba(139, 69, 19, 0.1); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fab fa-whatsapp" style="color: var(--primary); font-size: 1.5rem;"></i>
                        </div>
                        <h3 style="color: var(--primary); margin-bottom: 10px;">WhatsApp</h3>
                        <p style="color: #555;">Realiza tus pedidos y paga directamente por WhatsApp.</p>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: var(--transition);">
                        <div style="background: rgba(139, 69, 19, 0.1); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-wallet" style="color: var(--primary); font-size: 1.5rem;"></i>
                        </div>
                        <h3 style="color: var(--primary); margin-bottom: 10px;">Billeteras</h3>
                        <p style="color: #555;">Aceptamos Nequi, Daviplata y otras billeteras digitales.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <!-- Footer mejorado -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <img src="assets/img/logo.png" alt="Delicias Paisanas" style="width: 50px; height: auto; margin-right: 10px;">
                    <h3 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; color: white;">Delicias Paisanas</h3>
                </div>
                <p style="margin-bottom: 20px;">Cocina tradicional con un toque gourmet. Sabores auténticos que evocan los mejores recuerdos de la cocina de casa.</p>
                <div style="display: flex; gap: 15px;">
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fas fa-phone-alt"></i></a>
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fas fa-envelope"></i></a>
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fas fa-map-marker-alt"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Horarios</h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span>Lunes - Viernes</span>
                        <span>7:00 AM - 10:00 PM</span>
                    </li>
                    <li style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span>Sábados</span>
                        <span>8:00 AM - 11:00 PM</span>
                    </li>
                    <li style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span>Domingos</span>
                        <span>9:00 AM - 9:00 PM</span>
                    </li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Síguenos</h3>
                <p style="margin-bottom: 20px;">Conéctate con nosotros en redes sociales y mantente al día con nuestras promociones y novedades.</p>
                <div class="social-links">
                    <a href="#" class="social-link" style="background-color: #3b5998;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link" style="background-color: #1da1f2;"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link" style="background-color: #e1306c;"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link" style="background-color: #ff0000;"><i class="fab fa-youtube"></i></a>
                    <a href="https://wa.me/573143535896" class="social-link" style="background-color: #25d366;"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Newsletter</h3>
                <p style="margin-bottom: 15px;">Suscríbete para recibir nuestras promociones y menús especiales.</p>
                <form style="display: flex; margin-bottom: 20px;">
                    <input type="email" placeholder="Tu correo electrónico" style="flex: 1; padding: 12px; border: none; border-radius: 5px 0 0 5px; font-size: 0.9rem;">
                    <button type="submit" style="background-color: var(--gold); color: white; border: none; padding: 0 15px; border-radius: 0 5px 5px 0; cursor: pointer; font-size: 1.1rem;">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <img src="https://via.placeholder.com/60x40?text=Visa" alt="Visa" style="border-radius: 5px; height: 30px;">
                    <img src="https://via.placeholder.com/60x40?text=Mastercard" alt="Mastercard" style="border-radius: 5px; height: 30px;">
                    <img src="https://via.placeholder.com/60x40?text=Amex" alt="American Express" style="border-radius: 5px; height: 30px;">
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2023 Delicias Paisanas. Todos los derechos reservados. | 
               <a href="#" style="color: white; text-decoration: none;">Políticas de Privacidad</a> | 
               <a href="#" style="color: white; text-decoration: none;">Términos y Condiciones</a></p>
            <p style="margin-top: 10px; font-size: 0.8rem;">Diseñado con <i class="fas fa-heart" style="color: #ff6b6b;"></i> para amantes de la buena comida</p>
        </div>
    </footer>
    
    <!-- Botón flotante de WhatsApp -->
    <div class="whatsapp-float">
        <a href="https://wa.me/573143535896">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>
    
    <!-- Scripts para animaciones y funcionalidades -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Inicializar animaciones
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: false
        });
        
        // Efecto de scroll en el header
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Smooth scrolling para los enlaces del menú
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                    
                    // Actualizar clase activa en el menú
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });
        
        // Mostrar/ocultar menú en mobile
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.createElement('div');
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            menuToggle.style.position = 'fixed';
            menuToggle.style.bottom = '30px';
            menuToggle.style.left = '30px';
            menuToggle.style.width = '60px';
            menuToggle.style.height = '60px';
            menuToggle.style.backgroundColor = 'var(--primary)';
            menuToggle.style.color = 'white';
            menuToggle.style.borderRadius = '50%';
            menuToggle.style.display = 'flex';
            menuToggle.style.alignItems = 'center';
            menuToggle.style.justifyContent = 'center';
            menuToggle.style.fontSize = '1.5rem';
            menuToggle.style.zIndex = '1000';
            menuToggle.style.cursor = 'pointer';
            menuToggle.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
            menuToggle.style.transition = 'var(--transition)';
            
            menuToggle.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
            
            if (window.innerWidth <= 992) {
                document.body.appendChild(menuToggle);
            }
            
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 992) {
                    document.querySelector('.sidebar').classList.remove('active');
                    if (!document.body.contains(menuToggle)) {
                        document.body.appendChild(menuToggle);
                    }
                } else {
                    if (document.body.contains(menuToggle)) {
                        document.body.removeChild(menuToggle);
                    }
                    document.querySelector('.sidebar').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>