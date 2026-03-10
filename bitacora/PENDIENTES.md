# Pendientes Tecnicos

## Informes y datos
- Definir informe programado por correo con adjuntos PDF/XLS por rango configurable.
- Incluir comparativo presupuesto vs ejecucion por cada presupuesto activo.
- Agregar KPI de desviacion mensual contra promedio 3/6 meses.

## Ingresos
- Evaluar migracion gradual de registros legacy de `ingresos` hacia flujo unificado.
- Revisar necesidad de campo de usuario en ingresos legacy para trazabilidad completa.
## Bandeja de correos
- Agregar paginacion server-side para buzones grandes y filtros por remitente/asunto/fecha.
- Permitir elegir adjunto original del correo (ademas del snapshot) cuando el servidor IMAP lo entregue.
- Registrar aprobacion/rechazo de sugerencia para entrenamiento futuro de clasificacion IA.
## Seguridad
- Implementar limite de intentos fallidos en login.
- Implementar cambio de contrasena con politicas.
- Implementar cierre de sesion por inactividad configurable.
- Agregar tokens CSRF a accion de logout en layout.

## Datos y rendimiento
- Ejecutar migracion SQL 0001 en `presupuestos` para completar indices y columnas de hash moderno.
- Rehacer vista `v_ingresos_gastos` con estrategia de collation compatible.
- Definir politica de archivado de `sc_log`.

## Modulos funcionales
- Agregar historial de cambios (auditoria) para edicion/eliminacion de movimientos.
- Completar gestion de soportes avanzada (eliminar soporte individual desde edicion y vista previa embebida dentro del formulario).
- Conectar dashboard con comparativo contra presupuesto en tiempo real.

## API y automatizacion
- Implementar API v1 con autenticacion de token.
- Implementar bandeja de importacion y revision manual.
- Preparar endpoint de sugerencias de IA con trazabilidad de aceptacion/rechazo.

## Reportes y comunicacion
- Programar envio automatico de informes por correo (diario/semanal/mensual).
- Agregar adjuntos XLS/PDF en correo y bitacora de entregas por destinatario.


