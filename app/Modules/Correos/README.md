# Modulo Correos

## Objetivo
Convertir correos de la bandeja IMAP en movimientos sugeridos con apoyo de reglas e IA opcional.

## Flujo actual
1. Lista correos recientes desde IMAP.
2. Permite buscar y seleccionar un correo.
3. Genera sugerencia de formulario (reglas + OpenAI opcional).
4. Permite ajustar datos y guardar movimiento.
5. Genera soporte PNG del contenido del correo y lo registra en `ingresos_detalle`.
6. Registra trazabilidad en `correo_importaciones_log` cuando la tabla existe.

## Dependencias
- Extension PHP `imap` habilitada.
- Extension PHP `gd` para generar soporte PNG.
- Configuracion `MAIL_INBOX_*` en `.env`.
- Configuracion `OPENAI_API_KEY_PRESUPUESTO_PERSONAL` opcional.
