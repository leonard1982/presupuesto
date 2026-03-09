# Modulo Auth

## Objetivo
Implementar autenticacion segura, mantenible y compatible con datos legacy.

## Estado actual
- Login y logout base operativos.
- Sesion segura inicial.
- CSRF en formulario de login.
- Compatibilidad de validacion con hash moderno y MD5 legacy.
- Migracion automatica a hash moderno cuando existen columnas nuevas.

## Proxima evolucion
- Limite de intentos fallidos y bloqueo temporal.
- Cambio de contrasena y politica de complejidad.
- Recordar usuario con consentimiento.
- Auditoria de accesos y alertas.
