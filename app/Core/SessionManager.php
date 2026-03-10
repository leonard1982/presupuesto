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
        $sessionName = self::resolveSessionName($appConfig);
        $sessionLifetime = self::resolveLifetimeSeconds($appConfig);

        session_name($sessionName);
        self::configureSessionCookieParams($sessionLifetime, $isHttps);

        session_start();

        if (self::isSessionExpiredByInactivity($sessionLifetime)) {
            self::destroy();
            self::configureSessionCookieParams($sessionLifetime, $isHttps);
            session_name($sessionName);
            session_start();
        }

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

        $_SESSION['__session_last_activity_at'] = time();
        $_SESSION['__session_lifetime_seconds'] = $sessionLifetime;
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

    public static function updateLifetimePreference($hours, array $appConfig)
    {
        $hoursInt = (int) $hours;
        if ($hoursInt <= 0) {
            return false;
        }

        $seconds = $hoursInt * 3600;
        $bounds = self::resolveLifetimeBounds($appConfig);
        if ($seconds < $bounds['min']) {
            $seconds = $bounds['min'];
        }
        if ($seconds > $bounds['max']) {
            $seconds = $bounds['max'];
        }

        $cookieName = isset($appConfig['session_lifetime_cookie_name'])
            ? trim((string) $appConfig['session_lifetime_cookie_name'])
            : 'presupuesto_session_lifetime';
        if ($cookieName === '') {
            $cookieName = 'presupuesto_session_lifetime';
        }

        $cookiePath = self::resolveCookiePath($appConfig);
        $expiresAt = time() + (365 * 86400);
        $isHttps = self::isHttpsRequest();

        CookieManager::set($cookieName, (string) $seconds, $expiresAt, $cookiePath, $isHttps, true, 'Lax');

        if (session_status() === PHP_SESSION_ACTIVE) {
            CookieManager::set(session_name(), session_id(), time() + $seconds, '/', $isHttps, true, 'Lax');
            $_SESSION['__session_lifetime_seconds'] = $seconds;
            $_SESSION['__session_last_activity_at'] = time();
        }

        return true;
    }

    public static function getCurrentLifetimeHours(array $appConfig)
    {
        $seconds = self::resolveLifetimeSeconds($appConfig);
        if ($seconds < 3600) {
            return 1;
        }

        return (int) round($seconds / 3600);
    }

    private static function resolveSessionName(array $appConfig)
    {
        $rawSessionName = isset($appConfig['session_name']) ? $appConfig['session_name'] : 'presupuesto_session';
        $sessionName = is_string($rawSessionName) ? trim($rawSessionName) : '';
        if ($sessionName === '') {
            $sessionName = 'presupuesto_session';
        }

        return $sessionName;
    }

    private static function resolveLifetimeSeconds(array $appConfig)
    {
        $defaultSeconds = isset($appConfig['session_lifetime_seconds'])
            ? (int) $appConfig['session_lifetime_seconds']
            : 43200;
        if ($defaultSeconds <= 0) {
            $defaultSeconds = 43200;
        }

        $cookieName = isset($appConfig['session_lifetime_cookie_name'])
            ? trim((string) $appConfig['session_lifetime_cookie_name'])
            : 'presupuesto_session_lifetime';
        if ($cookieName === '') {
            $cookieName = 'presupuesto_session_lifetime';
        }

        $cookieValue = CookieManager::get($cookieName, '');
        $cookieSeconds = is_numeric($cookieValue) ? (int) $cookieValue : 0;
        $selectedSeconds = $cookieSeconds > 0 ? $cookieSeconds : $defaultSeconds;

        $bounds = self::resolveLifetimeBounds($appConfig);
        if ($selectedSeconds < $bounds['min']) {
            $selectedSeconds = $bounds['min'];
        }
        if ($selectedSeconds > $bounds['max']) {
            $selectedSeconds = $bounds['max'];
        }

        return $selectedSeconds;
    }

    private static function resolveLifetimeBounds(array $appConfig)
    {
        $minSeconds = isset($appConfig['session_lifetime_min_seconds'])
            ? (int) $appConfig['session_lifetime_min_seconds']
            : 28800;
        $maxSeconds = isset($appConfig['session_lifetime_max_seconds'])
            ? (int) $appConfig['session_lifetime_max_seconds']
            : 172800;

        if ($minSeconds <= 0) {
            $minSeconds = 28800;
        }
        if ($maxSeconds < $minSeconds) {
            $maxSeconds = $minSeconds;
        }

        return array(
            'min' => $minSeconds,
            'max' => $maxSeconds,
        );
    }

    private static function isSessionExpiredByInactivity($lifetimeSeconds)
    {
        if (!isset($_SESSION['__session_last_activity_at'])) {
            return false;
        }

        $lastActivity = (int) $_SESSION['__session_last_activity_at'];
        if ($lastActivity <= 0) {
            return false;
        }

        return (time() - $lastActivity) > (int) $lifetimeSeconds;
    }

    private static function resolveCookiePath(array $appConfig)
    {
        $baseUrl = isset($appConfig['base_url']) ? (string) $appConfig['base_url'] : '';
        $parsedPath = parse_url($baseUrl, PHP_URL_PATH);

        if (!is_string($parsedPath) || trim($parsedPath) === '') {
            return '/';
        }

        return rtrim($parsedPath, '/') . '/';
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
