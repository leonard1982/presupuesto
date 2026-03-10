# Decisiones Tecnicas

## 2026-03-10

### Decision: Duracion de sesion ampliada y configurable por usuario
- Motivo: el uso operativo supera jornadas de 8 horas y requiere continuidad sin cierres frecuentes.
- Impacto: sesion base de 12 horas con opciones configurables y control de inactividad por preferencia segura.

### Decision: Registrar ingresos en `gastos_costos` para nuevo flujo operativo
- Motivo: unificar captura diaria en un formulario rapido y evitar doble interfaz para operador.
- Impacto: se habilita categoria `Ingreso` sin perder compatibilidad con tabla legacy `ingresos`.

### Decision: Informes consolidan fuentes legacy + nuevas
- Motivo: mantener consistencia historica mientras el sistema evoluciona por fases.
- Impacto: KPIs y reportes usan `ingresos` + `gastos_costos` y permiten lectura ejecutiva completa.
## 2026-03-10

### Decision: Bandeja de correos con soporte IMAP nativo
- Motivo: integrar correos de bancos/proveedores sin agregar dependencias pesadas.
- Impacto: se usa extension IMAP de PHP y configuracion por `.env`, compatible con PHP 7.2-8.2.

### Decision: Sugerencia hibrida para correos (reglas + OpenAI opcional)
- Motivo: garantizar funcionamiento incluso sin API externa.
- Impacto: continuidad operativa con prediccion local y mejora de precision cuando OpenAI esta disponible.

### Decision: Soporte de correo como imagen PNG en almacenamiento privado
- Motivo: dejar evidencia trazable del contenido importado sin exponer archivos en `/public`.
- Impacto: cada movimiento creado desde correo queda con respaldo en `ingresos_detalle`.
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

### Decision: DataTables en listados operativos
- Motivo: agregar filtros, ordenamiento y paginacion sin rehacer backend en esta fase.
- Impacto: valor rapido para uso diario con experiencia profesional responsive.

### Decision: Chart.js para dashboard inicial
- Motivo: mostrar tendencia y distribucion con visualizacion clara para decisiones rapidas.
- Impacto: dashboard util desde fase base y escalable a KPIs mas avanzados.

### Decision: Sidebar lateral colapsable
- Motivo: reducir ruido visual y priorizar espacio de trabajo en pantallas pequenas y medianas.
- Impacto: navegacion mas profesional y mejor productividad en escritorio/movil.

### Decision: Soportes en almacenamiento privado fuera de /public
- Motivo: evitar descarga directa no autorizada por URL publica.
- Impacto: los soportes se sirven por endpoint autenticado con control de permisos.

### Decision: Paginacion centrada con iconos y filas numeradas
- Motivo: mejorar rapidez visual y ergonomia en uso diario intensivo.
- Impacto: tablas mas claras, compactas y consistentes en todos los modulos.

### Decision: Usar `ingresos_detalle` como origen de soportes multiples
- Motivo: el modelo existente ya declara que `gastos_costos.soporte` no es el canal activo y centraliza adjuntos en tabla de detalle.
- Impacto: compatibilidad real con datos legacy, multiples archivos por movimiento y trazabilidad de soporte por registro.

### Decision: Cargar CSS propio despues de librerias UI
- Motivo: evitar que DataTables/Select2 pisen estilos de contraste, paginacion y componentes del sistema.
- Impacto: consistencia visual en tema claro/oscuro y mejor legibilidad de pagina activa.

### Decision: Modal de soportes en listado en lugar de render directo de archivos
- Motivo: mejorar legibilidad de tabla y evitar celdas extensas cuando un movimiento tiene varios soportes.
- Impacto: columna compacta, acceso rapido por icono y flujo mas limpio para ver/descargar adjuntos.

### Decision: Formato ticket en layout de impresion separado
- Motivo: tener comprobante visual de media hoja sin ruido de navegacion y con salida de impresion consistente.
- Impacto: nueva vista operativa para revisiones, validaciones y soporte documental por movimiento.

### Decision: Exportacion Excel/PDF con DataTables Buttons
- Motivo: entregar exportacion inmediata en listados sin construir reporteria backend adicional en esta fase.
- Impacto: reportes operativos rapidos; se excluyen columnas de accion para mantener archivos limpios.

### Decision: Formateo numerico visual con normalizacion backend
- Motivo: mejorar velocidad de digitacion y evitar errores al ingresar montos grandes en movimientos.
- Impacto: los campos monetarios muestran separadores de miles al escribir, y backend guarda montos limpios; `valor_neto` se autocompleta con `valor` cuando llega vacio.

### Decision: Confirmacion de guardado con modal UI
- Motivo: entregar feedback claro y profesional despues de registrar movimientos.
- Impacto: reduce dudas del usuario al guardar y permite continuar con acciones rapidas sin perder contexto.

### Decision: Servicio SMTP interno sin dependencia de librerias pesadas
- Motivo: mantener compatibilidad PHP 7.2-8.2 en entorno actual sin requerir Composer en esta fase.
- Impacto: envio de informes funcional desde dashboard, con configuracion centralizada en `.env`.

### Decision: Asesor KPI hibrido (reglas + OpenAI opcional)
- Motivo: asegurar recomendaciones incluso si la API externa falla o no esta disponible.
- Impacto: continuidad operativa con fallback local y mejora progresiva cuando OpenAI responde.


