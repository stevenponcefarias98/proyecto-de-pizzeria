<?php
declare(strict_types=1);

// Presenta la página pública de inicio y el catálogo disponible para los clientes.
function home_page(): void
{
    page('Pizza recién hecha', function () { ?>
      <section class="hero"><div class="row align-items-center"><div class="col-lg-8">
      <span class="text-warning text-uppercase fw-bold">Bienvenido</span><h2 class="display-4 fw-bold mt-2">Tu próxima pizza empieza aquí.</h2>
      <p class="lead">Elige tus favoritos, haz tu pedido y sigue cada paso de su preparación.</p>
      <a class="btn btn-warning btn-lg" href="<?= h(url('menu')) ?>">Explorar menú</a>
      </div><div class="col-lg-4 text-center d-none d-lg-block" style="font-size:8rem" aria-hidden="true">🍕</div></div></section>
      <div class="row g-3 mt-3"><div class="col-md-4"><div class="card p-4 h-100"><h3 class="h5">1. Escoge</h3><p class="mb-0">Consulta precios y disponibilidad.</p></div></div>
      <div class="col-md-4"><div class="card p-4 h-100"><h3 class="h5">2. Ordena</h3><p class="mb-0">Pide varios productos en una sola compra.</p></div></div>
      <div class="col-md-4"><div class="card p-4 h-100"><h3 class="h5">3. Disfruta</h3><p class="mb-0">Revisa el estado de tu pedido.</p></div></div></div>
    <?php });
}

function menu_page(): void
{
    $products = rows('SELECT * FROM productos WHERE activo=1 ORDER BY categoria,nombre');
    page('Nuestro menú', function () use ($products) { ?>
      <p class="text-secondary">Sabores y precios en dólares. El stock se confirma al enviar el pedido.</p>
      <?php if (!$products): ?><div class="empty">El menú está en preparación.</div><?php endif; ?>
      <div class="row g-4"><?php foreach ($products as $p): ?><div class="col-md-6 col-xl-4"><div class="card h-100 p-4">
      <div class="fs-1 mb-2" aria-hidden="true"><?= $p['categoria'] === 'Bebida' ? '🥤' : '🍕' ?></div><span class="text-uppercase small text-secondary"><?= h($p['categoria']) ?></span>
      <h2 class="h4 mt-2"><?= h($p['nombre']) ?></h2><p class="text-secondary flex-grow-1"><?= h($p['descripcion']) ?></p>
      <div class="d-flex justify-content-between align-items-center"><span class="price"><?= h(money($p['precio'])) ?></span><span class="badge text-bg-<?= (int) $p['stock'] > 0 ? 'success' : 'secondary' ?>"><?= (int) $p['stock'] > 0 ? 'Disponible' : 'Agotado' ?></span></div>
      </div></div><?php endforeach; ?></div>
      <div class="mt-4"><a class="btn btn-primary" href="<?= h(url(user() ? 'pedidos' : 'registro')) ?>"><?= user() ? 'Hacer un pedido' : 'Crear cuenta para pedir' ?></a></div>
    <?php });
}

