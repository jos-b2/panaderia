-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS panaderia_don_leo DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE panaderia_don_leo;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') NOT NULL DEFAULT 'empleado',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    debe_cambiar TINYINT(1) NOT NULL DEFAULT 0,
    creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Insumos (Materia Prima)
CREATE TABLE IF NOT EXISTS insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    unidad VARCHAR(20) NOT NULL,
    stock DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 3. Tabla de Movimientos de Inventario
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    stock_resultante DECIMAL(10,2) NOT NULL,
    nota TEXT DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_insumo FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE,
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabla de Productos Finales
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    base_insumo_id INT NOT NULL,
    rendimiento DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_prod_insumo FOREIGN KEY (base_insumo_id) REFERENCES insumos(id)
) ENGINE=InnoDB;

-- 5. Tabla de Recetas (Relación Producto - Insumos)
CREATE TABLE IF NOT EXISTS receta_items (
    producto_id INT NOT NULL,
    insumo_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (producto_id, insumo_id),
    CONSTRAINT fk_receta_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_receta_insumo FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabla de Producciones Diarias
CREATE TABLE IF NOT EXISTS producciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    producto_id INT NOT NULL,
    base_cantidad DECIMAL(10,2) NOT NULL,
    piezas INT NOT NULL,
    nota TEXT DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    anulada TINYINT(1) NOT NULL DEFAULT 0,
    creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    CONSTRAINT fk_prod_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. Tabla de Consumo por Producción
CREATE TABLE IF NOT EXISTS produccion_consumo (
    produccion_id INT NOT NULL,
    insumo_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_pc_produccion FOREIGN KEY (produccion_id) REFERENCES producciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_insumo FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Tabla de Detalle de Arqueo (Ventas vs Sobrantes)
CREATE TABLE IF NOT EXISTS arqueo_detalle (
    fecha DATE NOT NULL,
    producto_id INT NOT NULL,
    vendidas INT NOT NULL DEFAULT 0,
    sobrante INT NOT NULL DEFAULT 0,
    PRIMARY KEY (fecha, producto_id),
    CONSTRAINT fk_arqueo_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Tabla de Arqueo de Caja Diario
CREATE TABLE IF NOT EXISTS arqueo_caja (
    fecha DATE PRIMARY KEY,
    caja_real DECIMAL(10,2) DEFAULT NULL,
    nota TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- 10. Tabla de Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(25) DEFAULT NULL
) ENGINE=InnoDB;

-- 11. Tabla de Pedidos / Encargos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_entrega DATETIME NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('pendiente', 'en_produccion', 'listo', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    nota TEXT DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_pedido_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 12. Tabla de Abonos a Pedidos
CREATE TABLE IF NOT EXISTS abonos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATE NOT NULL DEFAULT (CURRENT_DATE),
    nota TEXT DEFAULT NULL,
    CONSTRAINT fk_abono_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. Tabla de Perfil de Usuario
CREATE TABLE IF NOT EXISTS perfil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre_personalizado VARCHAR(100) DEFAULT NULL,
    foto_url VARCHAR(255) DEFAULT NULL,
    banner_url VARCHAR(255) DEFAULT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices recomendados para optimizar consultas
CREATE INDEX idx_prod_fecha ON producciones(fecha);
CREATE INDEX idx_ped_entrega ON pedidos(fecha_entrega);
CREATE INDEX idx_mov_insumo ON movimientos(insumo_id);