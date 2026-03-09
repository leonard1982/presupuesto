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
    'enable_pwa' => Environment::getBoolean('APP_ENABLE_PWA', false),
    'asset_version' => Environment::get('APP_ASSET_VERSION', '0.1.0'),
    'session_name' => Environment::get('APP_SESSION_NAME', 'presupuesto_session'),
    'session_lifetime_seconds' => Environment::getInteger('APP_SESSION_LIFETIME_SECONDS', 7200),
    'csrf_token_name' => Environment::get('APP_CSRF_TOKEN_NAME', 'csrf_token'),
    'files_max_upload_mb' => Environment::getInteger('FILES_MAX_UPLOAD_MB', 10),
    'files_allowed_extensions' => Environment::getCsv('FILES_ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'webp', 'pdf')),
    'files_allowed_mime' => Environment::getCsv('FILES_ALLOWED_MIME', array('image/jpeg', 'image/png', 'image/webp', 'application/pdf')),
);
