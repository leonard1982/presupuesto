<?php
/**
 * Proyecto PRESUPUESTO - Controlador de movimientos (gastos, costos y compras).
 */

namespace App\Modules\Movimientos\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Movimientos\Repositories\MovimientoRepository;

class MovimientoController
{
    private $movimientoRepository;
    private $viewRenderer;
    private $appConfig;
    private $authService;

    public function __construct(
        MovimientoRepository $movimientoRepository,
        ViewRenderer $viewRenderer,
        array $appConfig,
        AuthService $authService
    ) {
        $this->movimientoRepository = $movimientoRepository;
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
    }

    public function index()
    {
        $movimientos = $this->movimientoRepository->getRecentMovimientos(25);
        $successMessage = $this->pullFlash('movimientos_success');
        $errorMessage = $this->pullFlash('movimientos_error');

        $this->viewRenderer->render('movimientos/index', array(
            'pageTitle' => 'Movimientos',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'movimientos',
            'movimientos' => $movimientos,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ));
    }

    public function createForm()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($tokenName);

        $oldInput = $this->pullFlash('movimientos_old_input');
        $errorMessage = $this->pullFlash('movimientos_error');

        $this->viewRenderer->render('movimientos/form', array(
            'pageTitle' => 'Nuevo movimiento',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'movimientos',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'errorMessage' => $errorMessage,
            'formData' => is_array($oldInput) ? $oldInput : array(),
            'clasificaciones' => $this->movimientoRepository->getClasificaciones(),
            'mediosPago' => $this->movimientoRepository->getMediosPago(),
            'presupuestosActivos' => $this->movimientoRepository->getPresupuestosActivos(),
        ));
    }

    public function store()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('movimientos_error', 'La sesion de formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        $formData = $this->collectMovementFormData($_POST);
        $validation = $this->validateMovementFormData($formData);

        if (!$validation['valid']) {
            $this->setFlash('movimientos_error', $validation['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        $authenticatedUser = $this->authService->getAuthenticatedUser();
        $userLogin = isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema';

        $movementToPersist = array(
            'fecha' => $formData['fecha'],
            'id_clasificacion' => (int) $formData['id_clasificacion'],
            'detalle' => $formData['detalle'],
            'valor' => (float) $formData['valor'],
            'fecha_periodo' => substr($formData['fecha'], 0, 10),
            'id_presupuesto' => (int) $formData['id_presupuesto'],
            'soporte' => '',
            'gasto_costo' => $formData['gasto_costo'],
            'tipo' => $formData['tipo'],
            'por_pagar_cobrar' => $formData['por_pagar_cobrar'],
            'valor_neto' => (float) $formData['valor_neto'],
            'saldo' => (float) $formData['saldo'],
            'id_costo' => 0,
            'usuario' => $userLogin,
        );

        $saved = $this->movimientoRepository->createMovimiento($movementToPersist);
        if (!$saved) {
            $this->setFlash('movimientos_error', 'No fue posible guardar el movimiento. Revisa el log de aplicacion.');
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('movimientos_success', 'Movimiento registrado correctamente.');
        Response::redirect($this->buildUrl('/movimientos'));
    }

    private function collectMovementFormData(array $postData)
    {
        return array(
            'fecha' => isset($postData['fecha']) ? trim((string) $postData['fecha']) : '',
            'id_clasificacion' => isset($postData['id_clasificacion']) ? (int) $postData['id_clasificacion'] : 0,
            'detalle' => isset($postData['detalle']) ? trim((string) $postData['detalle']) : '',
            'valor' => $this->toDecimal(isset($postData['valor']) ? $postData['valor'] : '0'),
            'id_presupuesto' => isset($postData['id_presupuesto']) ? (int) $postData['id_presupuesto'] : 0,
            'gasto_costo' => isset($postData['gasto_costo']) ? trim((string) $postData['gasto_costo']) : '',
            'tipo' => isset($postData['tipo']) ? trim((string) $postData['tipo']) : '',
            'por_pagar_cobrar' => isset($postData['por_pagar_cobrar']) ? trim((string) $postData['por_pagar_cobrar']) : 'NINGUNO',
            'valor_neto' => $this->toDecimal(isset($postData['valor_neto']) ? $postData['valor_neto'] : '0'),
            'saldo' => $this->toDecimal(isset($postData['saldo']) ? $postData['saldo'] : '0'),
        );
    }

    private function validateMovementFormData(array $formData)
    {
        if (!$this->isDateTimeString($formData['fecha'])) {
            return array('valid' => false, 'message' => 'La fecha del movimiento es obligatoria.');
        }

        if ((int) $formData['id_clasificacion'] <= 0) {
            return array('valid' => false, 'message' => 'Debes seleccionar una clasificacion.');
        }

        if (mb_strlen($formData['detalle']) < 3) {
            return array('valid' => false, 'message' => 'El detalle debe tener al menos 3 caracteres.');
        }

        if ((float) $formData['valor'] <= 0) {
            return array('valid' => false, 'message' => 'El valor debe ser mayor a cero.');
        }

        if (!in_array($formData['gasto_costo'], array('Gasto', 'Costo'), true)) {
            return array('valid' => false, 'message' => 'Selecciona una categoria principal valida.');
        }

        if (!in_array($formData['por_pagar_cobrar'], array('COBRAR', 'PAGAR', 'NINGUNO'), true)) {
            return array('valid' => false, 'message' => 'Selecciona un estado de saldo valido.');
        }

        if ($formData['tipo'] === '') {
            return array('valid' => false, 'message' => 'Debes seleccionar un tipo o medio de movimiento.');
        }

        return array('valid' => true, 'message' => '');
    }

    private function toDecimal($value)
    {
        $stringValue = trim((string) $value);
        $stringValue = str_replace(' ', '', $stringValue);

        $hasComma = strpos($stringValue, ',') !== false;
        $hasDot = strpos($stringValue, '.') !== false;

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($stringValue, ',');
            $lastDot = strrpos($stringValue, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $stringValue);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $stringValue);
            }
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $stringValue);
        } else {
            $normalized = $stringValue;
        }

        if ($normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }

    private function isDateTimeString($dateTimeString)
    {
        if (!is_string($dateTimeString) || trim($dateTimeString) === '') {
            return false;
        }

        $timestamp = strtotime($dateTimeString);
        return $timestamp !== false;
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
