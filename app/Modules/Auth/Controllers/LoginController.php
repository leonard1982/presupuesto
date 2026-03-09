<?php
/**
 * Proyecto PRESUPUESTO - Controlador de login/logout.
 */

namespace App\Modules\Auth\Controllers;

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

        $this->viewRenderer->render('auth/login', array(
            'pageTitle' => 'Ingreso al sistema',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'errorMessage' => $errorMessage,
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
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

        if ($username === '' || $password === '') {
            $this->setFlash('auth_error', 'Debes ingresar usuario y contrasena.');
            Response::redirect($this->buildUrl('/login'));
        }

        $authenticated = $this->authService->attemptLogin($username, $password);
        if (!$authenticated) {
            $this->setFlash('auth_error', 'Credenciales invalidas o usuario inactivo.');
            Response::redirect($this->buildUrl('/login'));
        }

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
        return $baseUrl . '/' . ltrim($path, '/');
    }
}
