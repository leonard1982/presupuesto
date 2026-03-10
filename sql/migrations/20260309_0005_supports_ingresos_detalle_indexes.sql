-- Proyecto PRESUPUESTO
-- Migracion 20260309_0005
-- Objetivo: optimizar consultas de soportes en ingresos_detalle.

START TRANSACTION;

SET @db_name = DATABASE();

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ingresos_detalle' AND INDEX_NAME = 'idx_ingresos_detalle_id_ingreso_id'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ingresos_detalle ADD INDEX idx_ingresos_detalle_id_ingreso_id (id_ingreso, id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
