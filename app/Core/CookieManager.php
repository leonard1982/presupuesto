<?php
/**
 * Proyecto PRESUPUESTO - Utilidades de cookies compatibles con PHP 7.2 a 8.2.
 */

namespace App\Core;

class CookieManager
{
    public static function get($name, $default = '')
    {
        if (!is_string($name) || $name === '') {
            return $default;
        }

        if (!isset($_COOKIE[$name])) {
            return $default;
        }

        return (string) $_COOKIE[$name];
    }

    public static function set($name, $value, $expiresAt, $path, $secure, $httpOnly, $sameSite)
    {
        if (!is_string($name) || $name === '') {
            return false;
        }

        $pathValue = self::normalizePath($path);
        $sameSiteValue = self::normalizeSameSite($sameSite);
        $expiresAtValue = (int) $expiresAt;
        $secureValue = (bool) $secure;
        $httpOnlyValue = (bool) $httpOnly;

        if (PHP_VERSION_ID >= 70300) {
            return setcookie($name, (string) $value, array(
                'expires' => $expiresAtValue,
                'path' => $pathValue,
                'secure' => $secureValue,
                'httponly' => $httpOnlyValue,
                'samesite' => $sameSiteValue,
            ));
        }

        return setcookie(
            $name,
            (string) $value,
            $expiresAtValue,
            $pathValue . '; samesite=' . $sameSiteValue,
            '',
            $secureValue,
            $httpOnlyValue
        );
    }

    public static function delete($name, $path, $secure, $httpOnly, $sameSite)
    {
        if (!is_string($name) || $name === '') {
            return false;
        }

        $deleted = self::set($name, '', time() - 3600, $path, $secure, $httpOnly, $sameSite);
        unset($_COOKIE[$name]);
        return $deleted;
    }

    private static function normalizePath($path)
    {
        $pathString = trim((string) $path);
        if ($pathString === '') {
            return '/';
        }

        if ($pathString[0] !== '/') {
            $pathString = '/' . $pathString;
        }

        return rtrim($pathString, '/') . '/';
    }

    private static function normalizeSameSite($sameSite)
    {
        $value = strtolower(trim((string) $sameSite));
        if ($value === 'strict') {
            return 'Strict';
        }

        if ($value === 'none') {
            return 'None';
        }

        return 'Lax';
    }
}
