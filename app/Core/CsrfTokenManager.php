<?php
/**
 * Proyecto PRESUPUESTO - Generacion y validacion de token CSRF.
 */

namespace App\Core;

class CsrfTokenManager
{
    public static function generateToken($tokenName)
    {
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = array();
        }

        if (empty($_SESSION['csrf_tokens'][$tokenName])) {
            $_SESSION['csrf_tokens'][$tokenName] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_tokens'][$tokenName];
    }

    public static function validateToken($tokenName, $providedToken)
    {
        if (!isset($_SESSION['csrf_tokens'][$tokenName])) {
            return false;
        }

        $storedToken = (string) $_SESSION['csrf_tokens'][$tokenName];
        return hash_equals($storedToken, (string) $providedToken);
    }

    public static function rotateToken($tokenName)
    {
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = array();
        }

        $_SESSION['csrf_tokens'][$tokenName] = bin2hex(random_bytes(32));
    }
}
