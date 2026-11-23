<?php
declare(strict_types=1);

function login_page(): void
{
    if (user()) go('pedidos');
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $account = one('SELECT u.id,u.nombre,u.correo,u.contrasena,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.correo=? AND u.activo=1', [trim((string) ($_POST['correo'] ?? ''))]);
        if ($account && password_verify((string) ($_POST['contrasena'] ?? ''), $account['contrasena'])) {
            session_regenerate_id(true);
            unset($account['contrasena']);
            $_SESSION['user'] = $account;
            go('pedidos', 'Bienvenido, ' . $account['nombre'] . '.');
        }
        $error = 'Correo o contraseña incorrectos.';
    }
    page('Ingresar', function () use ($error) { ?>
      <div class="row justify-content-center"><div class="col-md-6 col-lg-5"><div class="card p-4">
      <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
      <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <label class="form-label">Correo</label><input class="form-control mb-3" type="email" name="correo" required autocomplete="username">
      <label class="form-label">Contraseña</label><input class="form-control mb-4" type="password" name="contrasena" required autocomplete="current-password">
      <button class="btn btn-primary w-100">Ingresar</button></form>
      <p class="mt-3 mb-0">¿No tienes cuenta? <a href="<?= h(url('registro')) ?>">Regístrate como cliente</a>.</p>
      </div></div></div>
    <?php });
}

function register_page(): void
{
    if (user()) go('pedidos');
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $name = trim((string) ($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string) ($_POST['correo'] ?? '')));
        $pass = (string) ($_POST['contrasena'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
            $error = 'Indica un nombre, un correo válido y una contraseña de al menos 8 caracteres.';
        } elseif (one('SELECT id FROM usuarios WHERE correo=?', [$email])) {
            $error = 'Ese correo ya está registrado.';
        } else {
            db()->prepare('INSERT INTO usuarios(nombre,correo,contrasena,rol_id,telefono,direccion) VALUES(?,?,?,3,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), trim((string) ($_POST['telefono'] ?? '')), trim((string) ($_POST['direccion'] ?? ''))]);
            go('login', 'Cuenta creada. Ya puedes ingresar.');
        }
    }
    page('Crear cuenta de cliente', function () use ($error) { ?>
      <div class="row justify-content-center"><div class="col-lg-6"><div class="card p-4">
      <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
      <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <label class="form-label">Nombre</label><input class="form-control mb-3" name="nombre" maxlength="80" required value="<?= h($_POST['nombre'] ?? '') ?>">
      <label class="form-label">Correo</label><input class="form-control mb-3" type="email" name="correo" required value="<?= h($_POST['correo'] ?? '') ?>">
      <label class="form-label">Teléfono</label><input class="form-control mb-3" name="telefono" maxlength="30" value="<?= h($_POST['telefono'] ?? '') ?>">
      <label class="form-label">Dirección de entrega</label><input class="form-control mb-3" name="direccion" maxlength="255" value="<?= h($_POST['direccion'] ?? '') ?>">
      <label class="form-label">Contraseña (mínimo 8 caracteres)</label><input class="form-control mb-4" type="password" name="contrasena" minlength="8" required autocomplete="new-password">
      <button class="btn btn-primary w-100">Crear cuenta</button></form></div></div></div>
    <?php });
}

function logout_action(): void
{
    require_role();
    post_only();
    $_SESSION = [];
    session_regenerate_id(true);
    go('inicio', 'Sesión cerrada.');
}

