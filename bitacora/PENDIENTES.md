# Pendientes Tecnicos

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
- Agregar gestion de soportes (eliminar soporte individual y vista previa de imagen/PDF).
- Conectar dashboard con comparativo contra presupuesto en tiempo real.

## API y automatizacion
- Implementar API v1 con autenticacion de token.
- Implementar bandeja de importacion y revision manual.
- Preparar endpoint de sugerencias de IA con trazabilidad de aceptacion/rechazo.
