-- Proyecto PRESUPUESTO
-- Rollback Migracion 20260309_0001

START TRANSACTION;

SET @db_name = DATABASE();

-- Eliminar indices agregados
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_periodo_clasificacion_presupuesto'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE gastos_costos DROP INDEX idx_gastos_periodo_clasificacion_presupuesto',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_usuario_periodo'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE gastos_costos DROP INDEX idx_gastos_usuario_periodo',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_estado_cobro'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE gastos_costos DROP INDEX idx_gastos_estado_cobro',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_periodo_clasificacion_presupuesto'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE ingresos DROP INDEX idx_ingresos_periodo_clasificacion_presupuesto',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_tipo_estado'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE ingresos DROP INDEX idx_ingresos_tipo_estado',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'detalle_presupuesto' AND INDEX_NAME = 'idx_detalle_presupuesto_relaciones'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE detalle_presupuesto DROP INDEX idx_detalle_presupuesto_relaciones',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos_detalle' AND INDEX_NAME = 'idx_ingresos_detalle_id_ingreso'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE ingresos_detalle DROP INDEX idx_ingresos_detalle_id_ingreso',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sc_log' AND INDEX_NAME = 'idx_sc_log_inserted_date_application'
);
SET @sql = IF(@index_exists > 0,
    'ALTER TABLE sc_log DROP INDEX idx_sc_log_inserted_date_application',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Eliminar columnas de auth moderna
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sec_users' AND COLUMN_NAME = 'password_migrated_at'
);
SET @sql = IF(@column_exists > 0,
    'ALTER TABLE sec_users DROP COLUMN password_migrated_at',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sec_users' AND COLUMN_NAME = 'password_algorithm'
);
SET @sql = IF(@column_exists > 0,
    'ALTER TABLE sec_users DROP COLUMN password_algorithm',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'sec_users' AND COLUMN_NAME = 'password_hash'
);
SET @sql = IF(@column_exists > 0,
    'ALTER TABLE sec_users DROP COLUMN password_hash',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
