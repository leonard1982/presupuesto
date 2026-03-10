# Cambios Generales

## 2026-03-09 - Filtros moviles ocultos por defecto y panel lateral ocultable
- Movimientos: se agrega boton para mostrar/ocultar filtros y se permite plegar el bloque completo.
- En movil, los filtros quedan ocultos por defecto al entrar al modulo para priorizar la lista.
- Se conserva la preferencia del usuario para el estado de filtros (visible/oculto) en visitas siguientes.
- En escritorio, el panel lateral de resumen ahora tiene boton para ocultar/mostrar.
- Se conserva la preferencia del usuario para el panel lateral (visible/oculto).
- Se elimina la columna ID de la lista de movimientos (tabla y tarjetas moviles).
- Se ajusta el filtrado de DataTables a la nueva estructura de columnas sin ID.
## 2026-03-09 - Productividad integral PC y movil en movimientos
- Se implementa barra de filtros sticky en movimientos con acceso rapido visible durante scroll.
- Se agregan filtros rapidos por fecha: Hoy, Semana, Mes y Todo.
- Se agrega panel lateral de resumen rapido en escritorio al seleccionar filas del listado.
- Se reemplaza popup invasivo de soportes por selector contextual no bloqueante en la columna de soportes.
- Se redisenia la lista movil para dejar 3 acciones primarias visibles por registro:
  - ver detalle
  - editar
  - eliminar
- Se amplian detalles moviles con soportes y accesos secundarios (ticket/ver-descargar soporte) dentro del popup.
- Se agrega modo de captura rapida en formulario de movimientos para ocultar campos avanzados y acelerar digitacion.
- Se agregan atajos de teclado para uso diario:
  - N nuevo movimiento
  - F foco de busqueda
  - E exportar Excel en movimientos
- Se implementa persistencia de preferencias por usuario (local storage con namespace por login):
  - tema
  - filtros de movimientos
  - cantidad de registros por tabla (cuando el listado define clave de preferencia).
- Se extiende soporte de preferencias de paginacion a:
  - movimientos
  - clasificaciones
  - medios de pago
  - movimientos recientes del dashboard.
## 2026-03-09 - Fase 2 base tecnica inicial
- Se crea estructura de carpetas profesional y modular.
- Se agrega configuracion centralizada por entorno (`.env`).
- Se implementa bootstrap, autoloader y front controller.
- Se agregan medidas de seguridad base con `.htaccess`.
- Se implementa login/logout inicial con CSRF y sesion segura.
- Se agrega compatibilidad con migracion gradual MD5 -> hash moderno.
- Se crean documentos base de arquitectura y despliegue.
- Se crean scripts SQL de migracion y rollback.

## 2026-03-09 - Correccion acceso local 403
- Se agrega `index.php` en raiz como fallback para entornos XAMPP con configuracion parcial de reescritura.
- Se ajusta `DirectoryIndex` en `.htaccess` a `index.php public/index.php`.
- Objetivo: evitar error 403 por ausencia de indice cuando Apache no procesa reescritura como se espera.

## 2026-03-09 - Estrategia dual de base de datos
- Se crea base `presupuestos_legacy` para conservar y consultar datos historicos.
- Se crea base `presupuestos_nuevo` para evolucionar el nuevo modelo sin romper legado.
- En `presupuestos_nuevo` se crean tablas de control de migracion:
  - `sistema_control_versiones`
  - `migracion_ejecuciones`
  - `migracion_mapeo_registros`
- Se crean vistas de consulta de legado desde base nueva:
  - `legacy_gastos_costos`
  - `legacy_ingresos`
  - `legacy_presupuesto`
- Se versiona SQL reproducible en `sql/migrations/20260309_0002_dual_database_setup.sql` y rollback asociado.

## 2026-03-09 - Compatibilidad cruzada MariaDB 10.1 y MySQL nuevos
- Se agrega migracion `20260309_0003_new_database_legacy_compatible_base.sql`.
- Se replica en `presupuestos_nuevo` estructura base legacy requerida para compatibilidad de consultas antiguas.
- Se crea tabla `compatibilidad_instancia` para trazabilidad de version de motor y charset.
- Se crea tabla `movimiento_unificado` como base evolutiva sin romper el modelo anterior.
- Se crea `catalogo_medios_pago` consolidado para normalizacion incremental.
- Se agrega documentacion tecnica en `docs/COMPATIBILIDAD_MARIADB_MYSQL.md`.

## 2026-03-09 - Ajuste dinamico de URL base y puertos
- Se revierte cambio no solicitado de escucha en puerto 80 de Apache local.
- Se establece deteccion dinamica de `base_url` cuando `APP_BASE_URL=AUTO`.
- La aplicacion ahora construye URLs con host y puerto reales del request.
- Se mantiene compatibilidad para desarrollo con puerto variable (ejemplo 9192) y produccion 80/443.
- Se agregan entradas directas `login/`, `dashboard/` y `logout/` para evitar dependencia total de `mod_rewrite`.

## 2026-03-09 - Correccion de base URL en rutas directas
- Se corrige deteccion de `base_url` para accesos directos como `/login/` sin agregar subruta incorrecta.
- La deteccion ahora prioriza `DOCUMENT_ROOT` + `PROJECT_ROOT` para obtener ruta real del proyecto.
- Se conserva fallback por `SCRIPT_NAME` para escenarios donde `DOCUMENT_ROOT` no es confiable.
- Se valida compatibilidad de rutas en Windows y Linux, incluyendo puertos dinamicos.

## 2026-03-09 - Mejora UX login y recordar usuario
- Se elimina el texto tecnico de migracion MD5 en la pantalla de login para evitar ruido al usuario final.
- Se implementa opcion `Recordar usuario en este dispositivo` con cookie segura (sin guardar contrasena).
- Se agrega compatibilidad de cookies para PHP 7.2 a 8.2 mediante `CookieManager`.
- Se rediseña el login con interfaz profesional y responsive (desktop/movil).
- Se mejora validacion frontend del formulario con mensajes en pantalla y boton para ver/ocultar contrasena.

## 2026-03-09 - Navegacion profesional y modulos operativos iniciales
- Se reemplaza el dashboard tecnico por un panel visual orientado a operacion (KPIs, accesos rapidos y movimientos recientes).
- Se implementa menu responsive con buscador global, accesos de usuario y navegacion por modulos.
- Se crean modulos funcionales iniciales:
  - `Movimientos`: listado y registro de gastos/costos/compras con validacion frontend/backend.
  - `Clasificaciones`: alta y busqueda.
  - `Medios de pago`: alta y busqueda.
- Se agregan rutas directas sin dependencia de `mod_rewrite` para:
  - `/movimientos`
  - `/movimientos/nuevo`
  - `/clasificaciones`
  - `/medios-pago`
- Se integra Select2 para selects con busqueda en formularios de alta concurrencia de opciones.
- Se agrega migracion `20260309_0004_ui_navigation_and_catalog_indexes.sql` con rollback para rendimiento de dashboard/catalogos/menu.

## 2026-03-09 - Sidebar lateral, iconografia y analitica visual
- Se reemplaza el menu horizontal por un sidebar izquierdo colapsable para escritorio y desplegable para movil.
- Se agrega iconografia consistente en titulo del software, menu lateral, acciones de usuario y botones principales.
- Se mejora presentacion de formularios y encabezados de modulo con estilo visual unificado.
- Se integra Chart.js para dashboard con graficos de tendencia mensual y distribucion por clasificacion.
- Se integra DataTables responsive para tablas con filtro, ordenamiento, paginacion y control de cantidad de filas por pagina.

## 2026-03-09 - Edicion, eliminacion y soportes de movimientos
- Se agrega edicion de movimientos (`movimientos/editar`) con formulario reutilizable.
- Se agrega eliminacion de movimientos (`movimientos/eliminar`) con confirmacion obligatoria desde interfaz.
- Se agrega carga de soportes multiples en movimientos con validacion frontend y backend:
  - extension permitida
  - MIME permitido
  - tamano maximo por archivo
- Los soportes se guardan en almacenamiento privado (`storage/uploads/soportes`).
- Se agrega descarga segura de soportes por endpoint autenticado (`movimientos/soporte`).
- Se agrega columna de acciones en listado para editar/eliminar y enlaces de descarga de soportes.

## 2026-03-09 - Tablas sin scroll horizontal y paginacion centrada
- Se ajusta DataTables para evitar scroll horizontal forzado y mejorar adaptacion responsive.
- Se centraliza paginacion con iconos de navegacion y sin textos largos.
- Se habilita selector de cantidad de registros: 10, 20, 30, 50, 100, 200, 300, 500, 1000 y Todos.
- Se numeran registros en todas las tablas visibles por pagina.
- Se ajusta formato monetario sin decimales en dashboard y movimientos.

## 2026-03-09 - Soportes alineados al modelo real y mejora de contraste UI
- Se corrige el modulo de soportes para usar `ingresos_detalle` como fuente oficial de multiples archivos por movimiento.
- Se elimina dependencia funcional del campo `gastos_costos.soporte` para nuevas cargas y consultas.
- Se agrega migracion `20260309_0005_supports_ingresos_detalle_indexes.sql` con indice compuesto `(id_ingreso, id)` para acelerar listados y descarga por soporte.
- Se agrega rollback `20260309_0005_supports_ingresos_detalle_indexes_rollback.sql`.
- Se dejan botones de editar/eliminar solo con icono en listados operativos.
- Se corrige contraste de paginacion cargando `app.css` despues de estilos de librerias (DataTables/Select2), evitando texto oscuro en pagina activa.
- Se mantiene el boton de modo claro/oscuro en topbar junto al menu de usuario.

## 2026-03-09 - Correccion integral de contraste en modo oscuro
- Se ajustan etiquetas de formularios (`label`), chips de encabezado y textos de soporte para mejorar legibilidad.
- Se corrigen colores de controles en modo oscuro: `input`, `select`, `textarea` y placeholders.
- Se corrige contraste de paginacion y estados hover en DataTables.
- Se corrige contraste de Select2 en seleccion, dropdown, busqueda y resultados.
- Se mejora contraste de botones `btn-ghost` usados en columna de soportes.

## 2026-03-09 - Soportes por icono, ticket visual y exportacion de listados
- En la columna de soportes se reemplaza el listado visible por un icono de clip solo cuando hay archivos.
- Al hacer clic en el icono se abre modal emergente con listado de archivos y acciones de ver/descargar.
- Se agrega accion por fila para abrir ticket del movimiento en formato media hoja con diseno imprimible.
- Se agrega nueva ruta `movimientos/ticket` y entrada directa `movimientos/ticket/index.php` para entornos sin reescritura.
- Se integra exportacion a Excel y PDF en tablas DataTables mediante Buttons + JSZip + pdfmake.
- Se excluyen columnas operativas (`#`, soportes, acciones) de la exportacion para evitar ruido en reportes.

## 2026-03-09 - Correccion modal de soportes en movimientos
- Se corrige apertura de modal para tomar soportes desde atributo JSON del boton (`data-supports-json`).
- Se evita dependencia de nodos ocultos en tabla para compatibilidad total con DataTables responsive/paginacion.
- Se mantiene fallback de lectura desde DOM para compatibilidad con render previo.

## 2026-03-09 - Ajuste de cierre de modal de soportes
- Se corrige regla CSS para que la clase `hidden` prevalezca sobre `modal-overlay`.
- Se evita que el popup de soportes quede abierto al cargar la pantalla.

## 2026-03-09 - Formato numerico y valor neto automatico en movimientos
- Se agrega formato de miles en tiempo real para `valor`, `valor_neto` y `saldo` durante digitacion.
- Se normaliza captura de montos en backend para admitir entradas con separadores visuales.
- Si `valor_neto` llega vacio al guardar, se asigna automaticamente el mismo valor de `valor`.
- Si `valor_neto` viene diligenciado, se conserva y guarda el valor digitado.

## 2026-03-09 - Notificacion visual de guardado y selector de archivos mejorado
- Se reemplaza aviso simple de guardado por modal de confirmacion visual con acciones rapidas.
- El modal de confirmacion puede cerrarse por boton, clic fuera o tecla ESC.
- Se redisenia selector de soportes con boton `Elegir archivos`, contador y listado de archivos seleccionados.

## 2026-03-09 - Envio de informes por correo y consejo KPI con IA
- Se habilita formulario en dashboard para enviar informe mensual por correo con estilo operativo profesional.
- Se implementa servicio SMTP propio (`SmtpMailer`) compatible con credenciales por `.env`.
- Se implementa compositor de informe (`DashboardReportComposer`) con resumen, top clasificaciones y movimientos recientes.
- Se agrega opcion de `Generar consejo IA` en dashboard con panel de recomendaciones KPI.
- El asesor KPI funciona en modo hibrido: reglas internas + OpenAI opcional si hay API key.
- Se agregan validaciones frontend/backend para los formularios de correo y consejo KPI.

## 2026-03-09 - Correccion compatibilidad de sesiones en PHP 7.2
- Se corrige `SessionManager` para evitar `Notice: Array to string conversion` en servidores con PHP 7.2.
- `session_set_cookie_params` ahora usa estrategia dual:
  - PHP >= 7.3 con arreglo y `samesite`.
  - PHP 7.2 con firma legacy y `samesite` en `path`.

## 2026-03-09 - Soportes por portapapeles en movimientos (Ctrl + V)
- Se completa flujo end-to-end para soportes pegados desde portapapeles en formulario de movimientos.
- El frontend ya no depende solo de `input[type=file]`: si el navegador no permite asignar `DataTransfer`, usa fallback JSON (`soportes_clipboard_json`).
- El backend procesa y valida los soportes pegados con reglas de seguridad:
  - base64 valido
  - tamano maximo por archivo
  - extension permitida
  - MIME detectado y compatible con extension
- Los soportes pegados se guardan en `storage/uploads/soportes/{id_movimiento}` y se registran en `ingresos_detalle`.
- Se agregan rollbacks de integridad para guardar/actualizar:
  - limpieza de archivos escritos
  - limpieza de registros de soporte insertados parcialmente
  - mantenimiento de consistencia en errores.

## 2026-03-09 - Instalacion en celular (PWA) habilitada
- Se habilita opcion visible de `Instalar app` en cabecera para usuarios autenticados y pantalla de login.
- Se agrega modal guiado de instalacion:
  - instalacion directa en Android/Chrome cuando el navegador la permite
  - guia para iPhone/iPad por `Agregar a pantalla de inicio`
- Se actualiza `manifest.webmanifest` para subruta del proyecto (`../`) con accesos directos a dashboard y nuevo movimiento.
- Se agregan iconos PWA locales:
  - `public/assets/pwa/icon-192.png`
  - `public/assets/pwa/icon-512.png`
  - `public/assets/pwa/apple-touch-icon.png`
- Se mejora `sw.js` con cache ligera de recursos estaticos y limpieza de versiones antiguas.
- Se activa PWA por defecto en configuracion (`APP_ENABLE_PWA` con valor default `true`, configurable por `.env`).

## 2026-03-09 - Vista movil de movimientos con popup de detalle
- Se agrega listado movil dedicado para movimientos, evitando render incomodo de tabla en pantallas pequenas.
- Cada registro movil muestra fecha, identificador, clasificacion y valor en formato compacto.
- Se agrega boton de `Ver detalle` por registro que abre popup con todos los datos del movimiento.
- Se mantienen acciones operativas directas en la lista movil:
  - ver soportes
  - ver ticket
  - editar
  - eliminar con confirmacion.
- La tabla DataTables se conserva para escritorio y el nuevo listado se activa solo en mobile por CSS responsive.

## 2026-03-09 - Confirmacion visual profesional para eliminar registros
- Se reemplaza `window.confirm` por modal visual de confirmacion reutilizable.
- La eliminacion de movimientos ahora muestra cuadro de confirmacion elegante con:
  - titulo
  - mensaje claro de irreversible
  - boton principal de eliminacion
  - boton cancelar.
- El modal se puede cerrar por boton, clic fuera o tecla ESC.
- Se mantiene proteccion por backend y token CSRF en formularios de eliminacion.

## 2026-03-09 - Mejora de distribucion del encabezado en movil
- Se reorganiza la barra superior en mobile con estructura de grilla:
  - titulo y menu a la izquierda
  - acciones de usuario a la derecha
  - buscador en fila completa inferior
- Se compacta el bloque de usuario en movil ocultando el nombre en el resumen para evitar saturacion visual.
- Se mejora el encabezado de modulo (`page-header`) para que el boton principal ocupe ancho completo en pantallas pequenas.
- Se ajusta tambien la cabecera de login para mejor alineacion y legibilidad en celular.

## 2026-03-09 - Listas de catalogos sin columna ID visible
- Se retira la columna `ID` de las listas de:
  - Clasificaciones
  - Medios de pago
- Se mantiene columna de numeracion `#` para orden visual del usuario.
- Se ajusta `colspan` de filas vacias para mantener integridad del render.
- Se valida en SQL base (`presupuesto.sql`) que las tablas de este modulo usan:
  - `clasificaciones(id, descripcion)`
  - `medios(id, medio)`
  y no exigen campo `clasificacion2` en este contexto funcional.

## 2026-03-09 - Filtros de movimientos y mejora de PDF exportado
- Se agrega panel de filtros en Movimientos para escritorio y movil con:
  - fecha
  - clasificacion
  - categoria
  - tipo/medio
- En movil, el filtro de fecha inicia por defecto en el dia actual para mostrar movimientos del dia.
- Se aplica filtrado sincronizado entre:
  - tabla de escritorio (DataTables)
  - tarjetas de listado movil
- Se mejora exportacion PDF de Movimientos:
  - orientacion horizontal para evitar desbordes
  - cabecera con nombre del reporte
  - fecha y hora de impresion
  - pie con numeracion de paginas
  - ajuste de anchos y estilo de tabla para mejor legibilidad.

## 2026-03-09 - Fondo dark mode mas profundo
- Se oscurece el fondo global en modo oscuro para reducir tono gris y mejorar contraste visual.
- Se aplica nuevo gradiente en `body.theme-dark` con base mas oscura y atmosfera uniforme.

## 2026-03-09 - Select2 de busqueda en selects con varios items
- Se habilita Select2 en filtros de movimientos para:
  - clasificacion
  - tipo o medio
- Se mejora inicializacion global de Select2:
  - evita doble inicializacion del mismo campo
  - muestra buscador solo cuando el select tiene varios items (umbral >= 8 opciones)
  - conserva placeholders y `allowClear`.

## 2026-03-09 - Dashboard movil: movimientos recientes legibles
- Se redisenia la seccion `Movimientos recientes` del dashboard para movil con tarjetas compactas.
- Cada tarjeta muestra:
  - fecha
  - valor
  - clasificacion
  - categoria
  - detalle
  - usuario
- En escritorio se mantiene la tabla DataTables actual.
- En movil se oculta la tabla y se muestra el nuevo listado adaptado sin desbordes.

## 2026-03-09 - Columna numerica compacta en catalogos
- Se ajusta ancho de la columna numerica `#` en:
  - Clasificaciones
  - Medios de pago
- Se define ancho fijo corto para que ocupe solo el espacio del numero y no consuma area de contenido.
