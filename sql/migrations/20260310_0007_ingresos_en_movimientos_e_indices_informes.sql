-- Proyecto PRESUPUESTO
-- Migracion 20260310_0007
-- Objetivo:
-- 1) habilitar categoria Ingreso en gastos_costos.gasto_costo
-- 2) agregar indices para consultas de informes y KPIs.

START TRANSACTION;

SET @db_name = DATABASE();

SET @column_type = (
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'gastos_costos'
      AND COLUMN_NAME = 'gasto_costo'
    LIMIT 1
);

SET @has_ingreso = IF(@column_type LIKE '%''Ingreso''%', 1, 0);
SET @sql = IF(@has_ingreso = 0,
    'ALTER TABLE gastos_costos MODIFY gasto_costo SET(''Gasto'',''Costo'',''Ingreso'') NOT NULL COMMENT ''Si fue un Gasto, Costo o Ingreso de la operacion''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gc_periodo_categoria'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gc_periodo_categoria (fecha_periodo, gasto_costo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gc_clasificacion_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gc_clasificacion_periodo (id_clasificacion, fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gc_tipo_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gc_tipo_periodo (tipo, fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos ADD INDEX idx_ingresos_periodo (fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_clasificacion_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos ADD INDEX idx_ingresos_clasificacion_periodo (id_clasificacion, fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_tipo_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos ADD INDEX idx_ingresos_tipo_periodo (tipo, fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
