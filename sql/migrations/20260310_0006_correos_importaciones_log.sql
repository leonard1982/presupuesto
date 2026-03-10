-- Proyecto PRESUPUESTO
-- Migracion 20260310_0006
-- Objetivo: crear log de importaciones desde bandeja de correos para trazabilidad.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS correo_importaciones_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    correo_uid VARCHAR(120) NOT NULL,
    remitente VARCHAR(255) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    fecha_correo DATETIME NULL,
    contenido_hash CHAR(40) NOT NULL,
    sugerencia_json LONGTEXT NULL,
    movimiento_id INT(11) DEFAULT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
    confianza DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    usuario VARCHAR(120) NOT NULL,
    observaciones TEXT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @db_name = DATABASE();

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'correo_importaciones_log' AND INDEX_NAME = 'idx_correo_importaciones_uid'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE correo_importaciones_log ADD INDEX idx_correo_importaciones_uid (correo_uid)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'correo_importaciones_log' AND INDEX_NAME = 'idx_correo_importaciones_movimiento'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE correo_importaciones_log ADD INDEX idx_correo_importaciones_movimiento (movimiento_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'correo_importaciones_log' AND INDEX_NAME = 'idx_correo_importaciones_estado_fecha'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE correo_importaciones_log ADD INDEX idx_correo_importaciones_estado_fecha (estado, fecha_registro)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
