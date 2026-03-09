-- Proyecto PRESUPUESTO
-- Migracion 20260309_0004
-- Objetivo: mejorar rendimiento de consultas del menu, dashboard y catalogos.

START TRANSACTION;

SET @db_name = DATABASE();

-- Indice para listado de movimientos recientes (ORDER BY fecha DESC LIMIT N)
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_fecha'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gastos_fecha (fecha)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice para consultas por clasificacion en dashboard y formularios
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'gastos_costos' AND INDEX_NAME = 'idx_gastos_clasificacion_fecha_periodo'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE gastos_costos ADD INDEX idx_gastos_clasificacion_fecha_periodo (id_clasificacion, fecha_periodo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice para consultas por fecha en ingresos del dashboard
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos' AND INDEX_NAME = 'idx_ingresos_fecha'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos ADD INDEX idx_ingresos_fecha (fecha)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice para busqueda y orden en clasificaciones
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'clasificaciones' AND INDEX_NAME = 'idx_clasificaciones_descripcion'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE clasificaciones ADD INDEX idx_clasificaciones_descripcion (descripcion)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice para busqueda y orden en medios de pago
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'medios' AND INDEX_NAME = 'idx_medios_medio'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE medios ADD INDEX idx_medios_medio (medio)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice para filtro de presupuestos activos
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'presupuesto' AND INDEX_NAME = 'idx_presupuesto_estado_fecha'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE presupuesto ADD INDEX idx_presupuesto_estado_fecha (estado, fecha_creacion)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
