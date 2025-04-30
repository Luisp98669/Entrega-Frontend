<?php
// Mostrar todos los errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuraciones
include 'includes/config.php';
include 'includes/db.php';

// Iniciar temporizador para medir rendimiento
$start_time = microtime(true);

try {
    // Establecer conexión
    $pdo = db_connect();
    $connection_status = true;
    $connection_message = "¡Conexión exitosa a la base de datos!";
    
    // Obtener información del servidor
    $server_info = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
    $server_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $driver_name = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    // Verificar usuario admin
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE username = 'Anders'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar otras tablas importantes
    $tables = ['usuarios', 'registro_actividad', 'pedidos'];
    $table_status = [];
    
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            $table_status[$table] = ['exists' => true, 'rows' => $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()];
        } catch (PDOException $e) {
            $table_status[$table] = ['exists' => false, 'error' => $e->getMessage()];
        }
    }
    
} catch (PDOException $e) {
    $connection_status = false;
    $connection_message = "Error de conexión: " . $e->getMessage();
}

// Calcular tiempo de ejecución
$execution_time = round((microtime(true) - $start_time) * 1000, 2); // en milisegundos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Base de Datos | Delicias Paisanas</title>
    <style>
        :root {
            --primary-color: #8B4513;
            --secondary-color: #D2B48C;
            --accent-color: #5C3317;
            --light-color: #FFF8DC;
            --error-color: #D32F2F;
            --success-color: #388E3C;
            --info-color: #31708f;
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
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        h1, h2, h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        h1 {
            text-align: center;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }
        
        .status-box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 5px solid;
        }
        
        .success {
            background-color: #dff0d8;
            border-color: var(--success-color);
            color: var(--success-color);
        }
        
        .error {
            background-color: #f2dede;
            border-color: var(--error-color);
            color: var(--error-color);
        }
        
        .info {
            background-color: #d9edf7;
            border-color: var(--info-color);
            color: var(--info-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table, th, td {
            border: 1px solid var(--secondary-color);
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: var(--secondary-color);
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: rgba(210, 180, 140, 0.1);
        }
        
        .stats {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed var(--secondary-color);
        }
        
        .stat-box {
            text-align: center;
            padding: 10px;
        }
        
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prueba de Conexión a Base de Datos</h1>
        
        <div class="status-box <?php echo $connection_status ? 'success' : 'error'; ?>">
            <h2>Estado de Conexión</h2>
            <p><?php echo $connection_message; ?></p>
            <?php if ($connection_status): ?>
                <p><strong>Servidor:</strong> <?php echo $server_info; ?></p>
                <p><strong>Versión:</strong> <?php echo $server_version; ?></p>
                <p><strong>Controlador:</strong> <?php echo $driver_name; ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($connection_status): ?>
            <div class="status-box info">
                <h2>Tablas del Sistema</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Estado</th>
                            <th>Registros</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($table_status as $table => $status): ?>
                            <tr>
                                <td><?php echo $table; ?></td>
                                <td>
                                    <?php if ($status['exists']): ?>
                                        <span style="color: var(--success-color);">✓ Existente</span>
                                    <?php else: ?>
                                        <span style="color: var(--error-color);">✗ No encontrada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $status['exists'] ? $status['rows'] : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php echo $status['exists'] ? 'OK' : htmlspecialchars($status['error']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="status-box info">
                <h2>Usuario Administrador</h2>
                <?php if ($Anders): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $Anders['id']; ?></td>
                                <td><?php echo htmlspecialchars($Anders['username']); ?></td>
                                <td><?php echo htmlspecialchars($Anders['nombre'] . ' ' . $Anders['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($Anders['email']); ?></td>
                                <td><?php echo htmlspecialchars($Anders['rol']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--error-color);">No se encontró el usuario administrador en la base de datos.</p>
                <?php endif; ?>
            </div>
            
            <div class="stats">
                <div class="stat-box">
                    <h3>Tiempo de Ejecución</h3>
                    <p><?php echo $execution_time; ?> ms</p>
                </div>
                <div class="stat-box">
                    <h3>Fecha de Prueba</h3>
                    <p><?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Versión PHP</h3>
                    <p><?php echo phpversion(); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>