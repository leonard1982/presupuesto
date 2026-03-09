-- Proyecto PRESUPUESTO
-- Rollback Migracion 20260309_0002
-- Nota: no elimina presupuestos_legacy para evitar perdida accidental de respaldo historico.

USE presupuestos_nuevo;

DROP VIEW IF EXISTS legacy_gastos_costos;
DROP VIEW IF EXISTS legacy_ingresos;
DROP VIEW IF EXISTS legacy_presupuesto;

DROP TABLE IF EXISTS migracion_mapeo_registros;
DROP TABLE IF EXISTS migracion_ejecuciones;
DROP TABLE IF EXISTS sistema_control_versiones;

-- Si desea eliminar completamente la base nueva en entorno controlado:
-- DROP DATABASE IF EXISTS presupuestos_nuevo;
