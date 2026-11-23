-- Importa este archivo en phpMyAdmin. MySQL 8 / MariaDB 10.4+.
CREATE DATABASE IF NOT EXISTS pizzeria34 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pizzeria34;

CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED PRIMARY KEY,
  nombre VARCHAR(20) NOT NULL UNIQUE
);
INSERT INTO roles (id,nombre) VALUES (1,'Administrador'),(2,'Empleado'),(3,'Cliente')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Una tabla de usuarios evita contraseñas duplicadas entre clientes y personal.
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  rol_id TINYINT UNSIGNED NOT NULL,
  telefono VARCHAR(30) DEFAULT NULL,
  direccion VARCHAR(255) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  categoria VARCHAR(40) NOT NULL DEFAULT 'Pizza',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CHECK (precio >= 0)
);

-- Cabecera y detalle permiten varios productos en un mismo pedido.
CREATE TABLE IF NOT EXISTS pedidos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('Pendiente','Preparando','Listo','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  observaciones VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS pedido_detalles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  nombre_producto VARCHAR(80) NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  CHECK (cantidad > 0)
);

-- Los ingresos se calculan de pedidos entregados; aquí se registran gastos.
CREATE TABLE IF NOT EXISTS gastos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  concepto VARCHAR(120) NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  CHECK (monto > 0)
);

INSERT INTO productos(nombre,descripcion,precio,stock,categoria) VALUES
('Margarita','Salsa de tomate, mozzarella y albahaca.',7.50,30,'Pizza'),
('Pepperoni','Mozzarella y pepperoni crocante.',9.00,25,'Pizza'),
('Cuatro quesos','Mozzarella, cheddar, parmesano y queso azul.',11.50,18,'Pizza'),
('Limonada','Bebida fría de limón natural.',2.50,40,'Bebida');
