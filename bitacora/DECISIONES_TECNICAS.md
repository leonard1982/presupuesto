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

### Decision: Compatibilidad dual de base de datos
- Motivo: mantener operacion legacy y evolucionar en paralelo sin rehacer todo.
- Impacto: permite migracion por fases y soporte simultaneo MariaDB 10.1 / motores nuevos.

### Decision: Recordar solo usuario y no contrasena
- Motivo: evitar almacenamiento inseguro de credenciales en cliente.
- Impacto: mejora experiencia de acceso manteniendo criterio de seguridad.

### Decision: Select2 en formularios con alto volumen de opciones
- Motivo: acelerar seleccion de clasificaciones, medios y presupuestos con busqueda inmediata.
- Impacto: mejora productividad diaria sin imponer rediseño de base de datos.
- Nota: se mantiene degradacion funcional si la libreria externa no carga.

### Decision: Busqueda por prefijo en catalogos
- Motivo: permitir uso de indices B-Tree y evitar busquedas lentas por `%texto%`.
- Impacto: mejor rendimiento en clasificaciones y medios de pago.
