# CHANGELOG - Modulo Correos

## 2026-03-10
- Se crea controlador `CorreoController` con flujo de bandeja, sugerencia y guardado.
- Se crea `InboxReaderService` para lectura IMAP compatible PHP 7.2+.
- Se crea `CorreoSuggestionService` con modo hibrido (reglas + OpenAI opcional).
- Se crea `CorreoRepository` para log de importaciones de correo.
- Se crea vista `app/Views/correos/index.php`.
- Se agregan rutas `GET /correos` y `POST /correos/guardar`.
- Se agrega entrada directa `correos/index.php` para entornos sin `mod_rewrite`.
