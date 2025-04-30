<?php
function hashPassword($password) {
return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
function verifyPassword($password, $hash) {
return password_verify($password, $hash);
}
function verificarUsuario($username, $pdo) {
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username");
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
return $stmt->fetch(PDO::FETCH_ASSOC);
}
function registrarIntentoFallido($username, $pdo) {
$pdo->beginTransaction();
try {
// Incrementar intentos
$stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE username = :username");
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
// Verificar si debe bloquearse
$usuario = verificarUsuario($username, $pdo);
if ($usuario && $usuario['intentos_fallidos'] >= 5) {
$stmt = $pdo->prepare("UPDATE usuarios SET cuenta_bloqueada = TRUE WHERE username = :username");
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
 }
$pdo->commit();
 } catch (Exception $e) {
$pdo->rollBack();
 error_log("Error en registrarIntentoFallido: " . $e->getMessage());
 }
}
function registrarActividad($usuarioId, $accion, $pdo) {
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("INSERT INTO registro_actividad (usuario_id, accion, ip) VALUES (:usuario_id, :accion, :ip)");
$stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
$stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
$stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
$stmt->execute();
}
?>

