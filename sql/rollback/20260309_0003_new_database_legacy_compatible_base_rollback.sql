-- Proyecto PRESUPUESTO
-- Rollback Migracion 20260309_0003
-- Nota: no elimina tablas legacy clonadas para evitar romper compatibilidad.

USE presupuestos_nuevo;

DROP TABLE IF EXISTS catalogo_medios_pago;
DROP TABLE IF EXISTS movimiento_unificado;
DROP TABLE IF EXISTS compatibilidad_instancia;
