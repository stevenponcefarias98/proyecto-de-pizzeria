<?php
declare(strict_types=1);
// Ejecutar solo desde consola: php crear_admin.php "Nombre" correo@ejemplo.com "ContraseñaLarga"
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/bootstrap.php';
[$script, $name, $email, $password] = array_pad($argv, 4, '');
$name = trim($name);
$email = strtolower(trim($email));
if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php crear_admin.php \"Nombre\" correo@ejemplo.com \"Contraseña de 12 o más caracteres\"\n");
    exit(1);
}
if (one('SELECT id FROM usuarios WHERE correo=?', [$email])) {
    fwrite(STDERR, "El correo ya existe.\n");
    exit(1);
}
db()->prepare('INSERT INTO usuarios(nombre,correo,contrasena,rol_id) VALUES(?,?,?,1)')
    ->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
echo "Administrador creado. Ya puedes iniciar sesión.\n";
