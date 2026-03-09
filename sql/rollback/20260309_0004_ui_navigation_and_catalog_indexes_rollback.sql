-- Proyecto PRESUPUESTO
-- Rollback migracion 20260309_0004

START TRANSACTION;

SET @db_name = DATABASE();

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_fecha'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE gastos_costos DROP INDEX idx_gastos_fecha', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_clasificacion_fecha_periodo'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE gastos_costos DROP INDEX idx_gastos_clasificacion_fecha_periodo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_fecha'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE ingresos DROP INDEX idx_ingresos_fecha', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'clasificaciones' AND INDEX_NAME = 'idx_clasificaciones_descripcion'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE clasificaciones DROP INDEX idx_clasificaciones_descripcion', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'medios' AND INDEX_NAME = 'idx_medios_medio'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE medios DROP INDEX idx_medios_medio', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'presupuesto' AND INDEX_NAME = 'idx_presupuesto_estado_fecha'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE presupuesto DROP INDEX idx_presupuesto_estado_fecha', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
