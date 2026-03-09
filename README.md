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

## Seguridad
- Directorios sensibles bloqueados por `.htaccess`.
- Login con CSRF y sesion segura.
- Base lista para migracion gradual MD5 -> hash moderno.

## Nota de compatibilidad
- Funciona con PHP 7.2 a 8.2.
- Desarrollo actual validado en PHP 7.3.
