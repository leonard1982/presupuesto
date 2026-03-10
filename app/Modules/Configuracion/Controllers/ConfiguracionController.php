<?php
/**
 * Proyecto PRESUPUESTO - Configuraciones de usuario autenticado.
 */

namespace App\Modules\Configuracion\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\SessionManager;
use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;

class ConfiguracionController
{
    private $viewRenderer;
    private $appConfig;
    private $authService;

    public function __construct(ViewRenderer $viewRenderer, array $appConfig, AuthService $authService)
    {
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
    }

    public function sessionForm()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($tokenName);
        $successMessage = $this->pullFlash('configuracion_success');
        $errorMessage = $this->pullFlash('configuracion_error');

        $options = $this->resolveSessionOptionsHours();
        $currentHours = SessionManager::getCurrentLifetimeHours($this->appConfig);
        if (!in_array($currentHours, $options, true)) {
            $options[] = $currentHours;
            sort($options);
        }

        $this->viewRenderer->render('configuracion/session', array(
            'pageTitle' => 'Configuracion de sesion',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'configuracion',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'successMessage' => is_string($successMessage) ? $successMessage : '',
            'errorMessage' => is_string($errorMessage) ? $errorMessage : '',
            'sessionOptionsHours' => $options,
            'currentSessionHours' => $currentHours,
        ));
    }

    public function updateSessionConfig()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('configuracion_error', 'La sesion del formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/configuracion/sesion'));
        }

        $hours = isset($_POST['session_hours']) ? (int) $_POST['session_hours'] : 0;
        $options = $this->resolveSessionOptionsHours();
        if (!in_array($hours, $options, true)) {
            $this->setFlash('configuracion_error', 'Selecciona un tiempo de sesion valido.');
            Response::redirect($this->buildUrl('/configuracion/sesion'));
        }

        $updated = SessionManager::updateLifetimePreference($hours, $this->appConfig);
        if (!$updated) {
            $this->setFlash('configuracion_error', 'No fue posible actualizar la configuracion de sesion.');
            Response::redirect($this->buildUrl('/configuracion/sesion'));
        }

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('configuracion_success', 'Tiempo de sesion actualizado a ' . $hours . ' horas.');
        Response::redirect($this->buildUrl('/configuracion/sesion'));
    }

    private function resolveSessionOptionsHours()
    {
        $rawOptions = isset($this->appConfig['session_lifetime_options_hours']) && is_array($this->appConfig['session_lifetime_options_hours'])
            ? $this->appConfig['session_lifetime_options_hours']
            : array('8', '12', '24', '48');

        $options = array();
        foreach ($rawOptions as $rawOption) {
            $hours = (int) $rawOption;
            if ($hours >= 8 && $hours <= 168) {
                $options[] = $hours;
            }
        }

        $options = array_values(array_unique($options));
        sort($options);

        if (empty($options)) {
            return array(8, 12, 24, 48);
        }

        return $options;
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

        $value = $_SESSION['flash'][$key];
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

        $encodedRoute = implode('/', array_map('rawurlencode', explode('/', $route)));
        return $baseUrl . '/index.php?route=' . $encodedRoute;
    }
}
