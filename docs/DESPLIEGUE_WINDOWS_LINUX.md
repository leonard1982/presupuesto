# Despliegue Windows y Linux

## Entornos
- Desarrollo Windows: `C:\facilweb\htdocs\PRESUPUESTO`
- Produccion Linux: `/var/www/html/presupuesto`

## Variables de entorno
1. Copiar `.env.example` a `.env`.
2. Ajustar `APP_BASE_URL`, `DB_*`, `APP_ENV`, `APP_DEBUG`.
   - Recomendado: `APP_BASE_URL=AUTO` para deteccion dinamica de host/puerto.
   - Si se define manual, debe incluir puerto cuando aplique.
3. Estrategia dual recomendada:
   - `DB_NAME=presupuestos` (operacion actual)
   - `DB_NAME_LEGACY=presupuestos_legacy` (consulta historica)
   - `DB_NAME_NEW=presupuestos_nuevo` (evolucion del nuevo modelo)

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
