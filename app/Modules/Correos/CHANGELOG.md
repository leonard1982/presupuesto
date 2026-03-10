# CHANGELOG - Modulo Correos

## 2026-03-10
- Se corrige codificacion de correos IMAP para mostrar texto con acentos en UTF-8.
- Se mejora saneamiento de contenido HTML/quoted-printable y payload base64 en extractos.
- La columna `Extracto` se maneja por icono con modal de lectura de contenido.
- Se corrige apertura estable del modal de extracto para todos los registros.
- La sugerencia de movimiento deja de mostrarse en panel lateral y pasa a modal emergente por accion `Analizar`.
- Se agrega validacion de relevancia economica para bloquear sugerencias en correos no financieros.
- Se agrega validacion por UID al reutilizar datos de formulario (flash) y evitar cruces entre correos.
- Se compacta columna de numeracion en bandeja para ganar espacio horizontal.

## 2026-03-10
- Se crea controlador `CorreoController` con flujo de bandeja, sugerencia y guardado.
- Se crea `InboxReaderService` para lectura IMAP compatible PHP 7.2+.
- Se crea `CorreoSuggestionService` con modo hibrido (reglas + OpenAI opcional).
- Se crea `CorreoRepository` para log de importaciones de correo.
- Se crea vista `app/Views/correos/index.php`.
- Se agregan rutas `GET /correos` y `POST /correos/guardar`.
- Se agrega entrada directa `correos/index.php` para entornos sin `mod_rewrite`.
