# Compatibilidad MariaDB 10.1 y MySQL nuevos

## Objetivo
Disenar y migrar `presupuestos_nuevo` sin perder compatibilidad con:
- MariaDB 10.1.48 (produccion actual)
- MySQL/MariaDB mas recientes

## Reglas de compatibilidad aplicadas
- Charset y collation base: `utf8mb4` + collation explicita.
- Motor de tablas: `InnoDB`.
- Tipos SQL portables:
  - `INT`, `BIGINT`, `VARCHAR`, `DATETIME`, `DATE`, `DECIMAL`, `TEXT`.
- Evitar dependencias en funciones exclusivas de versiones nuevas.
- Evitar JSON nativo obligatorio (usar `TEXT`/`LONGTEXT` cuando se requiera compatibilidad).
- Evitar `CHECK` como regla de negocio obligatoria (en motores antiguos no siempre se aplica).
- Mantener migraciones reversibles.

## Estrategia de modelo
- `presupuestos_legacy`: conservacion historica y referencia.
- `presupuestos_nuevo`: base evolutiva.
- En `presupuestos_nuevo`:
  - Se clona estructura legacy necesaria para compatibilidad de consultas antiguas.
  - Se agregan tablas nuevas de evolucion (`movimiento_unificado`, `catalogo_medios_pago`).
  - Se deja trazabilidad de version de motor (`compatibilidad_instancia`).

## Compatibilidad funcional
- El sistema actual puede seguir operando con `DB_NAME=presupuestos`.
- El nuevo modelo puede crecer por fases en `presupuestos_nuevo`.
- Se puede migrar entidad por entidad sin apagar el sistema legacy.
