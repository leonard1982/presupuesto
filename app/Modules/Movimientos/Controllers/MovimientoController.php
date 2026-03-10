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

        foreach ($movimientos as &$movement) {
            $movement['supports'] = $this->parseSoporteField(isset($movement['soporte']) ? $movement['soporte'] : '');
            $movement['supports_count'] = count($movement['supports']);
        }
        unset($movement);

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
            'csrfTokenName' => $this->appConfig['csrf_token_name'],
            'csrfToken' => CsrfTokenManager::generateToken($this->appConfig['csrf_token_name']),
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
            'existingSupports' => $this->parseSoporteField(isset($movement['soporte']) ? $movement['soporte'] : ''),
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

        $uploadedSupportsResult = $this->handleUploadedSupports(array());
        if (!$uploadedSupportsResult['ok']) {
            $this->setFlash('movimientos_error', $uploadedSupportsResult['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/nuevo'));
        }

        $authenticatedUser = $this->authService->getAuthenticatedUser();
        $userLogin = isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema';

        $movementToPersist = $this->buildMovementPersistenceData($formData, $userLogin, $uploadedSupportsResult['supports']);
        $saved = $this->movimientoRepository->createMovimiento($movementToPersist);

        if (!$saved) {
            $this->rollbackSupports($uploadedSupportsResult['supports']);
            $this->setFlash('movimientos_error', 'No fue posible guardar el movimiento. Revisa el log de aplicacion.');
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

        $existingSupports = $this->parseSoporteField(isset($existingMovement['soporte']) ? $existingMovement['soporte'] : '');
        $uploadedSupportsResult = $this->handleUploadedSupports($existingSupports);
        if (!$uploadedSupportsResult['ok']) {
            $this->setFlash('movimientos_error', $uploadedSupportsResult['message']);
            $this->setFlash('movimientos_old_input', $formData);
            Response::redirect($this->buildUrl('/movimientos/editar') . '&id=' . $movementId);
        }

        $authenticatedUser = $this->authService->getAuthenticatedUser();
        $userLogin = isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema';

        $movementToPersist = $this->buildMovementPersistenceData($formData, $userLogin, $uploadedSupportsResult['supports']);
        $updated = $this->movimientoRepository->updateMovimiento($movementId, $movementToPersist);

        if (!$updated) {
            $newOnlySupports = $this->getNewSupportsDiff($existingSupports, $uploadedSupportsResult['supports']);
            $this->rollbackSupports($newOnlySupports);
            $this->setFlash('movimientos_error', 'No fue posible actualizar el movimiento.');
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

        $supports = $this->parseSoporteField(isset($movement['soporte']) ? $movement['soporte'] : '');

        $deleted = $this->movimientoRepository->deleteMovimiento($movementId);
        if (!$deleted) {
            $this->setFlash('movimientos_error', 'No fue posible eliminar el movimiento.');
            Response::redirect($this->buildUrl('/movimientos'));
        }

        $this->deleteSupportsFromDisk($supports);
        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('movimientos_success', 'Movimiento eliminado correctamente.');
        Response::redirect($this->buildUrl('/movimientos'));
    }

    public function downloadSupport()
    {
        $movementId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $storedName = isset($_GET['file']) ? trim((string) $_GET['file']) : '';

        if ($movementId <= 0 || $storedName === '') {
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

        $supports = $this->parseSoporteField(isset($movement['soporte']) ? $movement['soporte'] : '');
        $selectedSupport = null;

        foreach ($supports as $support) {
            if (isset($support['stored_name']) && (string) $support['stored_name'] === $storedName) {
                $selectedSupport = $support;
                break;
            }
        }

        if (!$selectedSupport) {
            http_response_code(404);
            echo 'Soporte no encontrado.';
            exit;
        }

        $absolutePath = $this->resolveSupportAbsolutePath($selectedSupport);
        if (!is_file($absolutePath)) {
            http_response_code(404);
            echo 'Archivo no disponible.';
            exit;
        }

        $mime = isset($selectedSupport['mime']) ? (string) $selectedSupport['mime'] : 'application/octet-stream';
        $originalName = isset($selectedSupport['original_name']) ? (string) $selectedSupport['original_name'] : basename($absolutePath);
        $size = filesize($absolutePath);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) ($size !== false ? $size : 0));
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $originalName) . '"');
        readfile($absolutePath);
        exit;
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

    private function handleUploadedSupports(array $existingSupports)
    {
        $supports = $existingSupports;

        if (!isset($_FILES['soportes']) || !is_array($_FILES['soportes'])) {
            return array('ok' => true, 'message' => '', 'supports' => $supports);
        }

        $supportsFiles = $this->normalizeFilesArray($_FILES['soportes']);
        if (empty($supportsFiles)) {
            return array('ok' => true, 'message' => '', 'supports' => $supports);
        }

        $uploadDirectory = $this->ensureSupportsDirectory();
        if ($uploadDirectory === '') {
            return array('ok' => false, 'message' => 'No fue posible preparar el directorio de soportes.', 'supports' => $supports);
        }

        foreach ($supportsFiles as $file) {
            if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $validation = FileUploadValidator::validate($file, $this->appConfig);
            if (empty($validation['is_valid'])) {
                $errors = isset($validation['errors']) && is_array($validation['errors']) ? implode(' ', $validation['errors']) : 'Archivo invalido.';
                return array('ok' => false, 'message' => $errors, 'supports' => $supports);
            }

            $extension = isset($validation['extension']) ? (string) $validation['extension'] : '';
            $randomName = $this->generateSupportStoredName($extension);
            $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $randomName;

            if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
                return array('ok' => false, 'message' => 'No fue posible guardar un archivo de soporte.', 'supports' => $supports);
            }

            $relativePath = $this->buildSupportRelativePath($randomName);
            $supports[] = array(
                'original_name' => isset($file['name']) ? (string) $file['name'] : $randomName,
                'stored_name' => $randomName,
                'relative_path' => $relativePath,
                'mime' => isset($validation['detected_mime']) ? (string) $validation['detected_mime'] : 'application/octet-stream',
                'size_bytes' => isset($validation['size_bytes']) ? (int) $validation['size_bytes'] : 0,
                'uploaded_at' => date('Y-m-d H:i:s'),
            );
        }

        return array('ok' => true, 'message' => '', 'supports' => $supports);
    }

    private function buildMovementPersistenceData(array $formData, $userLogin, array $supports)
    {
        return array(
            'fecha' => $formData['fecha'],
            'id_clasificacion' => (int) $formData['id_clasificacion'],
            'detalle' => $formData['detalle'],
            'valor' => (float) $formData['valor'],
            'fecha_periodo' => substr($formData['fecha'], 0, 10),
            'id_presupuesto' => (int) $formData['id_presupuesto'],
            'soporte' => $this->serializeSoporteField($supports),
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

    private function ensureSupportsDirectory()
    {
        $basePath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soportes';
        $year = date('Y');
        $month = date('m');
        $targetPath = $basePath . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;

        if (!is_dir($targetPath)) {
            $created = mkdir($targetPath, 0755, true);
            if (!$created && !is_dir($targetPath)) {
                return '';
            }
        }

        return $targetPath;
    }

    private function buildSupportRelativePath($storedName)
    {
        return date('Y') . '/' . date('m') . '/' . $storedName;
    }

    private function generateSupportStoredName($extension)
    {
        $safeExtension = $extension !== '' ? '.' . $extension : '';
        $randomPart = bin2hex(random_bytes(8));
        return date('YmdHis') . '_' . $randomPart . $safeExtension;
    }

    private function parseSoporteField($rawSoporte)
    {
        $raw = trim((string) $rawSoporte);
        if ($raw === '') {
            return array();
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $supports = array();
            foreach ($decoded as $item) {
                if (!is_array($item)) {
                    continue;
                }

                if (!isset($item['stored_name']) || trim((string) $item['stored_name']) === '') {
                    continue;
                }

                $supports[] = array(
                    'original_name' => isset($item['original_name']) ? (string) $item['original_name'] : (string) $item['stored_name'],
                    'stored_name' => (string) $item['stored_name'],
                    'relative_path' => isset($item['relative_path']) ? (string) $item['relative_path'] : '',
                    'mime' => isset($item['mime']) ? (string) $item['mime'] : 'application/octet-stream',
                    'size_bytes' => isset($item['size_bytes']) ? (int) $item['size_bytes'] : 0,
                    'uploaded_at' => isset($item['uploaded_at']) ? (string) $item['uploaded_at'] : '',
                );
            }

            return $supports;
        }

        return array(
            array(
                'original_name' => basename($raw),
                'stored_name' => basename($raw),
                'relative_path' => $raw,
                'mime' => 'application/octet-stream',
                'size_bytes' => 0,
                'uploaded_at' => '',
            ),
        );
    }

    private function serializeSoporteField(array $supports)
    {
        if (empty($supports)) {
            return '';
        }

        $encoded = json_encode($supports);
        return is_string($encoded) ? $encoded : '';
    }

    private function resolveSupportAbsolutePath(array $support)
    {
        $relativePath = isset($support['relative_path']) ? trim((string) $support['relative_path']) : '';
        if ($relativePath !== '') {
            $normalizedRelative = str_replace(array('\\', '..'), array('/', ''), $relativePath);
            return PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soportes' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedRelative);
        }

        $storedName = isset($support['stored_name']) ? (string) $support['stored_name'] : '';
        if ($storedName === '') {
            return '';
        }

        return PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soportes' . DIRECTORY_SEPARATOR . $storedName;
    }

    private function rollbackSupports(array $supports)
    {
        foreach ($supports as $support) {
            $absolutePath = $this->resolveSupportAbsolutePath($support);
            if ($absolutePath !== '' && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function deleteSupportsFromDisk(array $supports)
    {
        foreach ($supports as $support) {
            $absolutePath = $this->resolveSupportAbsolutePath($support);
            if ($absolutePath !== '' && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function getNewSupportsDiff(array $oldSupports, array $newSupports)
    {
        $oldMap = array();
        foreach ($oldSupports as $support) {
            if (isset($support['stored_name'])) {
                $oldMap[(string) $support['stored_name']] = true;
            }
        }

        $diff = array();
        foreach ($newSupports as $support) {
            $storedName = isset($support['stored_name']) ? (string) $support['stored_name'] : '';
            if ($storedName === '' || isset($oldMap[$storedName])) {
                continue;
            }

            $diff[] = $support;
        }

        return $diff;
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
