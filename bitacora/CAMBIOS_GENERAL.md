# Cambios Generales

## 2026-03-09 - Fase 2 base tecnica inicial
- Se crea estructura de carpetas profesional y modular.
- Se agrega configuracion centralizada por entorno (`.env`).
- Se implementa bootstrap, autoloader y front controller.
- Se agregan medidas de seguridad base con `.htaccess`.
- Se implementa login/logout inicial con CSRF y sesion segura.
- Se agrega compatibilidad con migracion gradual MD5 -> hash moderno.
- Se crean documentos base de arquitectura y despliegue.
- Se crean scripts SQL de migracion y rollback.

## 2026-03-09 - Correccion acceso local 403
- Se agrega `index.php` en raiz como fallback para entornos XAMPP con configuracion parcial de reescritura.
- Se ajusta `DirectoryIndex` en `.htaccess` a `index.php public/index.php`.
- Objetivo: evitar error 403 por ausencia de indice cuando Apache no procesa reescritura como se espera.

## 2026-03-09 - Estrategia dual de base de datos
- Se crea base `presupuestos_legacy` para conservar y consultar datos historicos.
- Se crea base `presupuestos_nuevo` para evolucionar el nuevo modelo sin romper legado.
- En `presupuestos_nuevo` se crean tablas de control de migracion:
  - `sistema_control_versiones`
  - `migracion_ejecuciones`
  - `migracion_mapeo_registros`
- Se crean vistas de consulta de legado desde base nueva:
  - `legacy_gastos_costos`
  - `legacy_ingresos`
  - `legacy_presupuesto`
- Se versiona SQL reproducible en `sql/migrations/20260309_0002_dual_database_setup.sql` y rollback asociado.

## 2026-03-09 - Compatibilidad cruzada MariaDB 10.1 y MySQL nuevos
- Se agrega migracion `20260309_0003_new_database_legacy_compatible_base.sql`.
- Se replica en `presupuestos_nuevo` estructura base legacy requerida para compatibilidad de consultas antiguas.
- Se crea tabla `compatibilidad_instancia` para trazabilidad de version de motor y charset.
- Se crea tabla `movimiento_unificado` como base evolutiva sin romper el modelo anterior.
- Se crea `catalogo_medios_pago` consolidado para normalizacion incremental.
- Se agrega documentacion tecnica en `docs/COMPATIBILIDAD_MARIADB_MYSQL.md`.

## 2026-03-09 - Ajuste dinamico de URL base y puertos
- Se revierte cambio no solicitado de escucha en puerto 80 de Apache local.
- Se establece deteccion dinamica de `base_url` cuando `APP_BASE_URL=AUTO`.
- La aplicacion ahora construye URLs con host y puerto reales del request.
- Se mantiene compatibilidad para desarrollo con puerto variable (ejemplo 9192) y produccion 80/443.
