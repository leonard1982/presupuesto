<?php
/**
 * Proyecto PRESUPUESTO - Controlador de movimientos (gastos, costos y compras).
 */

namespace App\Modules\Movimientos\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\ViewRenderer;
use App\Helpers\FileUploadValidator;
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
        $movimientos = $this->movimientoRepository->getRecentMovimientos(250);
        $successMessage = $this->pullFlash('movimientos_success');
        $errorMessage = $this->pullFlash('movimientos_error');

        $movementIds = array();
        foreach ($movimientos as $movement) {
            if (isset($movement['id'])) {
                $movementIds[] = (int) $movement['id'];
            }
        }

        $supportsByMovement = $this->movimientoRepository->getSupportsByMovementIds($movementIds);
        foreach ($movimientos as &$movement) {
            $movementId = isset($movement['id']) ? (int) $movement['id'] : 0;
            $supportsRaw = isset($supportsByMovement[$movementId]) ? $supportsByMovement[$movementId] : array();
            $movement['supports'] = $this->normalizeSupportRows($supportsRaw);
            $movement['supports_count'] = count($movement['supports']);
        }
        unset($movement);

        $csrfTokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($csrfTokenName);

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
            'csrfTokenName' => $csrfTokenName,
            'csrfToken' => $csrfToken,
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
            'formActionRoute' => 'movimientos',
            'isEditMode' => false,
            'movementId' => 0,
            'existingSupports' => array(),
            'filesConfig' => array(
                'maxMb' => isset($this->appConfig['files_max_upload_mb']) ? (int) $this->appConfig['files_max_upload_mb'] : 10,
                'allowedExtensions' => isset($this->appConfig['files_allowed_extensions']) ? $this->appConfig['files_allowed_extensions'] : array(),
            ),
        ));
    }

    public function editForm()
    {
        $movementId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($movementId <= 0) {
            $this->setFlash('movimientos_error', 'Movimiento no valido para editar.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $movement = $this->movimientoRepository->findMovimientoById($movementId);
        if (!$movement) {
            $this->setFlash('movimientos_error', 'No se encontro el movimiento solicitado.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $tokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($tokenName);

        $oldInput = $this->pullFlash('movimientos_old_input');
        $errorMessage = $this->pullFlash('movimientos_error');
        $supportsRaw = $this->movimientoRepository->getSupportsByMovementId($movementId);

        $formData = is_array($oldInput) && !empty($oldInput) ? $oldInput : array(
            'fecha' => isset($movement['fecha']) ? (string) $movement['fecha'] : '',
            'id_clasificacion' => isset($movement['id_clasificacion']) ? (int) $movement['id_clasificacion'] : 0,
            'detalle' => isset($movement['detalle']) ? (string) $movement['detalle'] : '',
            'valor' => isset($movement['valor']) ? (string) (int) round((float) $movement['valor']) : '0',
            'id_presupuesto' => isset($movement['id_presupuesto']) ? (int) $movement['id_presupuesto'] : 0,
            'gasto_costo' => isset($movement['gasto_costo']) ? (string) $movement['gasto_costo'] : 'Gasto',
            'tipo' => isset($movement['tipo']) ? (string) $movement['tipo'] : '',
            'por_pagar_cobrar' => isset($movement['por_pagar_cobrar']) ? (string) $movement['por_pagar_cobrar'] : 'NINGUNO',
            'valor_neto' => isset($movement['valor_neto']) ? (string) (int) round((float) $movement['valor_neto']) : '0',
            'saldo' => isset($movement['saldo']) ? (string) (int) round((float) $movement['saldo']) : '0',
        );

        $this->viewRenderer->render('movimientos/form', array(
            'pageTitle' => 'Editar movimiento',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'movimientos',
            'csrfTokenName' => $tokenName,
            'csrfToken' => $csrfToken,
            'errorMessage' => $errorMessage,
            'formData' => $formData,
            'clasificaciones' => $this->movimientoRepository->getClasificaciones(),
            'mediosPago' => $this->movimientoRepository->getMediosPago(),
            'presupuestosActivos' => $this->movimientoRepository->getPresupuestosActivos(),
            'formActionRoute' => 'movimientos/actualizar',
            'isEditMode' => true,
            'movementId' => $movementId,
            'existingSupports' => $this->normalizeSupportRows($supportsRaw),
            'filesConfig' => array(
                'maxMb' => isset($this->appConfig['files_max_upload_mb']) ? (int) $this->appConfig['files_max_upload_mb'] : 10,
                'allowedExtensions' => isset($this->appConfig['files_allowed_extensions']) ? $this->appConfig['files_allowed_extensions'] : array(),
            ),
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

        $fileValidation = $this->validateUploadBatch();
        if (!$fileValidation['valid']) {
            $this->setFlash('movimientos_error', $fileValidation['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        $authenticatedUser = $this->authService->getAuthenticatedUser();
        $userLogin = isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema';

        $movementToPersist = $this->buildMovementPersistenceData($formData, $userLogin);
        $newMovementId = $this->movimientoRepository->createMovimiento($movementToPersist);

        if ($newMovementId === false) {
            $this->setFlash('movimientos_error', 'No fue posible guardar el movimiento. Revisa el log de aplicacion.');
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        $storedSupports = $this->storeSupportsForMovement((int) $newMovementId, $userLogin);
        if (!$storedSupports['ok']) {
            $this->movimientoRepository->deleteSupportsByMovementId((int) $newMovementId);
            $this->movimientoRepository->deleteMovimiento((int) $newMovementId);
            $this->deleteSupportFiles($storedSupports['files']);

            $this->setFlash('movimientos_error', $storedSupports['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('movimientos_success', 'Movimiento registrado correctamente.');
        Response::redirect($this->buildUrl('/movimientos'));
    }

    public function update()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('movimientos_error', 'La sesion de formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $movementId = isset($_POST['movement_id']) ? (int) $_POST['movement_id'] : 0;
        if ($movementId <= 0) {
            $this->setFlash('movimientos_error', 'Movimiento no valido para actualizar.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $existingMovement = $this->movimientoRepository->findMovimientoById($movementId);
        if (!$existingMovement) {
            $this->setFlash('movimientos_error', 'No se encontro el movimiento solicitado.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $formData = $this->collectMovementFormData($_POST);
        $validation = $this->validateMovementFormData($formData);
        if (!$validation['valid']) {
            $this->setFlash('movimientos_error', $validation['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/editar') . '&id=' . $movementId);
        }

        $fileValidation = $this->validateUploadBatch();
        if (!$fileValidation['valid']) {
            $this->setFlash('movimientos_error', $fileValidation['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/editar') . '&id=' . $movementId);
        }

        $authenticatedUser = $this->authService->getAuthenticatedUser();
        $userLogin = isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema';

        $movementToPersist = $this->buildMovementPersistenceData($formData, $userLogin);
        $updated = $this->movimientoRepository->updateMovimiento($movementId, $movementToPersist);

        if (!$updated) {
            $this->setFlash('movimientos_error', 'No fue posible actualizar el movimiento.');
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/editar') . '&id=' . $movementId);
        }

        $storedSupports = $this->storeSupportsForMovement($movementId, $userLogin);
        if (!$storedSupports['ok']) {
            $this->deleteSupportFiles($storedSupports['files']);
            $this->setFlash('movimientos_error', $storedSupports['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/editar') . '&id=' . $movementId);
        }

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('movimientos_success', 'Movimiento actualizado correctamente.');
        Response::redirect($this->buildUrl('/movimientos'));
    }

    public function delete()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('movimientos_error', 'La sesion de formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $movementId = isset($_POST['movement_id']) ? (int) $_POST['movement_id'] : 0;
        if ($movementId <= 0) {
            $this->setFlash('movimientos_error', 'Movimiento no valido para eliminar.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $movement = $this->movimientoRepository->findMovimientoById($movementId);
        if (!$movement) {
            $this->setFlash('movimientos_error', 'No se encontro el movimiento solicitado.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $supports = $this->movimientoRepository->getSupportsByMovementId($movementId);
        $normalizedSupports = $this->normalizeSupportRows($supports);

        $supportsDeleted = $this->movimientoRepository->deleteSupportsByMovementId($movementId);
        if (!$supportsDeleted) {
            $this->setFlash('movimientos_error', 'No fue posible eliminar los soportes del movimiento.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $deleted = $this->movimientoRepository->deleteMovimiento($movementId);
        if (!$deleted) {
            $this->setFlash('movimientos_error', 'No fue posible eliminar el movimiento.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $this->deleteSupportFiles($normalizedSupports);
        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('movimientos_success', 'Movimiento eliminado correctamente.');
        Response::redirect($this->buildUrl('/movimientos'));
    }

    public function downloadSupport()
    {
        $movementId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $supportId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;

        if ($movementId <= 0 || $supportId <= 0) {
            http_response_code(400);
            echo 'Solicitud invalida.';
            exit;
        }

        $movement = $this->movimientoRepository->findMovimientoById($movementId);
        if (!$movement) {
            http_response_code(404);
            echo 'Movimiento no encontrado.';
            exit;
        }

        $support = $this->movimientoRepository->findSupportById($supportId);
        if (!$support || (int) $support['id_ingreso'] !== $movementId) {
            http_response_code(404);
            echo 'Soporte no encontrado.';
            exit;
        }

        $normalized = $this->normalizeSupportRow($support);
        $absolutePath = $this->resolveSupportAbsolutePath($movementId, $normalized['stored_name']);
        if ($absolutePath === '' || !is_file($absolutePath)) {
            http_response_code(404);
            echo 'Archivo no disponible.';
            exit;
        }

        $originalName = isset($normalized['original_name']) ? (string) $normalized['original_name'] : basename($absolutePath);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = $this->detectMimeByExtension($extension);
        $size = filesize($absolutePath);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) ($size !== false ? $size : 0));
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $originalName) . '"');
        readfile($absolutePath);
        exit;
    }

    public function ticketView()
    {
        $movementId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($movementId <= 0) {
            $this->setFlash('movimientos_error', 'Movimiento no valido para ticket.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $movement = $this->movimientoRepository->findMovimientoById($movementId);
        if (!$movement) {
            $this->setFlash('movimientos_error', 'No se encontro el movimiento solicitado.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $supportsRaw = $this->movimientoRepository->getSupportsByMovementId($movementId);
        $normalizedSupports = $this->normalizeSupportRows($supportsRaw);
        $authenticatedUser = $this->authService->getAuthenticatedUser();

        $this->viewRenderer->render('movimientos/ticket', array(
            'pageTitle' => 'Ticket movimiento #' . $movementId,
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'movement' => $movement,
            'supports' => $normalizedSupports,
            'generatedAt' => date('Y-m-d H:i:s'),
            'generatedBy' => isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema',
        ), 'layouts/print');
    }

    private function collectMovementFormData(array $postData)
    {
        $valor = $this->toCurrencyAmount(isset($postData['valor']) ? $postData['valor'] : '');
        $valorNeto = $this->toCurrencyAmount(isset($postData['valor_neto']) ? $postData['valor_neto'] : '');
        $saldo = $this->toCurrencyAmount(isset($postData['saldo']) ? $postData['saldo'] : '');

        if ($valorNeto === null) {
            $valorNeto = $valor !== null ? (float) $valor : 0.0;
        }

        return array(
            'fecha' => isset($postData['fecha']) ? trim((string) $postData['fecha']) : '',
            'id_clasificacion' => isset($postData['id_clasificacion']) ? (int) $postData['id_clasificacion'] : 0,
            'detalle' => isset($postData['detalle']) ? trim((string) $postData['detalle']) : '',
            'valor' => $valor !== null ? (float) $valor : 0.0,
            'id_presupuesto' => isset($postData['id_presupuesto']) ? (int) $postData['id_presupuesto'] : 0,
            'gasto_costo' => isset($postData['gasto_costo']) ? trim((string) $postData['gasto_costo']) : '',
            'tipo' => isset($postData['tipo']) ? trim((string) $postData['tipo']) : '',
            'por_pagar_cobrar' => isset($postData['por_pagar_cobrar']) ? trim((string) $postData['por_pagar_cobrar']) : 'NINGUNO',
            'valor_neto' => (float) $valorNeto,
            'saldo' => $saldo !== null ? (float) $saldo : 0.0,
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

    private function validateUploadBatch()
    {
        if (!isset($_FILES['soportes']) || !is_array($_FILES['soportes'])) {
            return array('valid' => true, 'message' => '');
        }

        $files = $this->normalizeFilesArray($_FILES['soportes']);
        foreach ($files as $file) {
            if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $validation = FileUploadValidator::validate($file, $this->appConfig);
            if (empty($validation['is_valid'])) {
                $errorMessage = isset($validation['errors']) && is_array($validation['errors'])
                    ? implode(' ', $validation['errors'])
                    : 'Archivo de soporte invalido.';
                return array('valid' => false, 'message' => $errorMessage);
            }
        }

        return array('valid' => true, 'message' => '');
    }

    private function storeSupportsForMovement($movementId, $username)
    {
        if (!isset($_FILES['soportes']) || !is_array($_FILES['soportes'])) {
            return array('ok' => true, 'message' => '', 'files' => array());
        }

        $files = $this->normalizeFilesArray($_FILES['soportes']);
        if (empty($files)) {
            return array('ok' => true, 'message' => '', 'files' => array());
        }

        $storedFiles = array();
        $supportIds = array();

        foreach ($files as $file) {
            if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $originalName = isset($file['name']) ? (string) $file['name'] : '';
            $storedName = $this->buildStoredSupportFileName($originalName);
            $targetDirectory = $this->ensureMovementSupportDirectory($movementId);
            if ($targetDirectory === '') {
                $this->movimientoRepository->deleteSupportsByIds($supportIds);
                return array('ok' => false, 'message' => 'No fue posible preparar directorio de soportes.', 'files' => $storedFiles);
            }

            $absolutePath = $targetDirectory . DIRECTORY_SEPARATOR . $storedName;
            if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
                $this->movimientoRepository->deleteSupportsByIds($supportIds);
                return array('ok' => false, 'message' => 'No fue posible guardar un archivo de soporte.', 'files' => $storedFiles);
            }

            $supportId = $this->movimientoRepository->createSupportRecord($movementId, $storedName, $username);
            if ($supportId === false) {
                $this->movimientoRepository->deleteSupportsByIds($supportIds);
                return array('ok' => false, 'message' => 'No fue posible registrar soporte en base de datos.', 'files' => $storedFiles);
            }

            $supportIds[] = (int) $supportId;
            $storedFiles[] = array(
                'movement_id' => (int) $movementId,
                'stored_name' => $storedName,
            );
        }

        return array('ok' => true, 'message' => '', 'files' => $storedFiles);
    }

    private function buildMovementPersistenceData(array $formData, $userLogin)
    {
        return array(
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
    }

    private function normalizeFilesArray(array $filesInput)
    {
        $normalized = array();

        if (!isset($filesInput['name'])) {
            return $normalized;
        }

        if (is_array($filesInput['name'])) {
            $total = count($filesInput['name']);
            for ($index = 0; $index < $total; $index += 1) {
                $normalized[] = array(
                    'name' => isset($filesInput['name'][$index]) ? $filesInput['name'][$index] : '',
                    'type' => isset($filesInput['type'][$index]) ? $filesInput['type'][$index] : '',
                    'tmp_name' => isset($filesInput['tmp_name'][$index]) ? $filesInput['tmp_name'][$index] : '',
                    'error' => isset($filesInput['error'][$index]) ? $filesInput['error'][$index] : UPLOAD_ERR_NO_FILE,
                    'size' => isset($filesInput['size'][$index]) ? $filesInput['size'][$index] : 0,
                );
            }

            return $normalized;
        }

        $normalized[] = $filesInput;
        return $normalized;
    }

    private function ensureMovementSupportDirectory($movementId)
    {
        $movementIdInt = (int) $movementId;
        if ($movementIdInt <= 0) {
            return '';
        }

        $directory = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soportes' . DIRECTORY_SEPARATOR . $movementIdInt;
        if (!is_dir($directory)) {
            $created = mkdir($directory, 0755, true);
            if (!$created && !is_dir($directory)) {
                return '';
            }
        }

        return $directory;
    }

    private function buildStoredSupportFileName($originalName)
    {
        $safeOriginal = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', (string) $originalName);
        if (!is_string($safeOriginal) || trim($safeOriginal) === '') {
            $safeOriginal = 'soporte.dat';
        }

        $randomPart = bin2hex(random_bytes(6));
        return date('YmdHis') . '_' . $randomPart . '__' . $safeOriginal;
    }

    private function normalizeSupportRows(array $supportsRaw)
    {
        $normalized = array();
        foreach ($supportsRaw as $support) {
            $normalized[] = $this->normalizeSupportRow($support);
        }

        return $normalized;
    }

    private function normalizeSupportRow(array $support)
    {
        $storedName = isset($support['imagen']) ? (string) $support['imagen'] : '';
        $originalName = $this->extractOriginalSupportName($storedName);

        return array(
            'support_id' => isset($support['id']) ? (int) $support['id'] : 0,
            'movement_id' => isset($support['id_ingreso']) ? (int) $support['id_ingreso'] : 0,
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'uploaded_at' => isset($support['fechayhora']) ? (string) $support['fechayhora'] : '',
            'usuario' => isset($support['usuario']) ? (string) $support['usuario'] : '',
        );
    }

    private function extractOriginalSupportName($storedName)
    {
        $value = (string) $storedName;
        $separatorPosition = strpos($value, '__');
        if ($separatorPosition === false) {
            return $value;
        }

        return substr($value, $separatorPosition + 2);
    }

    private function resolveSupportAbsolutePath($movementId, $storedName)
    {
        $movementIdInt = (int) $movementId;
        $safeName = basename((string) $storedName);
        if ($movementIdInt <= 0 || $safeName === '') {
            return '';
        }

        $candidatePaths = array(
            PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soportes' . DIRECTORY_SEPARATOR . $movementIdInt . DIRECTORY_SEPARATOR . $safeName,
            PROJECT_ROOT . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $movementIdInt . DIRECTORY_SEPARATOR . $safeName,
        );

        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath)) {
                return $candidatePath;
            }
        }

        return $candidatePaths[0];
    }

    private function deleteSupportFiles(array $supports)
    {
        foreach ($supports as $support) {
            $movementId = isset($support['movement_id']) ? (int) $support['movement_id'] : 0;
            $storedName = isset($support['stored_name']) ? (string) $support['stored_name'] : '';
            if ($movementId <= 0 || $storedName === '') {
                continue;
            }

            $absolutePath = $this->resolveSupportAbsolutePath($movementId, $storedName);
            if ($absolutePath !== '' && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function detectMimeByExtension($extension)
    {
        $map = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        );

        $key = strtolower((string) $extension);
        return isset($map[$key]) ? $map[$key] : 'application/octet-stream';
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

    private function toCurrencyAmount($value)
    {
        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        $isNegative = strpos($stringValue, '-') === 0;
        $digitsOnly = preg_replace('/[^0-9]/', '', $stringValue);
        if (!is_string($digitsOnly) || $digitsOnly === '') {
            return null;
        }

        $normalizedDigits = ltrim($digitsOnly, '0');
        if ($normalizedDigits === '') {
            $normalizedDigits = '0';
        }

        $amount = (float) $normalizedDigits;
        return $isNegative ? (-1 * $amount) : $amount;
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
