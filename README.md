# PRESUPUESTO - Base Tecnica Inicial

## Estado
Fase 2 base tecnica creada con enfoque modular, compatible Windows/Linux.

## Estructura clave
- `public/` entrada web y assets.
- `app/` logica modular.
- `storage/` logs, cache y cargas.
- `bitacora/` trazabilidad obligatoria.
- `sql/` migraciones y rollback.

## Inicio rapido
1. Copia `.env.example` a `.env`.
2. Ajusta variables de conexion y `APP_BASE_URL`.
3. Configura Apache con `AllowOverride All`.
4. Abre `http://localhost/PRESUPUESTO/login`.
5. Para bandeja de correos, habilita extension `imap` y configura `MAIL_INBOX_*` en `.env`.
6. Ejecuta migraciones SQL nuevas:
   - `20260310_0006_correos_importaciones_log.sql`
   - `20260310_0007_ingresos_en_movimientos_e_indices_informes.sql`

## Seguridad
- Directorios sensibles bloqueados por `.htaccess`.
- Login con CSRF y sesion segura.
- Base lista para migracion gradual MD5 -> hash moderno.

## Nota de compatibilidad
- Funciona con PHP 7.2 a 8.2.
- Desarrollo actual validado en PHP 7.3.
- Incluye modulo `correos` con integracion IMAP y sugerencia IA opcional.
- Incluye `Informes y KPIs` con filtros, exportacion y graficos.
- Incluye configuracion de sesion por usuario (8h, 12h, 24h, 48h).
