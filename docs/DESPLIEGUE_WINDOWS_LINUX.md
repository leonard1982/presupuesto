# Despliegue Windows y Linux

## Entornos
- Desarrollo Windows: `C:\facilweb\htdocs\PRESUPUESTO`
- Produccion Linux: `/var/www/html/presupuesto`

## Variables de entorno
1. Copiar `.env.example` a `.env`.
2. Ajustar `APP_BASE_URL`, `DB_*`, `APP_ENV`, `APP_DEBUG`.

## Apache
- Mantener `mod_rewrite` activo.
- Permitir `.htaccess` (`AllowOverride All`).
- Recomendado: apuntar VirtualHost a `public/`.

## Permisos Linux
- Carpeta `storage/` con permisos de escritura para usuario de Apache.
- Ejemplo: `www-data` para Ubuntu.

## Validaciones previas a despliegue
- Sintaxis PHP sin errores.
- Conexion DB correcta.
- Bitacora actualizada.
- Migraciones SQL aplicadas en orden.
