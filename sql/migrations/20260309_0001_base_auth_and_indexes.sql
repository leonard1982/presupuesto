-- Proyecto PRESUPUESTO
-- Migracion 20260309_0001
-- Objetivo: base de seguridad de auth + indices de rendimiento iniciales.

START TRANSACTION;

SET @db_name = DATABASE();

-- Columnas para migracion gradual MD5 -> hash moderno
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sec_users' AND COLUMN_NAME = 'password_hash'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE sec_users ADD COLUMN password_hash VARCHAR(255) NULL AFTER pswd',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sec_users' AND COLUMN_NAME = 'password_algorithm'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE sec_users ADD COLUMN password_algorithm VARCHAR(40) NULL AFTER password_hash',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sec_users' AND COLUMN_NAME = 'password_migrated_at'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE sec_users ADD COLUMN password_migrated_at DATETIME NULL AFTER password_algorithm',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indices para consultas frecuentes de movimientos
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_periodo_clasificacion_presupuesto'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gastos_periodo_clasificacion_presupuesto (fecha_periodo, id_clasificacion, id_presupuesto)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_usuario_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gastos_usuario_periodo (usuario, fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_estado_cobro'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gastos_estado_cobro (por_pagar_cobrar, saldo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_periodo_clasificacion_presupuesto'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos ADD INDEX idx_ingresos_periodo_clasificacion_presupuesto (fecha_periodo, id_clasificacion, id_presupuesto)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_tipo_estado'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos ADD INDEX idx_ingresos_tipo_estado (tipo, por_cobrar_pagar)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'detalle_presupuesto' AND INDEX_NAME = 'idx_detalle_presupuesto_relaciones'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE detalle_presupuesto ADD INDEX idx_detalle_presupuesto_relaciones (id_presupuesto, id_clasificacion)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos_detalle' AND INDEX_NAME = 'idx_ingresos_detalle_id_ingreso'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos_detalle ADD INDEX idx_ingresos_detalle_id_ingreso (id_ingreso)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sc_log' AND INDEX_NAME = 'idx_sc_log_inserted_date_application'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE sc_log ADD INDEX idx_sc_log_inserted_date_application (inserted_date, application)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
