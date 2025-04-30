<?php
// Incluir archivos de configuración y funciones
include 'includes/config.php';
include 'includes/db.php';
include 'includes/functions.php';

// Función para crear las tablas si no existen
function crearTablasSiNoExisten() {
    try {
        $pdo = db_connect();
        
        // Verificar y crear tabla usuarios
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            rol ENUM('admin', 'gerente', 'empleado') NOT NULL DEFAULT 'empleado',
            pregunta_seguridad VARCHAR(255) NOT NULL,
            respuesta_seguridad VARCHAR(255) NOT NULL,
            intentos_fallidos INT(1) NOT NULL DEFAULT 0,
            cuenta_bloqueada BOOLEAN NOT NULL DEFAULT FALSE,
            ultimo_acceso DATETIME DEFAULT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Verificar y crear tabla registro_actividad
        $pdo->exec("CREATE TABLE IF NOT EXISTS registro_actividad (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT(11) DEFAULT NULL,
            accion VARCHAR(255) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        return true;
    } catch (PDOException $e) {
        error_log("Error al crear tablas: " . $e->getMessage());
        return false;
    }
}

// Función para registrar un nuevo usuario
function registrarUsuario($username, $password, $nombre, $apellido, $email, $rol, $pregunta_seguridad, $respuesta_seguridad) {
    try {
        crearTablasSiNoExisten();
        $pdo = db_connect();
        
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // El usuario o email ya existe
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                // Registrar intento de registro con usuario existente
                $ip = $_SERVER['REMOTE_ADDR'];
                $accion = "Intento de registro con usuario/email existente: $username / $email";
                $stmt = $pdo->prepare("INSERT INTO registro_actividad (accion, ip) VALUES (:accion, :ip)");
                $stmt->bindParam(':accion', $accion);
                $stmt->bindParam(':ip', $ip);
                $stmt->execute();
                
                return ['success' => false, 'message' => 'El nombre de usuario o email ya está en uso.'];
            }
        }
        
        // El usuario no existe, proceder con el registro
        $password_hash = hashPassword($password);
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, apellido, email, rol, pregunta_seguridad, respuesta_seguridad) 
                              VALUES (:username, :password, :nombre, :apellido, :email, :rol, :pregunta_seguridad, :respuesta_seguridad)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':pregunta_seguridad', $pregunta_seguridad);
        $stmt->bindParam(':respuesta_seguridad', $respuesta_seguridad);
        
        if ($stmt->execute()) {
            $usuario_id = $pdo->lastInsertId();
            
            // Registrar actividad de registro exitoso
            $ip = $_SERVER['REMOTE_ADDR'];
            $accion = "Registro exitoso de nuevo usuario: $username";
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (usuario_id, accion, ip) VALUES (:usuario_id, :accion, :ip)");
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Usuario registrado correctamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar el usuario.'];
        }
    } catch (PDOException $e) {
        error_log("Error en registro: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al registrar el usuario: ' . $e->getMessage()];
    }
}

// Función para validar login
function validarLogin($username, $password) {
    try {
        crearTablasSiNoExisten();
        $pdo = db_connect();
        
        // Buscar el usuario en la base de datos
        $stmt = $pdo->prepare("SELECT id, username, password, nombre, apellido, rol, cuenta_bloqueada, intentos_fallidos FROM usuarios WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Registrar el intento de inicio de sesión
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!$usuario) {
            // Usuario no existe
            $accion = "Intento de inicio de sesión con usuario inexistente: $username";
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (accion, ip) VALUES (:accion, :ip)");
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos.'];
        }
        
        // Verificar si la cuenta está bloqueada
        if ($usuario['cuenta_bloqueada']) {
            $accion = "Intento de inicio de sesión en cuenta bloqueada: $username";
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (usuario_id, accion, ip) VALUES (:usuario_id, :accion, :ip)");
            $stmt->bindParam(':usuario_id', $usuario['id']);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            return ['success' => false, 'message' => 'Tu cuenta está bloqueada. Por favor, contacta con el administrador.'];
        }
        
        // Verificar la contraseña
        if (verifyPassword($password, $usuario['password'])) {
            // Login exitoso
            
            // Resetear intentos fallidos
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id = :id");
            $stmt->bindParam(':id', $usuario['id']);
            $stmt->execute();
            
            // Registrar login exitoso
            $accion = "Inicio de sesión exitoso";
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (usuario_id, accion, ip) VALUES (:usuario_id, :accion, :ip)");
            $stmt->bindParam(':usuario_id', $usuario['id']);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            // Iniciar sesión
            session_start();
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
            $_SESSION['usuario_usuario'] = $usuario['username'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            
            return [
                'success' => true, 
                'message' => 'Login exitoso.', 
                'usuario' => [
                    'id' => $usuario['id'],
                    'username' => $usuario['username'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'rol' => $usuario['rol']
                ]
            ];
        } else {
            // Contraseña incorrecta
            
            // Incrementar intentos fallidos
            $intentos_fallidos = $usuario['intentos_fallidos'] + 1;
            $cuenta_bloqueada = ($intentos_fallidos >= 5) ? true : false;
            
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = :intentos_fallidos, cuenta_bloqueada = :cuenta_bloqueada WHERE id = :id");
            $stmt->bindParam(':intentos_fallidos', $intentos_fallidos);
            $stmt->bindParam(':cuenta_bloqueada', $cuenta_bloqueada, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $usuario['id']);
            $stmt->execute();
            
            // Registrar intento fallido
            $accion = "Intento de inicio de sesión fallido (contraseña incorrecta)";
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (usuario_id, accion, ip) VALUES (:usuario_id, :accion, :ip)");
            $stmt->bindParam(':usuario_id', $usuario['id']);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            $intentos_restantes = 5 - $intentos_fallidos;
            $mensaje = 'Usuario o contraseña incorrectos.';
            
            if ($cuenta_bloqueada) {
                $mensaje = 'Tu cuenta ha sido bloqueada por múltiples intentos fallidos. Contacta al administrador.';
            } elseif ($intentos_restantes <= 3) {
                $mensaje .= " Te quedan $intentos_restantes intentos antes de que tu cuenta sea bloqueada.";
            }
            
            return ['success' => false, 'message' => $mensaje];
        }
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al iniciar sesión: ' . $e->getMessage()];
    }
}

// Función para recuperar contraseña con pregunta de seguridad
function recuperarContrasena($username, $respuesta_seguridad) {
    try {
        crearTablasSiNoExisten();
        $pdo = db_connect();
        
        // Buscar usuario y verificar respuesta de seguridad
        $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE username = :username AND respuesta_seguridad = :respuesta_seguridad");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':respuesta_seguridad', $respuesta_seguridad);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            // Generar nueva contraseña aleatoria
            $nueva_contrasena = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
            $nueva_contrasena_hash = hashPassword($nueva_contrasena);
            
            // Actualizar contraseña en la base de datos
            $stmt = $pdo->prepare("UPDATE usuarios SET password = :password, intentos_fallidos = 0, cuenta_bloqueada = FALSE WHERE id = :id");
            $stmt->bindParam(':password', $nueva_contrasena_hash);
            $stmt->bindParam(':id', $usuario['id']);
            $stmt->execute();
            
            // Registrar cambio de contraseña
            $ip = $_SERVER['REMOTE_ADDR'];
            $accion = "Recuperación de contraseña exitosa";
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (usuario_id, accion, ip) VALUES (:usuario_id, :accion, :ip)");
            $stmt->bindParam(':usuario_id', $usuario['id']);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Contraseña recuperada con éxito.',
                'nueva_contrasena' => $nueva_contrasena
            ];
        } else {
            // Respuesta de seguridad incorrecta o usuario no existe
            $accion = "Intento fallido de recuperación de contraseña para: $username";
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare("INSERT INTO registro_actividad (accion, ip) VALUES (:accion, :ip)");
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            return ['success' => false, 'message' => 'Usuario o respuesta de seguridad incorrectos.'];
        }
    } catch (PDOException $e) {
        error_log("Error en recuperación de contraseña: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al recuperar la contraseña: ' . $e->getMessage()];
    }
}

// -------------------- MANEJADORES DE PETICIONES --------------------

// Proceso de registro
if ($_SERVER['PHP_SELF'] == '/registro.php' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizar entradas
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // No sanitizamos contraseña para evitar alterarla
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rol = filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_STRING);
    $pregunta_seguridad = filter_input(INPUT_POST, 'pregunta_seguridad', FILTER_SANITIZE_STRING);
    $respuesta_seguridad = filter_input(INPUT_POST, 'respuesta_seguridad', FILTER_SANITIZE_STRING);
    
    // Validaciones básicas
    if (empty($username) || empty($password) || empty($nombre) || empty($email) || empty($pregunta_seguridad) || empty($respuesta_seguridad)) {
        $error_registro = "Todos los campos son obligatorios.";
    } else {
        // Procesar registro
        $resultado = registrarUsuario($username, $password, $nombre, $apellido, $email, $rol, $pregunta_seguridad, $respuesta_seguridad);
        
        if ($resultado['success']) {
            header("Location: index.php?registro=success");
            exit;
        } else {
            $error_registro = $resultado['message'];
        }
    }
}

// Proceso de login
if ($_SERVER['PHP_SELF'] == '/index.php' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start();
    
    // Sanitizar entradas
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // No sanitizamos contraseña
    
    // Validar login
    $resultado = validarLogin($username, $password);
    
    if ($resultado['success']) {
        // Redireccionar a dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        $error_login = $resultado['message'];
    }
}

// Proceso de recuperación de contraseña
if ($_SERVER['PHP_SELF'] == '/recuperar_contrasena.php' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizar entradas
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $respuesta_seguridad = filter_input(INPUT_POST, 'respuesta_seguridad', FILTER_SANITIZE_STRING);
    
    // Procesar recuperación
    $resultado = recuperarContrasena($username, $respuesta_seguridad);
    
    if ($resultado['success']) {
        $success_recuperacion = true;
        $nueva_contrasena = $resultado['nueva_contrasena'];
    } else {
        $error_recuperacion = $resultado['message'];
    }
}

// Asegurarse de que las tablas existen cuando se carga cualquier página
crearTablasSiNoExisten();
?>