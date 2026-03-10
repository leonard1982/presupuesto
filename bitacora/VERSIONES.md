# Versiones

## v0.5.3 - 2026-03-09
- Formato numerico en tiempo real para valor, valor neto y saldo en formulario de movimientos.
- Regla de guardado: si valor_neto llega vacio, toma automaticamente el valor principal.

## v0.5.2 - 2026-03-09
- Correccion CSS para que el modal de soportes inicie oculto y pueda cerrarse correctamente.

## v0.5.1 - 2026-03-09
- Correccion del modal de soportes para renderizar archivos desde data-supports-json y evitar mensajes vacios en tablas con DataTables.

## v0.5.0 - 2026-03-09
- Columna de soportes con icono contextual y modal emergente para ver/descargar archivos.
- Nuevo ticket visual por movimiento (movimientos/ticket) en formato media hoja listo para impresion.
- Exportacion de listados a Excel y PDF con DataTables Buttons (JSZip + pdfmake).
- Exclusiones de columnas operativas en exportacion para reportes limpios.

## v0.4.2 - 2026-03-09
- Correccion integral de contraste en modo oscuro para formularios, DataTables y Select2.
- Mejora de legibilidad en columna de soportes y botones tipo ghost en tema dark.
- Ajuste de estados hover y pagina activa en paginacion para mayor claridad visual.

## v0.4.1 - 2026-03-09
- Soportes de movimientos alineados al modelo legacy real en `ingresos_detalle` (multiples adjuntos por registro).
- Migracion `20260309_0005` para indice compuesto de rendimiento en soportes y rollback asociado.
- Botones de acciones en movimientos en formato solo icono (editar/eliminar).
- Correccion de contraste en paginacion DataTables al priorizar estilos propios de `app.css`.
- Boton de modo claro/oscuro disponible en topbar junto al menu de usuario.

## v0.4.0 - 2026-03-09
- Paginacion de tablas centrada con iconos y selector de cantidad configurable hasta 1000 o Todos.
- Listados sin scroll horizontal, con filas numeradas por pagina y valores sin decimales.
- Movimientos con acciones de editar y eliminar (eliminacion con confirmacion).
- Soportes multiples en movimientos con validacion de extension, MIME y tamano.
- Descarga segura de soportes desde ruta protegida (`movimientos/soporte`).

## v0.3.0 - 2026-03-09
- Sidebar izquierdo colapsable en escritorio y menu lateral desplegable en movil.
- Iconografia profesional en titulos, botones y navegacion.
- Dashboard con graficos de tendencia mensual y distribucion por clasificacion.
- Tablas con DataTables responsive (filtros, ordenamiento y paginacion).
- Mejora visual de formularios con enfoque profesional para uso diario.

## v0.2.0 - 2026-03-09
- Dashboard renovado sin texto tecnico de implementacion.
- Menu responsive profesional con buscador rapido y menu de usuario.
- Modulo de movimientos habilitado (listado + formulario de alta con validacion frontend/backend).
- Modulo de clasificaciones habilitado (alta + busqueda + listado).
- Modulo de medios de pago habilitado (alta + busqueda + listado).
- Integracion de Select2 para busqueda en selects de alto volumen.
- Migracion SQL `20260309_0004` para indices de rendimiento en consultas del nuevo flujo UI.

## v0.1.2 - 2026-03-09
- Rediseno de login con experiencia profesional y responsive.
- Opcion de recordar usuario agregada de forma segura y compatible con PHP 7.2-8.2.
- Mejora de validacion frontend de acceso.

## v0.1.1 - 2026-03-09
- Correccion de `base_url` dinamica en accesos directos (`/login/`, `/dashboard/`, `/logout/`).
- Compatibilidad reforzada para puertos dinamicos en desarrollo y 80/443 en produccion.

## v0.1.0 - 2026-03-09
- Fase 2 base tecnica inicial creada.
- Estructura, seguridad base, auth inicial y bitacora habilitadas.

## Convencion de versionado
- `MAJOR.MINOR.PATCH`
- `MAJOR`: cambios incompatibles.
- `MINOR`: funcionalidades nuevas compatibles.
- `PATCH`: correcciones y ajustes sin romper API funcional.






