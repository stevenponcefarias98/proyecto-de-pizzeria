<?php
declare(strict_types=1);

function orders_page(): void
{
    require_role();
    $staff = in_array(user()['rol'], ['Administrador', 'Empleado'], true);
    $orders = $staff
        ? rows('SELECT p.*,u.nombre AS cliente FROM pedidos p JOIN usuarios u ON u.id=p.cliente_id ORDER BY p.id DESC LIMIT 100')
        : rows('SELECT p.*,u.nombre AS cliente FROM pedidos p JOIN usuarios u ON u.id=p.cliente_id WHERE p.cliente_id=? ORDER BY p.id DESC LIMIT 100', [user()['id']]);
    $items = [];
    if ($orders) {
        $ids = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        foreach (rows("SELECT pedido_id,nombre_producto,cantidad,precio_unitario FROM pedido_detalles WHERE pedido_id IN ($placeholders)", $ids) as $item) {
            $items[$item['pedido_id']][] = $item;
        }
    }
    $products = rows('SELECT id,nombre,precio,stock FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre');
    $clients = $staff ? rows('SELECT id,nombre FROM usuarios WHERE rol_id=3 AND activo=1 ORDER BY nombre') : [];
    page('Pedidos', function () use ($staff, $orders, $items, $products, $clients) { ?>
      <div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Nuevo pedido</h2>
      <?php if (!$products): ?><div class="alert alert-warning">No hay productos disponibles.</div><?php else: ?>
      <form method="post" action="<?= h(url('pedir')) ?>"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
      <?php if ($staff): ?><label class="form-label">Cliente</label><select class="form-select mb-3" name="cliente_id" required><option value="">Selecciona un cliente</option>
      <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['nombre']) ?></option><?php endforeach; ?></select><?php endif; ?>
      <?php foreach ($products as $p): ?><div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom"><label for="qty<?= (int) $p['id'] ?>">
      <strong><?= h($p['nombre']) ?></strong><br><small class="text-secondary"><?= h(money($p['precio'])) ?> · quedan <?= (int) $p['stock'] ?></small></label>
      <input id="qty<?= (int) $p['id'] ?>" class="form-control text-center" style="width:90px" type="number" min="0" max="<?= (int) $p['stock'] ?>" value="0" name="qty[<?= (int) $p['id'] ?>]" data-qty data-price="<?= h($p['precio']) ?>"></div><?php endforeach; ?>
      <label class="form-label mt-3">Indicaciones</label><textarea class="form-control" name="observaciones" maxlength="255" rows="2" placeholder="Opcional"></textarea>
      <div class="d-flex justify-content-between align-items-center mt-3"><strong>Total estimado: <span id="total-estimado">$0.00</span></strong><button class="btn btn-primary" <?= $staff && !$clients ? 'disabled' : '' ?>>Enviar pedido</button></div></form><?php endif; ?>
      </div></div><div class="col-lg-7"><h2 class="h5 mb-3"><?= $staff ? 'Pedidos recientes' : 'Mis pedidos' ?></h2>
      <?php if (!$orders): ?><div class="empty">Aún no hay pedidos.</div><?php endif; ?>
      <?php foreach ($orders as $o): ?><article class="card p-3 mb-3"><div class="d-flex justify-content-between gap-2 flex-wrap"><div><strong>Pedido #<?= (int) $o['id'] ?></strong>
      <div class="text-secondary small"><?= h($o['cliente']) ?> · <?= h($o['fecha']) ?></div></div><div><span class="badge text-bg-<?= $o['estado'] === 'Cancelado' ? 'secondary' : ($o['estado'] === 'Entregado' ? 'success' : 'warning') ?> badge-state"><?= h($o['estado']) ?></span> <strong><?= h(money($o['total'])) ?></strong></div></div>
      <ul class="small mt-3 mb-2"><?php foreach ($items[$o['id']] ?? [] as $item): ?><li><?= (int) $item['cantidad'] ?> × <?= h($item['nombre_producto']) ?> (<?= h(money($item['precio_unitario'])) ?> c/u)</li><?php endforeach; ?></ul>
      <?php if ($o['observaciones']): ?><p class="small text-secondary">Nota: <?= h($o['observaciones']) ?></p><?php endif; ?>
      <?php if ($staff || (user()['rol'] === 'Cliente' && $o['estado'] === 'Pendiente')): ?>
      <form method="post" action="<?= h(url('estado')) ?>" class="d-flex gap-2 mt-1" <?= $staff ? '' : 'data-confirm="¿Cancelar este pedido?"' ?>><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="pedido_id" value="<?= (int) $o['id'] ?>">
      <?php if ($staff): ?><select class="form-select form-select-sm" name="estado" aria-label="Estado del pedido">
      <?php foreach (['Pendiente','Preparando','Listo','Entregado','Cancelado'] as $state): ?><option <?= $state === $o['estado'] ? 'selected' : '' ?>><?= h($state) ?></option><?php endforeach; ?></select><button class="btn btn-outline-primary btn-sm">Actualizar</button>
      <?php else: ?><input type="hidden" name="estado" value="Cancelado"><button class="btn btn-outline-danger btn-sm">Cancelar pedido</button><?php endif; ?></form><?php endif; ?>
      </article><?php endforeach; ?></div></div>
    <?php });
}

function order_action(): void
{
    require_role(); post_only();
    $staff = in_array(user()['rol'], ['Administrador', 'Empleado'], true);
    $clientId = $staff ? filter_var($_POST['cliente_id'] ?? null, FILTER_VALIDATE_INT) : (int) user()['id'];
    if (!$clientId || !one('SELECT id FROM usuarios WHERE id=? AND rol_id=3 AND activo=1', [$clientId])) fail('Selecciona un cliente válido.', 'pedidos');
    $quantities = $_POST['qty'] ?? [];
    if (!is_array($quantities)) fail('Pedido inválido.', 'pedidos');
    $selected = [];
    foreach ($quantities as $id => $qty) {
        if (!ctype_digit((string) $id) || !ctype_digit((string) $qty)) fail('Las cantidades deben ser enteros positivos.', 'pedidos');
        if ((int) $qty > 0) $selected[(int) $id] = (int) $qty;
    }
    if (!$selected || count($selected) > 50) fail('Selecciona al menos un producto.', 'pedidos');
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $lines = []; $totalCents = 0;
        ksort($selected);
        foreach ($selected as $id => $qty) {
            $p = one('SELECT id,nombre,precio,stock FROM productos WHERE id=? AND activo=1 FOR UPDATE', [$id]);
            if (!$p || $qty > (int) $p['stock']) throw new DomainException('Un producto ya no tiene stock suficiente. Revisa las cantidades.');
            $lines[] = [$p, $qty];
            $totalCents += (int) round((float) $p['precio'] * 100) * $qty;
        }
        $pdo->prepare('INSERT INTO pedidos(cliente_id,total,observaciones) VALUES(?,?,?)')->execute([$clientId, number_format($totalCents / 100, 2, '.', ''), mb_substr(trim((string) ($_POST['observaciones'] ?? '')), 0, 255)]);
        $orderId = (int) $pdo->lastInsertId();
        $detail = $pdo->prepare('INSERT INTO pedido_detalles(pedido_id,producto_id,nombre_producto,cantidad,precio_unitario) VALUES(?,?,?,?,?)');
        $stock = $pdo->prepare('UPDATE productos SET stock=stock-? WHERE id=?');
        foreach ($lines as [$p,$qty]) {
            $detail->execute([$orderId,$p['id'],$p['nombre'],$qty,$p['precio']]);
            $stock->execute([$qty,$p['id']]);
        }
        $pdo->commit();
        go('pedidos', 'Pedido #' . $orderId . ' creado correctamente.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof DomainException) fail($e->getMessage(), 'pedidos');
        throw $e;
    }
}

function status_action(): void
{
    require_role(); post_only();
    $id = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
    $next = (string) ($_POST['estado'] ?? '');
    $transitions = ['Pendiente'=>['Preparando','Cancelado'], 'Preparando'=>['Listo','Cancelado'], 'Listo'=>['Entregado','Cancelado'], 'Entregado'=>[], 'Cancelado'=>[]];
    if (!$id || !array_key_exists($next, $transitions)) fail('Estado inválido.', 'pedidos');
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $order = one('SELECT * FROM pedidos WHERE id=? FOR UPDATE', [$id]);
        if (!$order) throw new DomainException('Pedido no encontrado.');
        $staff = in_array(user()['rol'], ['Administrador','Empleado'], true);
        if (!$staff && ((int) $order['cliente_id'] !== (int) user()['id'] || $order['estado'] !== 'Pendiente' || $next !== 'Cancelado')) throw new DomainException('No tienes permiso para modificar este pedido.');
        if (!in_array($next, $transitions[$order['estado']], true)) throw new DomainException('No se permite ese cambio de estado.');
        if ($next === 'Cancelado') {
            foreach (rows('SELECT producto_id,cantidad FROM pedido_detalles WHERE pedido_id=? ORDER BY producto_id', [$id]) as $item) {
                $pdo->prepare('UPDATE productos SET stock=stock+? WHERE id=?')->execute([$item['cantidad'],$item['producto_id']]);
            }
        }
        $pdo->prepare('UPDATE pedidos SET estado=? WHERE id=?')->execute([$next,$id]);
        $pdo->commit();
        go('pedidos', 'Estado actualizado.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof DomainException) fail($e->getMessage(), 'pedidos');
        throw $e;
    }
}

