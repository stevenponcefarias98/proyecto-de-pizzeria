<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// El enrutador usa nombres fijos; ninguna ruta se convierte en un archivo arbitrario.
$route = (string) ($_GET['r'] ?? 'inicio');
try {
    require __DIR__ . '/src/auth.php';
    require __DIR__ . '/src/catalog.php';
    require __DIR__ . '/src/orders.php';
    require __DIR__ . '/src/admin.php';
    switch ($route) {
        case 'inicio': home_page(); break;
        case 'menu': menu_page(); break;
        case 'login': login_page(); break;
        case 'registro': register_page(); break;
        case 'salir': logout_action(); break;
        case 'pedidos': orders_page(); break;
        case 'pedir': order_action(); break;
        case 'estado': status_action(); break;
        case 'productos': products_page(); break;
        case 'guardar-producto': save_product_action(); break;
        case 'eliminar-producto': delete_product_action(); break;
        case 'clientes': customers_page(); break;
        case 'guardar-cliente': save_customer_action(); break;
        case 'finanzas': finance_page(); break;
        case 'guardar-gasto': expense_action(); break;
        case 'eliminar-gasto': delete_expense_action(); break;
        case 'usuarios': users_page(); break;
        case 'guardar-usuario': save_user_action(); break;
        default: http_response_code(404); page('Página no encontrada', fn() => print '<p>La ruta solicitada no existe.</p>');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    page('Error de base de datos', fn() => print '<div class="alert alert-danger">No se pudo completar la operación. Comprueba la configuración o vuelve a intentarlo.</div>');
}
