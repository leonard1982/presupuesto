<?php
/**
 * Proyecto PRESUPUESTO - Controlador de login/logout.
 */

namespace App\Modules\Auth\Controllers;

use App\Core\CookieManager;
use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;

class LoginController
{
    private $authService;
    private $viewRenderer;
    private $appConfig;

    public function __construct(AuthService $authService, ViewRenderer $viewRenderer, array $appConfig)
    {
        $this->authService = $authService;
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
    }

    public function show()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($tokenName);
        $errorMessage = $this->pullFlash('auth_error');
        $flashUsername = $this->pullFlash('auth_username');
        $rememberedUsername = CookieManager::get($this->appConfig['remember_username_cookie_name'], '');
        $prefilledUsername = $flashUsername !== '' ? $flashUsername : $rememberedUsername;

        $this->viewRenderer->render('auth/login', array(
            'pageTitle' => 'Ingreso al sistema',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'errorMessage' => $errorMessage,
            'rememberedUsername' => $prefilledUsername,
            'rememberUsernameChecked' => $rememberedUsername !== '',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'pageBodyClass' => 'login-body',
            'currentUser' => null,
        ));
    }

    public function authenticate()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('auth_error', 'Token de seguridad invalido. Recarga la pagina e intenta de nuevo.');
            Response::redirect($this->buildUrl('/login'));
        }

        $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $rememberUsername = isset($_POST['remember_username']) && (string) $_POST['remember_username'] === '1';

        if ($username === '' || $password === '') {
            $this->setFlash('auth_error', 'Debes ingresar usuario y contrasena.');
            $this->setFlash('auth_username', $username);
            Response::redirect($this->buildUrl('/login'));
        }

        $authenticated = $this->authService->attemptLogin($username, $password);
        if (!$authenticated) {
            $this->setFlash('auth_error', 'Credenciales invalidas o usuario inactivo.');
            $this->setFlash('auth_username', $username);
            Response::redirect($this->buildUrl('/login'));
        }

        $this->handleRememberUsername($rememberUsername, $username);
        CsrfTokenManager::rotateToken($tokenName);
        Response::redirect($this->buildUrl('/dashboard'));
    }

    public function logout()
    {
        $this->authService->logout();
        Response::redirect($this->buildUrl('/login'));
    }

    private function setFlash($key, $value)
    {
        $_SESSION['flash'][$key] = $value;
    }

    private function pullFlash($key)
    {
        if (!isset($_SESSION['flash'][$key])) {
            return '';
        }

        $value = (string) $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    private function buildUrl($path)
    {
        $baseUrl = rtrim($this->appConfig['base_url'], '/');
        $route = trim((string) $path, '/');

        if ($route === '') {
            return $baseUrl . '/index.php';
        }

        return $baseUrl . '/index.php?route=' . rawurlencode($route);
    }

    private function handleRememberUsername($rememberUsername, $username)
    {
        $cookieName = isset($this->appConfig['remember_username_cookie_name']) ? (string) $this->appConfig['remember_username_cookie_name'] : 'presupuesto_recordado_usuario';
        $cookiePath = $this->resolveCookiePath();
        $isSecure = $this->isHttpsRequest();

        if ($rememberUsername) {
            $days = isset($this->appConfig['remember_username_days']) ? (int) $this->appConfig['remember_username_days'] : 30;
            if ($days < 1) {
                $days = 30;
            }

            $expiresAt = time() + ($days * 86400);
            CookieManager::set($cookieName, $username, $expiresAt, $cookiePath, $isSecure, true, 'Lax');
            return;
        }

        CookieManager::delete($cookieName, $cookiePath, $isSecure, true, 'Lax');
    }

    private function resolveCookiePath()
    {
        $baseUrl = isset($this->appConfig['base_url']) ? (string) $this->appConfig['base_url'] : '';
        $parsedPath = parse_url($baseUrl, PHP_URL_PATH);

        if (!is_string($parsedPath) || $parsedPath === '') {
            return '/';
        }

        return rtrim($parsedPath, '/') . '/';
    }

    private function isHttpsRequest()
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
}
