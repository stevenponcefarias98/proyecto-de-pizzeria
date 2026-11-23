# Pizzería 34

Proyecto individual del ejercicio 34 de Programación Web Avanzada. PHP sin framework, MySQL/MariaDB, HTML, Bootstrap 5, CSS y JavaScript.

## Requisitos

- XAMPP con PHP 8.1 o superior, extensiones `pdo_mysql` y `mbstring`, y MySQL/MariaDB.
- Conexión a internet para cargar Bootstrap 5 desde CDN. El resto de recursos es local.

## Instalación en Windows / XAMPP

1. Copia esta carpeta como `C:\xampp\htdocs\pizzeria34` (sin mezclarla con el proyecto del hotel).
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. En `http://localhost/phpmyadmin`, entra en **Importar** e importa el archivo `database.sql` de la raíz. Esto crea la base `pizzeria34` y cuatro productos de muestra.
4. Revisa `config.php`: por defecto usa servidor `127.0.0.1`, base `pizzeria34`, usuario `root` y contraseña vacía. En un servidor real cambia estos valores mediante variables de entorno `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
5. En PowerShell, crea el administrador con tu propia contraseña (mínimo 12 caracteres):

   ```powershell
   & 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\pizzeria34\crear_admin.php' 'Terry Ponce' 'tu-correo@ejemplo.com' 'TuClaveSeguraDe12Caracteres'
   ```

6. Abre `http://localhost/pizzeria34/` y entra con el correo y la clave que acabas de crear.

También puedes usar el servidor de desarrollo: dentro de la carpeta ejecuta `C:\xampp\php\php.exe -S 127.0.0.1:8001`; abre `http://127.0.0.1:8001`. Se eligió el puerto 8001 para no interferir con Hotel Evorion.

## Roles y alcance

| Rol | Funciones |
| --- | --- |
| Administrador | Productos e inventario, clientes, pedidos, estados, gastos, balance y creación de personal. |
| Empleado | Alta y actualización de clientes; creación de pedidos y avance de estados. |
| Cliente | Registro, menú, pedidos propios, historial y cancelación mientras estén pendientes. |

Cada pedido admite varios productos. Al crearlo se descuentan unidades de stock dentro de una transacción; al cancelarlo se reponen. Los precios históricos quedan en el detalle, aunque se edite el producto después. Los ingresos son la suma de los pedidos **Entregados**, los gastos se registran aparte y el balance es ingresos menos gastos. Se usan contraseñas cifradas con `password_hash`, consultas preparadas, tokens CSRF y validación de permisos por ruta.

## Prueba rápida

1. Inicia como administrador; crea un empleado y revisa los productos.
2. Registra un cliente desde **Crear cuenta** en una ventana privada.
3. Como cliente, pide dos productos y comprueba que aparece el pedido y disminuye el stock.
4. Como empleado, cambia sucesivamente el estado a Preparando, Listo y Entregado.
5. Como administrador, registra un gasto y verifica los ingresos y el balance.
6. Crea otro pedido, cancélalo mientras está Pendiente y comprueba que el stock vuelve a subir.

## Entrega solicitada

El docente solicita un archivo RAR con esta carpeta, con `database.sql` en la raíz, y un archivo Word que incluya la URL del repositorio GitHub (también con el SQL en la raíz) y la URL del hospedaje web. Añade esas URLs reales después de publicar y alojar tu proyecto. No se incluyen credenciales reales en este repositorio.

Para hospedarlo, importa `database.sql` en la base del proveedor, configura las variables de conexión y `BASE_URL` si vive bajo una subcarpeta. Ejecuta `crear_admin.php` desde una consola segura del alojamiento y elimina ese script del despliegue tras crear la cuenta.
