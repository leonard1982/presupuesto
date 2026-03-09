-- Proyecto PRESUPUESTO
-- Migracion 20260309_0003
-- Objetivo: crear base funcional en presupuestos_nuevo compatible con el modelo legacy
-- y mantener compatibilidad tecnica entre MariaDB 10.1 y MySQL modernos.

USE presupuestos_nuevo;

START TRANSACTION;

SET @legacy_schema = 'presupuestos_legacy';
SET @new_schema = DATABASE();

-- Tabla de control de compatibilidad de motor y estructura
CREATE TABLE IF NOT EXISTS compatibilidad_instancia (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    servidor_version VARCHAR(120) NOT NULL,
    servidor_version_numero VARCHAR(120) NOT NULL,
    motor_detectado VARCHAR(60) NOT NULL,
    charset_servidor VARCHAR(60) NOT NULL,
    collation_servidor VARCHAR(120) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO compatibilidad_instancia (
    servidor_version,
    servidor_version_numero,
    motor_detectado,
    charset_servidor,
    collation_servidor
)
SELECT
    @@version,
    @@version_comment,
    IF(LOWER(@@version_comment) LIKE '%mariadb%', 'MariaDB', 'MySQL'),
    @@character_set_server,
    @@collation_server
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM compatibilidad_instancia
    WHERE DATE(fecha_registro) = CURRENT_DATE()
);

-- ============================================================
-- Replica de estructura legacy en base nueva (sin datos).
-- Esto permite cambiar DB_NAME a presupuestos_nuevo sin romper consultas antiguas.
-- ============================================================

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'clasificaciones'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE clasificaciones LIKE ', @legacy_schema, '.clasificaciones'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'presupuesto'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE presupuesto LIKE ', @legacy_schema, '.presupuesto'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'detalle_presupuesto'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE detalle_presupuesto LIKE ', @legacy_schema, '.detalle_presupuesto'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'gastos_costos'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE gastos_costos LIKE ', @legacy_schema, '.gastos_costos'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'gastos_costos_deudas_pagas'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE gastos_costos_deudas_pagas LIKE ', @legacy_schema, '.gastos_costos_deudas_pagas'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'ingresos'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE ingresos LIKE ', @legacy_schema, '.ingresos'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'ingresos_detalle'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE ingresos_detalle LIKE ', @legacy_schema, '.ingresos_detalle'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'medios'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE medios LIKE ', @legacy_schema, '.medios'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'sec_users'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE sec_users LIKE ', @legacy_schema, '.sec_users'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'sec_groups'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE sec_groups LIKE ', @legacy_schema, '.sec_groups'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'sec_users_groups'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE sec_users_groups LIKE ', @legacy_schema, '.sec_users_groups'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'sec_apps'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE sec_apps LIKE ', @legacy_schema, '.sec_apps'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'sec_groups_apps'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE sec_groups_apps LIKE ', @legacy_schema, '.sec_groups_apps'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @new_schema AND TABLE_NAME = 'sec_settings'
);
SET @sql = IF(@table_exists = 0,
    CONCAT('CREATE TABLE sec_settings LIKE ', @legacy_schema, '.sec_settings'),
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- Tabla de movimiento unificado (base para evolucion futura)
-- Mantiene mapeo a ids legacy sin romper modelo actual.
-- ============================================================

CREATE TABLE IF NOT EXISTS movimiento_unificado (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    origen_legacy_tabla VARCHAR(40) NOT NULL COMMENT 'gastos_costos|ingresos',
    origen_legacy_id INT(11) NOT NULL,
    tipo_movimiento VARCHAR(20) NOT NULL COMMENT 'INGRESO|GASTO|COSTO',
    fecha_registro DATETIME NOT NULL,
    fecha_periodo DATE NOT NULL,
    id_clasificacion INT(11) NOT NULL,
    id_presupuesto INT(11) NOT NULL DEFAULT 0,
    detalle MEDIUMTEXT NOT NULL,
    valor DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    valor_neto DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    saldo DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    medio_pago VARCHAR(80) NOT NULL DEFAULT 'EFECTIVO',
    estado_cartera VARCHAR(15) NOT NULL DEFAULT 'NINGUNO',
    usuario_registro VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_movimiento_origen (origen_legacy_tabla, origen_legacy_id),
    KEY idx_movimiento_periodo_clasificacion (fecha_periodo, id_clasificacion),
    KEY idx_movimiento_presupuesto (id_presupuesto, fecha_periodo),
    KEY idx_movimiento_tipo_fecha (tipo_movimiento, fecha_periodo),
    KEY idx_movimiento_cartera_saldo (estado_cartera, saldo),
    KEY idx_movimiento_usuario_fecha (usuario_registro, fecha_periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Catalogo consolidado de medios de pago (sin abreviaturas ambiguas)
-- ============================================================

CREATE TABLE IF NOT EXISTS catalogo_medios_pago (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_catalogo_medios_pago_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO catalogo_medios_pago (nombre)
SELECT medio
FROM presupuestos_legacy.medios
WHERE medio IS NOT NULL
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

COMMIT;
