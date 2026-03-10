-- Proyecto PRESUPUESTO
-- Rollback migracion 20260310_0007

START TRANSACTION;

SET @db_name = DATABASE();

UPDATE gastos_costos
SET gasto_costo = 'Gasto'
WHERE gasto_costo = 'Ingreso';

SET @column_type = (
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'gastos_costos'
      AND COLUMN_NAME = 'gasto_costo'
    LIMIT 1
);
SET @has_ingreso = IF(@column_type LIKE '%''Ingreso''%', 1, 0);
SET @sql = IF(@has_ingreso = 1,
    'ALTER TABLE gastos_costos MODIFY gasto_costo SET(''Gasto'',''Costo'') NOT NULL COMMENT ''Si fue un Gasto o Costo de la operacion''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gc_periodo_categoria'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE gastos_costos DROP INDEX idx_gc_periodo_categoria', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gc_clasificacion_periodo'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE gastos_costos DROP INDEX idx_gc_clasificacion_periodo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gc_tipo_periodo'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE gastos_costos DROP INDEX idx_gc_tipo_periodo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_periodo'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE ingresos DROP INDEX idx_ingresos_periodo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_clasificacion_periodo'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE ingresos DROP INDEX idx_ingresos_clasificacion_periodo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_tipo_periodo'
);
SET @sql = IF(@index_exists > 0, 'ALTER TABLE ingresos DROP INDEX idx_ingresos_tipo_periodo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
