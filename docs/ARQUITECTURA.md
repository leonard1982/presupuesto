# Arquitectura Base - PRESUPUESTO

## Enfoque
Monolito modular compatible con PHP 7.2 a 8.2, preparado para crecimiento incremental.

## Capas
- `public/`: punto de entrada HTTP y assets.
- `app/Core`: bootstrap, entorno, DB, sesion, CSRF, render.
- `app/Modules`: modulos por dominio funcional.
- `app/Helpers`: utilidades reutilizables.
- `storage/`: logs, cache y archivos subidos.
- `sql/`: migraciones versionadas y rollback.
- `bitacora/`: trazabilidad obligatoria.

## Regla de compatibilidad de rutas
- No usar rutas absolutas fijas de sistema operativo.
- Todas las rutas se construyen desde `PROJECT_ROOT`.
- Separador de directorio manejado por `DIRECTORY_SEPARATOR`.

## Regla de evolucion
- No romper tablas ni campos existentes sin estrategia de compatibilidad.
- Toda mejora grande entra por migracion SQL reversible.
