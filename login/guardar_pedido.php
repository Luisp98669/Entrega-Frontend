<?php
session_start();
require 'includes/config.php';
require 'includes/db.php';


// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso no autorizado");
}

// Procesar pedido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $menu = $_POST['menu'];
    $cantidad = (int)$_POST['cantidad'];
    $usuario_id = $_SESSION['usuario_id'];
    
    // Extraer precio del menú seleccionado
    preg_match('/\$(.*)$/', $menu, $matches);
    $precio = isset($matches[1]) ? (float)str_replace('.', '', $matches[1]) : 0;
    $total = $precio * $cantidad;
    
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, menu, cantidad, precio, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $menu, $cantidad, $precio, $total]);
        
        // Registrar actividad
        registrarActividad($usuario_id, "Nuevo pedido: $menu (x$cantidad)", $pdo);
        
        echo "Pedido registrado exitosamente. Total: $" . number_format($total, 0, ',', '.');
    } catch (PDOException $e) {
        error_log("Error al guardar pedido: " . $e->getMessage());
        echo "Error al procesar el pedido";
    }
} else {
    header("Location: index.php");
}
?>