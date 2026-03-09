-- Proyecto PRESUPUESTO
-- Migracion 20260309_0002
-- Objetivo: estrategia dual para consultar legado y evolucionar base nueva.

CREATE DATABASE IF NOT EXISTS presupuestos_legacy
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

CREATE DATABASE IF NOT EXISTS presupuestos_nuevo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE presupuestos_nuevo;

CREATE TABLE IF NOT EXISTS sistema_control_versiones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    version_codigo VARCHAR(30) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    aplicado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS migracion_ejecuciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    origen_base VARCHAR(120) NOT NULL,
    destino_base VARCHAR(120) NOT NULL,
    entidad VARCHAR(120) NOT NULL,
    total_origen INT UNSIGNED NOT NULL DEFAULT 0,
    total_migrado INT UNSIGNED NOT NULL DEFAULT 0,
    estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE',
    detalle TEXT NULL,
    iniciado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalizado_en DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS migracion_mapeo_registros (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entidad VARCHAR(120) NOT NULL,
    id_origen VARCHAR(120) NOT NULL,
    id_destino VARCHAR(120) NOT NULL,
    lote_id BIGINT UNSIGNED NULL,
    migrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_mapeo_entidad_origen (entidad, id_origen),
    INDEX idx_mapeo_entidad_destino (entidad, id_destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sistema_control_versiones (version_codigo, descripcion)
SELECT '0.1.0', 'Inicializacion base nueva para evolucion y migracion controlada'
WHERE NOT EXISTS (
    SELECT 1
    FROM sistema_control_versiones
    WHERE version_codigo = '0.1.0'
);

DROP VIEW IF EXISTS legacy_gastos_costos;
DROP VIEW IF EXISTS legacy_ingresos;
DROP VIEW IF EXISTS legacy_presupuesto;

CREATE VIEW legacy_gastos_costos AS
SELECT * FROM presupuestos_legacy.gastos_costos;

CREATE VIEW legacy_ingresos AS
SELECT * FROM presupuestos_legacy.ingresos;

CREATE VIEW legacy_presupuesto AS
SELECT * FROM presupuestos_legacy.presupuesto;
