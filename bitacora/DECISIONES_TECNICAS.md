# Decisiones Tecnicas

## 2026-03-09

### Decision: Monolito modular
- Motivo: permite escalar por modulos sin complejidad operativa de microservicios.
- Impacto: mayor control de mantenibilidad y despliegue sencillo.

### Decision: Configuracion centralizada por entorno
- Motivo: compatibilidad Windows/Linux y despliegues sin rutas rigidas.
- Impacto: reduce errores de migracion entre desarrollo y produccion.

### Decision: Compatibilidad MD5 heredada con migracion gradual
- Motivo: no romper usuarios existentes.
- Impacto: mejora seguridad progresiva sin corte de servicio.

### Decision: Front controller y carpetas sensibles protegidas
- Motivo: reducir superficie de ataque y acceso accidental a archivos internos.
- Impacto: fortalece seguridad base del sistema.
