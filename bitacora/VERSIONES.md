# Versiones

## v0.9.7 - 2026-03-10
- UI global: overlay de carga bloqueante con animacion y mensaje Espere por favor....
- Activacion automatica en formularios y enlaces de navegacion/consulta.
- Se evita activacion en acciones locales (anclas, descargas, target _blank, acciones JS).

## v0.9.6 - 2026-03-10
- Bandeja correo: columna # compacta para ganar espacio horizontal.
- Correos: sugerencia removida del panel lateral y movida a popup modal al pulsar Analizar.
- Correos: filtro de relevancia economica para no sugerir mensajes no financieros.

## v0.9.5 - 2026-03-10
- Correos IMAP: correccion de codificacion (UTF-8) para acentos y caracteres especiales.
- Correos: sugerencia movida a modal al pulsar Analizar.
- Correos: control por UID para evitar mezcla de sugerencias entre mensajes.

## v0.9.4 - 2026-03-10
- Bandeja correo: correccion de modal de extracto para registros con codificaciones problematicas.
- Bandeja correo: cuerpo de extracto enviado en Base64 para evitar fallos de parseo.

## v0.9.3 - 2026-03-10
- Bandeja de correos: columna Extracto ahora usa icono con modal de contenido formateado.
- Bandeja de correos: se corrige visualizacion de contenido quoted-printable/HTML en texto legible.

## v0.9.2 - 2026-03-10
- Correos: lectura IMAP robusta con fallback multipuerto (configurado, 993/SSL, 143/TLS y 143/notls).
- Correos: MAIL_INBOX_PORT deja de heredar MAIL_PORT para evitar conflicto SMTP vs IMAP.
- Informes: rediseño completo a 5 reportes corporativos tipo hoja A4 con navegación rápida e impresión.
- Informes: separación visual y funcional del dashboard operativo para evitar duplicación de enfoque.

## v0.9.1 - 2026-03-10
- Correccion de DataTables: se evita warning de conteo de columnas en tablas vacias.
- Nueva sincronizacion automatica de esquema: crea tablas/campos/indices minimos faltantes al iniciar.
- Conexion DB mejorada: crea base de datos automaticamente si no existe y esta habilitado por entorno.
- Nuevas variables de entorno: DB_AUTO_CREATE_DATABASE, DB_AUTO_SCHEMA_SYNC, DB_AUTO_SCHEMA_CHECK_SECONDS.
## v0.9.0 - 2026-03-10
- Sesion configurable por usuario: 8h/12h/24h/48h con base en 12 horas y control de inactividad.
- Movimientos: nueva categoria `Ingreso` y accesos rapidos para registrar ingresos.
- Dashboard: ingresos consolidados (legacy + ingresos nuevos en movimientos).
- Nuevo modulo `Informes y KPIs` con filtros ejecutivos, KPIs, graficos y exportacion.
- Migracion SQL `20260310_0007` para habilitar ingresos en movimientos e indices de rendimiento.
## v0.8.0 - 2026-03-10
- Nuevo modulo Correos: lectura IMAP, sugerencia IA y guardado de movimiento con soporte automatico.
- Integracion de menu/rutas para `correos` y entrada directa para entornos sin mod_rewrite.
- Migracion SQL `20260310_0006` para tabla `correo_importaciones_log` con indices.
- Endurecimiento de SessionManager para evitar Notice de conversion de array a string en PHP 7.2.
## v0.7.4 - 2026-03-10
- Movimientos PC: columna # compactada al ancho del numero.
- Movimientos PC: columna Detalle convertida a icono con popup de detalle por registro.
## v0.7.3 - 2026-03-09
- Fix: boton de panel lateral en movimientos ahora alterna correctamente ocultar/mostrar.
## v0.7.2 - 2026-03-09
- Movimientos: ocultado horizontal del panel lateral derecho para liberar ancho real de la lista.
- Movimientos: boton de panel movido a la barra superior de acciones para mostrar/ocultar aun con panel cerrado.
- Ajuste responsive: el colapso horizontal no afecta comportamiento movil existente.
## v0.7.1 - 2026-03-09
- Movimientos: filtros ocultables con estado inicial oculto en movil.
- Movimientos: panel lateral de resumen con boton ocultar/mostrar en escritorio.
- Movimientos: columna ID retirada del listado y tarjetas moviles.
- Ajuste tecnico: indices de columnas en filtro DataTables actualizados tras retirar ID.
## v0.7.0 - 2026-03-09
- Movimientos: barra de filtros sticky con rangos rapidos Hoy/Semana/Mes/Todo y persistencia por usuario.
- Movimientos escritorio: panel lateral de resumen rapido por seleccion de fila.
- Soportes: se reemplaza modal invasivo por panel contextual inline en la columna de soportes.
- Movimientos movil: lista con 3 acciones primarias visibles (ver, editar, eliminar) y detalle ampliado con soportes/ticket.
- Formulario de movimientos: nuevo modo de captura rapida con campos avanzados plegables y botones de seleccion rapida.
- Atajos de teclado habilitados para productividad diaria: N (nuevo), F (buscar), E (exportar Excel en movimientos).
- Preferencias de cantidad de registros por tabla persistidas por usuario en catalogos, dashboard y movimientos.
## v0.6.12 - 2026-03-09
- Catalogos de Clasificaciones y Medios de pago: columna numerica # con ancho compacto para mejor aprovechamiento horizontal.

## v0.6.11 - 2026-03-09
- Dashboard movil: seccion de movimientos recientes mejorada con tarjetas legibles y sin desbordes.
- En movil se oculta tabla y se muestra listado adaptado; en escritorio se conserva DataTables.

## v0.6.10 - 2026-03-09
- Select2 habilitado en filtros de movimientos con muchos items (clasificacion y tipo/medio).
- Inicializacion Select2 optimizada: evita doble carga y activa buscador solo cuando hay varias opciones.

## v0.6.9 - 2026-03-09
- Tema oscuro: fondo global ajustado a un tono mas oscuro para mejor contraste visual.

## v0.6.8 - 2026-03-09
- Movimientos: filtros por fecha, clasificacion, categoria y tipo/medio en escritorio y movil.
- En movil la fecha inicia en el dia actual para ver movimientos del dia.
- Exportacion PDF mejorada con cabecera, fecha/hora de impresion y paginacion.
- Ajuste visual de tabla PDF para reducir desbordes de informacion.

## v0.6.7 - 2026-03-09
- Listados de Clasificaciones y Medios de pago sin columna ID visible.
- Se conserva numeracion por fila con columna #.
- Verificacion de SQL base: en este modulo no se requiere campo clasificacion2.

## v0.6.6 - 2026-03-09
- Mejora del encabezado en movil: distribucion ordenada de titulo, acciones y buscador.
- Compactacion visual del bloque de usuario en topbar mobile.
- Ajuste de "page-header" para botones principales de ancho completo en celular.

## v0.6.5 - 2026-03-09
- Confirmacion de eliminacion en modal visual profesional (sin popup nativo del navegador).
- Integracion en movimientos escritorio y movil con mensaje claro de accion irreversible.
- Cierre del modal por boton, clic fuera o tecla ESC.

## v0.6.4 - 2026-03-09
- Nuevo listado movil de movimientos con fecha visible y acciones rapidas por registro.
- Boton "Ver detalle" en mobile con popup completo del movimiento.
- Acciones en lista movil: soportes, ticket, editar y eliminar con confirmacion.

## v0.6.3 - 2026-03-09
- Opcion visible de "Instalar app" en login y panel principal.
- Flujo de instalacion PWA con prompt nativo cuando aplica y guia simple para iPhone/iPad.
- Manifest actualizado para subruta del proyecto y accesos directos a Dashboard/Nuevo movimiento.
- Iconos PWA agregados (192, 512 y apple-touch-icon).
- Service Worker con cache ligera de assets y limpieza de versiones anteriores.

## v0.6.2 - 2026-03-09
- Correccion completa de pegado de imagenes desde portapapeles (Ctrl + V) en movimientos.
- Nuevo fallback backend/frontend para navegadores que no permiten asignar archivos pegados al input file.
- Validacion de seguridad para soportes pegados: base64, tamano, extension y MIME compatible.
- Persistencia de soportes pegados en ingresos_detalle con almacenamiento fisico en storage/uploads/soportes/{id}.

## v0.6.1 - 2026-03-09
- Fix de compatibilidad en sesiones para PHP 7.2 evitando Notice en session_set_cookie_params.

## v0.6.0 - 2026-03-09
- Envio de informe mensual por correo desde dashboard con servicio SMTP integrado.
- Nuevo asesor KPI con IA (reglas internas y soporte OpenAI opcional).
- Dashboard mejorado con formularios operativos para envio y recomendaciones.

## v0.5.4 - 2026-03-09
- Modal visual de confirmacion al guardar movimiento, con cierre por boton, fondo y ESC.
- Selector de archivos de soportes mejorado con boton, contador y listado de archivos elegidos.

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







