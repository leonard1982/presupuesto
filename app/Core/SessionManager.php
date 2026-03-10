<?php
/**
 * Proyecto PRESUPUESTO - Manejo seguro de sesiones.
 */

namespace App\Core;

class SessionManager
{
    public static function start(array $appConfig)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        $isHttps = self::isHttpsRequest();
        $sessionName = $appConfig['session_name'];
        $sessionLifetime = (int) $appConfig['session_lifetime_seconds'];

        session_name($sessionName);
        self::configureSessionCookieParams($sessionLifetime, $isHttps);

        session_start();

        if (!isset($_SESSION['__session_initialized_at'])) {
            $_SESSION['__session_initialized_at'] = time();
            session_regenerate_id(true);
        }

        if (!isset($_SESSION['__session_last_regeneration_at'])) {
            $_SESSION['__session_last_regeneration_at'] = time();
        }

        if ((time() - (int) $_SESSION['__session_last_regeneration_at']) > 900) {
            session_regenerate_id(true);
            $_SESSION['__session_last_regeneration_at'] = time();
        }
    }

    public static function destroy()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    private static function isHttpsRequest()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        return false;
    }

    private static function configureSessionCookieParams($sessionLifetime, $isHttps)
    {
        $lifetime = (int) $sessionLifetime;
        $secure = (bool) $isHttps;
        $httpOnly = true;

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params(array(
                'lifetime' => $lifetime,
                'path' => '/',
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => 'Lax',
            ));
            return;
        }

        // Compatibilidad PHP 7.2: samesite se declara en el path.
        session_set_cookie_params($lifetime, '/; samesite=Lax', '', $secure, $httpOnly);
    }
}
