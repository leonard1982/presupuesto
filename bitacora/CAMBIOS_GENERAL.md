# Cambios Generales

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
