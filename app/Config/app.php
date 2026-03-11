<?php
/**
 * Proyecto PRESUPUESTO - Configuracion general de aplicacion.
 */

use App\Core\Environment;

return array(
    'environment' => Environment::get('APP_ENV', 'production'),
    'debug' => Environment::getBoolean('APP_DEBUG', false),
    'base_url' => trim(Environment::get('APP_BASE_URL', 'AUTO')),
    'timezone' => Environment::get('APP_TIMEZONE', 'America/Bogota'),
    'enable_pwa' => Environment::getBoolean('APP_ENABLE_PWA', true),
    'asset_version' => Environment::get('APP_ASSET_VERSION', '0.1.0'),
    'session_name' => Environment::get('APP_SESSION_NAME', 'presupuesto_session'),
    'session_lifetime_seconds' => Environment::getInteger('APP_SESSION_LIFETIME_SECONDS', 43200),
    'session_lifetime_min_seconds' => Environment::getInteger('APP_SESSION_LIFETIME_MIN_SECONDS', 28800),
    'session_lifetime_max_seconds' => Environment::getInteger('APP_SESSION_LIFETIME_MAX_SECONDS', 172800),
    'session_lifetime_cookie_name' => Environment::get('APP_SESSION_LIFETIME_COOKIE_NAME', 'presupuesto_session_lifetime'),
    'session_lifetime_options_hours' => Environment::getCsv('APP_SESSION_LIFETIME_OPTIONS_HOURS', array('8', '12', '24', '48')),
    'remember_username_cookie_name' => Environment::get('APP_REMEMBER_USERNAME_COOKIE_NAME', 'presupuesto_recordado_usuario'),
    'remember_username_days' => Environment::getInteger('APP_REMEMBER_USERNAME_DAYS', 30),
    'csrf_token_name' => Environment::get('APP_CSRF_TOKEN_NAME', 'csrf_token'),
    'files_max_upload_mb' => Environment::getInteger('FILES_MAX_UPLOAD_MB', 10),
    'files_allowed_extensions' => Environment::getCsv('FILES_ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'webp', 'pdf')),
    'files_allowed_mime' => Environment::getCsv('FILES_ALLOWED_MIME', array('image/jpeg', 'image/png', 'image/webp', 'application/pdf')),
    'mail' => array(
        'host' => trim(Environment::get('MAIL_HOST', '')),
        'port' => Environment::getInteger('MAIL_PORT', 25),
        'username' => trim(Environment::get('MAIL_USER', '')),
        'password' => Environment::get('MAIL_PASS', ''),
        'from' => trim(Environment::get('MAIL_FROM', '')),
        'encryption' => strtolower(trim(Environment::get('MAIL_ENCRYPTION', 'none'))),
        'timeout_seconds' => Environment::getInteger('MAIL_TIMEOUT_SECONDS', 20),
    ),
    'mail_inbox' => array(
        'host' => trim(Environment::get('MAIL_INBOX_HOST', Environment::get('MAIL_HOST', ''))),
        'port' => Environment::getInteger('MAIL_INBOX_PORT', 993),
        'username' => trim(Environment::get('MAIL_INBOX_USER', Environment::get('MAIL_USER', ''))),
        'password' => Environment::get('MAIL_INBOX_PASS', Environment::get('MAIL_PASS', '')),
        'folder' => trim(Environment::get('MAIL_INBOX_FOLDER', 'INBOX')),
        'folders' => Environment::getCsv('MAIL_INBOX_FOLDERS', array(trim(Environment::get('MAIL_INBOX_FOLDER', 'INBOX')), 'INBOX', '[Gmail]/All Mail')),
        'encryption' => strtolower(trim(Environment::get('MAIL_INBOX_ENCRYPTION', 'ssl'))),
        'novalidate_cert' => Environment::getBoolean('MAIL_INBOX_NOVALIDATE_CERT', true),
        'allow_all_folders' => Environment::getBoolean('MAIL_INBOX_ALLOW_ALL_FOLDERS', true),
        'max_messages' => Environment::getInteger('MAIL_INBOX_MAX_MESSAGES', 250),
    ),
    'ai' => array(
        'openai_key' => trim(Environment::get('OPENAI_API_KEY_PRESUPUESTO_PERSONAL', '')),
        'openai_model' => trim(Environment::get('OPENAI_KPI_MODEL', 'gpt-4o-mini')),
        'openai_timeout_seconds' => Environment::getInteger('OPENAI_TIMEOUT_SECONDS', 20),
    ),
);
