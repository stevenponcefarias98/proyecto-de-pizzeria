<?php
declare(strict_types=1);

// Gestiona productos, inventario, clientes, personal, gastos y estados de pedidos.
function products_page(): void
{
    require_role('Administrador');
    $edit = isset($_GET['id']) ? one('SELECT * FROM productos WHERE id=?', [(int) $_GET['id']]) : null;
    $products = rows('SELECT * FROM productos ORDER BY activo DESC,categoria,nombre');
    page('Productos e inventario', function () use ($edit,$products) { ?>
      <div class="row g-4"><div class="col-lg-4"><div class="card p-4"><h2 class="h5"><?= $edit ? 'Editar producto' : 'Nuevo producto' ?></h2>
      <form method="post" action="<?= h(url('guardar-producto')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <label class="form-label">Nombre</label><input class="form-control mb-2" name="nombre" maxlength="80" required value="<?= h($edit['nombre'] ?? '') ?>">
      <label class="form-label">Descripción</label><textarea class="form-control mb-2" name="descripcion" maxlength="255" required><?= h($edit['descripcion'] ?? '') ?></textarea>
      <label class="form-label">Categoría</label><input class="form-control mb-2" name="categoria" maxlength="40" required value="<?= h($edit['categoria'] ?? 'Pizza') ?>">
      <label class="form-label">Precio USD</label><input class="form-control mb-2" name="precio" type="number" step="0.01" min="0.01" max="999999.99" required value="<?= h($edit['precio'] ?? '') ?>">
      <label class="form-label">Stock</label><input class="form-control mb-3" name="stock" type="number" min="0" max="999999" required value="<?= h($edit['stock'] ?? 0) ?>">
      <button class="btn btn-primary"><?= $edit ? 'Guardar cambios' : 'Añadir producto' ?></button><?php if ($edit): ?> <a href="<?= h(url('productos')) ?>">Cancelar</a><?php endif; ?></form></div></div>
      <div class="col-lg-8"><div class="card p-3 table-responsive"><table class="table"><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
      <?php foreach ($products as $p): ?><tr><td><strong><?= h($p['nombre']) ?></strong><br><small><?= h($p['categoria']) ?></small></td><td><?= h(money($p['precio'])) ?></td><td><?= (int) $p['stock'] ?></td><td><?= $p['activo'] ? 'Visible' : 'Oculto' ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= h(url('productos')) ?>&id=<?= (int) $p['id'] ?>">Editar</a>
      <form class="d-inline" method="post" action="<?= h(url('eliminar-producto')) ?>" data-confirm="¿Cambiar la visibilidad de este producto?"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn-sm btn-outline-secondary"><?= $p['activo'] ? 'Ocultar' : 'Mostrar' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <?php });
}

function save_product_action(): void
{
    require_role('Administrador'); post_only();
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['nombre'] ?? ''));
    $description = trim((string) ($_POST['descripcion'] ?? ''));
    $category = trim((string) ($_POST['categoria'] ?? ''));
    $price = (string) ($_POST['precio'] ?? '');
    $stock = (string) ($_POST['stock'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || $description === '' || mb_strlen($description) > 255 || $category === '' || mb_strlen($category) > 40 || !preg_match('/^\d{1,6}(\.\d{1,2})?$/', $price) || (float) $price <= 0 || !ctype_digit($stock) || (int) $stock > 999999) fail('Revisa los datos del producto.', 'productos');
    if ($id) {
        db()->prepare('UPDATE productos SET nombre=?,descripcion=?,categoria=?,precio=?,stock=? WHERE id=?')->execute([$name,$description,$category,$price,$stock,$id]);
    } else {
        db()->prepare('INSERT INTO productos(nombre,descripcion,categoria,precio,stock) VALUES(?,?,?,?,?)')->execute([$name,$description,$category,$price,$stock]);
    }
    go('productos', 'Producto guardado.');
}

function delete_product_action(): void
{
    require_role('Administrador'); post_only();
    db()->prepare('UPDATE productos SET activo=1-activo WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
    go('productos', 'Visibilidad actualizada.');
}

function customers_page(): void
{
    require_role('Administrador','Empleado');
    $edit = isset($_GET['id']) ? one('SELECT id,nombre,correo,telefono,direccion,activo FROM usuarios WHERE id=? AND rol_id=3', [(int) $_GET['id']]) : null;
    $clients = rows('SELECT u.id,u.nombre,u.correo,u.telefono,u.direccion,u.activo,COUNT(p.id) AS pedidos FROM usuarios u LEFT JOIN pedidos p ON p.cliente_id=u.id WHERE u.rol_id=3 GROUP BY u.id ORDER BY u.nombre');
    page('Clientes', function () use ($edit,$clients) { ?>
      <div class="row g-4"><div class="col-lg-4"><div class="card p-4"><h2 class="h5"><?= $edit ? 'Editar cliente' : 'Añadir cliente' ?></h2>
      <form method="post" action="<?= h(url('guardar-cliente')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <label class="form-label">Nombre</label><input class="form-control mb-2" name="nombre" maxlength="80" required value="<?= h($edit['nombre'] ?? '') ?>">
      <label class="form-label">Correo</label><input class="form-control mb-2" type="email" name="correo" required value="<?= h($edit['correo'] ?? '') ?>">
      <label class="form-label">Teléfono</label><input class="form-control mb-2" name="telefono" maxlength="30" value="<?= h($edit['telefono'] ?? '') ?>">
      <label class="form-label">Dirección</label><input class="form-control mb-2" name="direccion" maxlength="255" value="<?= h($edit['direccion'] ?? '') ?>">
      <?php if (!$edit): ?><label class="form-label">Contraseña inicial</label><input class="form-control mb-3" type="password" name="contrasena" minlength="8" required><?php else: ?><div class="form-check my-3"><input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" <?= $edit['activo'] ? 'checked' : '' ?>><label class="form-check-label" for="activo">Cuenta activa</label></div><?php endif; ?>
      <button class="btn btn-primary">Guardar</button></form></div></div><div class="col-lg-8"><div class="card p-3 table-responsive"><table class="table"><thead><tr><th>Cliente</th><th>Correo</th><th>Pedidos</th><th>Estado</th><th></th></tr></thead><tbody>
      <?php foreach ($clients as $c): ?><tr><td><?= h($c['nombre']) ?></td><td><?= h($c['correo']) ?></td><td><?= (int) $c['pedidos'] ?></td><td><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></td><td><a href="<?= h(url('clientes')) ?>&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <?php });
}

function save_customer_action(): void
{
    require_role('Administrador','Empleado'); post_only();
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['nombre'] ?? ''));
    $email = strtolower(trim((string) ($_POST['correo'] ?? '')));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || !filter_var($email, FILTER_VALIDATE_EMAIL) || one('SELECT id FROM usuarios WHERE correo=? AND id<>?', [$email,$id])) fail('Nombre o correo no válido o ya registrado.', 'clientes');
    $phone = mb_substr(trim((string) ($_POST['telefono'] ?? '')), 0, 30);
    $address = mb_substr(trim((string) ($_POST['direccion'] ?? '')), 0, 255);
    if ($id) {
        if (!one('SELECT id FROM usuarios WHERE id=? AND rol_id=3', [$id])) fail('Cliente no encontrado.', 'clientes');
        db()->prepare('UPDATE usuarios SET nombre=?,correo=?,telefono=?,direccion=?,activo=? WHERE id=? AND rol_id=3')->execute([$name,$email,$phone,$address,isset($_POST['activo']) ? 1 : 0,$id]);
    } else {
        $pass = (string) ($_POST['contrasena'] ?? '');
        if (strlen($pass) < 8) fail('La contraseña debe tener al menos 8 caracteres.', 'clientes');
        db()->prepare('INSERT INTO usuarios(nombre,correo,telefono,direccion,contrasena,rol_id) VALUES(?,?,?,?,?,3)')->execute([$name,$email,$phone,$address,password_hash($pass,PASSWORD_DEFAULT)]);
    }
    go('clientes', 'Cliente guardado.');
}

function finance_page(): void
{
    require_role('Administrador');
    $income = one("SELECT COALESCE(SUM(total),0) AS total FROM pedidos WHERE estado='Entregado'")['total'];
    $expenses = one('SELECT COALESCE(SUM(monto),0) AS total FROM gastos')['total'];
    $byMonth = rows("SELECT DATE_FORMAT(fecha,'%Y-%m') AS mes, SUM(total) AS ingresos FROM pedidos WHERE estado='Entregado' GROUP BY DATE_FORMAT(fecha,'%Y-%m') ORDER BY mes DESC LIMIT 12");
    $list = rows('SELECT * FROM gastos ORDER BY fecha DESC,id DESC LIMIT 100');
    page('Finanzas', function () use ($income,$expenses,$byMonth,$list) { ?>
      <p class="text-secondary">Los ingresos incluyen únicamente pedidos entregados. Los gastos se registran por separado.</p>
      <div class="row g-3 mb-4"><div class="col-md-4"><div class="card p-4"><small>Ingresos</small><div class="metric text-success"><?= h(money($income)) ?></div></div></div>
      <div class="col-md-4"><div class="card p-4"><small>Gastos</small><div class="metric text-danger"><?= h(money($expenses)) ?></div></div></div>
      <div class="col-md-4"><div class="card p-4"><small>Balance</small><div class="metric"><?= h(money((float) $income - (float) $expenses)) ?></div></div></div></div>
      <div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Registrar gasto</h2><form method="post" action="<?= h(url('guardar-gasto')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <label class="form-label">Fecha</label><input class="form-control mb-2" type="date" name="fecha" value="<?= h(date('Y-m-d')) ?>" required>
      <label class="form-label">Concepto</label><input class="form-control mb-2" name="concepto" maxlength="120" required>
      <label class="form-label">Monto USD</label><input class="form-control mb-3" type="number" step="0.01" min="0.01" name="monto" required><button class="btn btn-primary">Registrar</button></form></div>
      <div class="card p-4 mt-4"><h2 class="h5">Ingresos por mes</h2><?php foreach ($byMonth as $m): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= h($m['mes']) ?></span><strong><?= h(money($m['ingresos'])) ?></strong></div><?php endforeach; ?></div></div>
      <div class="col-lg-7"><div class="card p-3 table-responsive"><h2 class="h5 p-2">Gastos recientes</h2><table class="table"><thead><tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th></th></tr></thead><tbody>
      <?php foreach ($list as $g): ?><tr><td><?= h($g['fecha']) ?></td><td><?= h($g['concepto']) ?></td><td><?= h(money($g['monto'])) ?></td><td><form method="post" action="<?= h(url('eliminar-gasto')) ?>" data-confirm="¿Eliminar este gasto?"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <?php });
}

function expense_action(): void
{
    require_role('Administrador'); post_only();
    $date = (string) ($_POST['fecha'] ?? ''); $amount = (string) ($_POST['monto'] ?? ''); $name = trim((string) ($_POST['concepto'] ?? ''));
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date || $name === '' || mb_strlen($name) > 120 || !preg_match('/^\d{1,6}(\.\d{1,2})?$/', $amount) || (float) $amount <= 0) fail('Revisa los datos del gasto.', 'finanzas');
    db()->prepare('INSERT INTO gastos(fecha,concepto,monto) VALUES(?,?,?)')->execute([$date,$name,$amount]);
    go('finanzas', 'Gasto registrado.');
}

function delete_expense_action(): void
{
    require_role('Administrador'); post_only();
    db()->prepare('DELETE FROM gastos WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
    go('finanzas', 'Gasto eliminado.');
}

function users_page(): void
{
    require_role('Administrador');
    $staff = rows('SELECT u.id,u.nombre,u.correo,u.activo,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.rol_id IN (1,2) ORDER BY u.id');
    page('Personal y accesos', function () use ($staff) { ?>
      <div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Crear empleado o administrador</h2>
      <form method="post" action="<?= h(url('guardar-usuario')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <label class="form-label">Nombre</label><input class="form-control mb-2" name="nombre" maxlength="80" required>
      <label class="form-label">Correo</label><input class="form-control mb-2" type="email" name="correo" required>
      <label class="form-label">Contraseña</label><input class="form-control mb-2" type="password" name="contrasena" minlength="8" required>
      <label class="form-label">Rol</label><select class="form-select mb-3" name="rol_id"><option value="2">Empleado</option><option value="1">Administrador</option></select>
      <button class="btn btn-primary">Crear acceso</button></form></div></div>
      <div class="col-lg-7"><div class="card p-3 table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th></tr></thead><tbody><?php foreach ($staff as $person): ?><tr><td><?= h($person['nombre']) ?></td><td><?= h($person['correo']) ?></td><td><?= h($person['rol']) ?></td><td><?= $person['activo'] ? 'Activo' : 'Inactivo' ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <?php });
}

function save_user_action(): void
{
    require_role('Administrador'); post_only();
    $name = trim((string) ($_POST['nombre'] ?? '')); $email = strtolower(trim((string) ($_POST['correo'] ?? ''))); $pass = (string) ($_POST['contrasena'] ?? ''); $role = (int) ($_POST['rol_id'] ?? 0);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass) < 8 || !in_array($role,[1,2],true) || one('SELECT id FROM usuarios WHERE correo=?',[$email])) fail('Datos inválidos o correo existente.', 'usuarios');
    db()->prepare('INSERT INTO usuarios(nombre,correo,contrasena,rol_id) VALUES(?,?,?,?)')->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
    go('usuarios', 'Acceso creado.');
}

