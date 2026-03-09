<?php
/**
 * Proyecto PRESUPUESTO - Controlador de catalogos (clasificaciones y medios).
 */

namespace App\Modules\Catalogos\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Catalogos\Repositories\CatalogoRepository;

class CatalogoController
{
    private $catalogoRepository;
    private $viewRenderer;
    private $appConfig;
    private $authService;

    public function __construct(
        CatalogoRepository $catalogoRepository,
        ViewRenderer $viewRenderer,
        array $appConfig,
        AuthService $authService
    ) {
        $this->catalogoRepository = $catalogoRepository;
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
    }

    public function clasificacionesIndex()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($tokenName);
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $this->viewRenderer->render('catalogos/clasificaciones', array(
            'pageTitle' => 'Clasificaciones',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'clasificaciones',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'search' => $search,
            'records' => $this->catalogoRepository->listClasificaciones($search),
            'successMessage' => $this->pullFlash('clasificaciones_success'),
            'errorMessage' => $this->pullFlash('clasificaciones_error'),
        ));
    }

    public function clasificacionesStore()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('clasificaciones_error', 'La sesion de formulario caduco. Intenta de nuevo.');
            Response::redirect($this->buildUrl('/clasificaciones'));
        }

        $description = isset($_POST['descripcion']) ? trim((string) $_POST['descripcion']) : '';
        if (mb_strlen($description) < 2) {
            $this->setFlash('clasificaciones_error', 'La clasificacion debe tener minimo 2 caracteres.');
            Response::redirect($this->buildUrl('/clasificaciones'));
        }

        if ($this->catalogoRepository->existsClasificacionByName($description)) {
            $this->setFlash('clasificaciones_error', 'Esa clasificacion ya existe.');
            Response::redirect($this->buildUrl('/clasificaciones'));
        }

        if (!$this->catalogoRepository->createClasificacion($description)) {
            $this->setFlash('clasificaciones_error', 'No fue posible crear la clasificacion.');
            Response::redirect($this->buildUrl('/clasificaciones'));
        }

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('clasificaciones_success', 'Clasificacion creada correctamente.');
        Response::redirect($this->buildUrl('/clasificaciones'));
    }

    public function mediosIndex()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($tokenName);
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $this->viewRenderer->render('catalogos/medios_pago', array(
            'pageTitle' => 'Medios de pago',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'medios_pago',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'search' => $search,
            'records' => $this->catalogoRepository->listMediosPago($search),
            'successMessage' => $this->pullFlash('medios_success'),
            'errorMessage' => $this->pullFlash('medios_error'),
        ));
    }

    public function mediosStore()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('medios_error', 'La sesion de formulario caduco. Intenta de nuevo.');
            Response::redirect($this->buildUrl('/medios-pago'));
        }

        $medio = isset($_POST['medio']) ? trim((string) $_POST['medio']) : '';
        if (mb_strlen($medio) < 2) {
            $this->setFlash('medios_error', 'El medio de pago debe tener minimo 2 caracteres.');
            Response::redirect($this->buildUrl('/medios-pago'));
        }

        if ($this->catalogoRepository->existsMedioByName($medio)) {
            $this->setFlash('medios_error', 'Ese medio de pago ya existe.');
            Response::redirect($this->buildUrl('/medios-pago'));
        }

        if (!$this->catalogoRepository->createMedioPago($medio)) {
            $this->setFlash('medios_error', 'No fue posible crear el medio de pago.');
            Response::redirect($this->buildUrl('/medios-pago'));
        }

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('medios_success', 'Medio de pago creado correctamente.');
        Response::redirect($this->buildUrl('/medios-pago'));
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

        $encodedRoute = implode('/', array_map('rawurlencode', explode('/', $route)));
        return $baseUrl . '/index.php?route=' . $encodedRoute;
    }
}
