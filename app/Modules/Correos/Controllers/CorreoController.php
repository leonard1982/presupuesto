<?php
/**
 * Proyecto PRESUPUESTO - Controlador de bandeja de correos con sugerencia IA.
 */

namespace App\Modules\Correos\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Response;
use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Correos\Repositories\CorreoRepository;
use App\Modules\Movimientos\Repositories\MovimientoRepository;
use App\Services\Ai\CorreoSuggestionService;
use App\Services\Mail\InboxReaderService;

class CorreoController
{
    private $viewRenderer;
    private $appConfig;
    private $authService;
    private $movimientoRepository;
    private $correoRepository;
    private $inboxReaderService;
    private $correoSuggestionService;

    public function __construct(
        ViewRenderer $viewRenderer,
        array $appConfig,
        AuthService $authService,
        MovimientoRepository $movimientoRepository,
        CorreoRepository $correoRepository,
        InboxReaderService $inboxReaderService,
        CorreoSuggestionService $correoSuggestionService
    ) {
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
        $this->movimientoRepository = $movimientoRepository;
        $this->correoRepository = $correoRepository;
        $this->inboxReaderService = $inboxReaderService;
        $this->correoSuggestionService = $correoSuggestionService;
    }

    public function index()
    {
        $csrfTokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($csrfTokenName);

        $selectedUid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
        $openSuggestionParam = isset($_GET['analizar']) ? trim((string) $_GET['analizar']) : '';
        $searchText = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $flashSuccess = $this->pullFlash('correos_success');
        $flashError = $this->pullFlash('correos_error');
        $flashForm = $this->pullFlash('correos_form');
        $shouldOpenSuggestionModal = ($openSuggestionParam === '1' && $selectedUid > 0);

        $inboxResult = $this->inboxReaderService->fetchRecentMessages();
        $emails = isset($inboxResult['messages']) && is_array($inboxResult['messages']) ? $inboxResult['messages'] : array();

        if ($searchText !== '') {
            $emails = $this->filterEmails($emails, $searchText);
        }

        $selectedEmail = $this->findSelectedEmail($emails, $selectedUid);
        $clasificaciones = $this->movimientoRepository->getClasificaciones();
        $mediosPago = $this->movimientoRepository->getMediosPago();
        $presupuestosActivos = $this->movimientoRepository->getPresupuestosActivos();

        $suggestedData = array();
        if ($selectedEmail !== null) {
            $suggestedData = $this->correoSuggestionService->suggest($selectedEmail, $clasificaciones, $mediosPago);
        }

        if (is_array($flashForm) && !empty($flashForm)) {
            $flashFormUid = isset($flashForm['__uid']) ? (int) $flashForm['__uid'] : 0;
            unset($flashForm['__uid']);

            if ($selectedUid > 0 && $flashFormUid > 0 && $flashFormUid === $selectedUid) {
                $suggestedData = array_merge($suggestedData, $flashForm);
                $shouldOpenSuggestionModal = true;
            }
        }

        if (is_string($flashError) && trim($flashError) !== '' && $selectedUid > 0) {
            $shouldOpenSuggestionModal = true;
        }

        $inboxMessage = '';
        if (isset($inboxResult['ok']) && !$inboxResult['ok']) {
            $inboxMessage = isset($inboxResult['message']) ? (string) $inboxResult['message'] : 'No fue posible consultar la bandeja.';
        } elseif (empty($emails)) {
            $inboxMessage = 'No hay correos para mostrar con el filtro actual.';
        }

        $this->viewRenderer->render('correos/index', array(
            'pageTitle' => 'Bandeja de correos IA',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'correos',
            'csrfTokenName' => $csrfTokenName,
            'csrfToken' => $csrfToken,
            'emails' => $emails,
            'selectedUid' => $selectedUid,
            'selectedEmail' => $selectedEmail,
            'suggestedData' => $suggestedData,
            'clasificaciones' => $clasificaciones,
            'mediosPago' => $mediosPago,
            'presupuestosActivos' => $presupuestosActivos,
            'searchText' => $searchText,
            'successMessage' => is_string($flashSuccess) ? $flashSuccess : '',
            'errorMessage' => is_string($flashError) ? $flashError : '',
            'inboxMessage' => $inboxMessage,
            'shouldOpenSuggestionModal' => $shouldOpenSuggestionModal,
        ));
    }

    public function storeFromEmail()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('correos_error', 'La sesion del formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/correos'));
        }

        $uid = isset($_POST['email_uid']) ? (int) $_POST['email_uid'] : 0;
        $formData = $this->collectMovementFormData($_POST);
        $validation = $this->validateMovementFormData($formData);
        if (!$validation['valid']) {
            $formData['__uid'] = $uid;
            $this->setFlash('correos_error', $validation['message']);
            $this->setFlash('correos_form', $formData);
            Response::redirect($this->buildUrl('/correos') . '&uid=' . $uid . '&analizar=1');
        }

        $authenticatedUser = $this->authService->getAuthenticatedUser();
        $userLogin = isset($authenticatedUser['login']) ? (string) $authenticatedUser['login'] : 'sistema';
        $movementToPersist = $this->buildMovementPersistenceData($formData, $userLogin);

        $movementId = $this->movimientoRepository->createMovimiento($movementToPersist);
        if ($movementId === false) {
            $formData['__uid'] = $uid;
            $this->setFlash('correos_error', 'No fue posible guardar el movimiento desde correo.');
            $this->setFlash('correos_form', $formData);
            Response::redirect($this->buildUrl('/correos') . '&uid=' . $uid . '&analizar=1');
        }

        $correoData = array(
            'uid' => $uid,
            'from' => isset($_POST['email_from']) ? trim((string) $_POST['email_from']) : '',
            'subject' => isset($_POST['email_subject']) ? trim((string) $_POST['email_subject']) : '',
            'date_sql' => isset($_POST['email_date']) ? trim((string) $_POST['email_date']) : date('Y-m-d H:i:s'),
            'body_plain' => isset($_POST['email_body']) ? trim((string) $_POST['email_body']) : '',
            'hash' => isset($_POST['email_hash']) ? trim((string) $_POST['email_hash']) : '',
        );

        $supportResult = $this->createSnapshotSupportForEmail((int) $movementId, $correoData, $formData);
        if (!$supportResult['ok']) {
            $this->movimientoRepository->deleteMovimiento((int) $movementId);
            $formData['__uid'] = $uid;
            $this->setFlash('correos_error', $supportResult['message']);
            $this->setFlash('correos_form', $formData);
            Response::redirect($this->buildUrl('/correos') . '&uid=' . $uid . '&analizar=1');
        }

        $supportId = $this->movimientoRepository->createSupportRecord((int) $movementId, $supportResult['stored_name'], $userLogin);
        if ($supportId === false) {
            $this->safeDeleteFile($supportResult['absolute_path']);
            $this->movimientoRepository->deleteMovimiento((int) $movementId);
            $this->setFlash('correos_error', 'No fue posible registrar soporte del correo.');
            Response::redirect($this->buildUrl('/correos') . '&uid=' . $uid . '&analizar=1');
        }

        $this->correoRepository->registerCorreoImportacion(array(
            'correo_uid' => (string) $uid,
            'remitente' => $correoData['from'],
            'asunto' => $correoData['subject'],
            'fecha_correo' => $correoData['date_sql'],
            'contenido_hash' => $correoData['hash'],
            'sugerencia_json' => json_encode($formData),
            'movimiento_id' => (int) $movementId,
            'estado' => 'CREADO',
            'confianza' => isset($_POST['confidence']) ? (float) $_POST['confidence'] : 0.0,
            'usuario' => $userLogin,
            'observaciones' => 'Movimiento creado desde bandeja de correos',
        ));

        CsrfTokenManager::rotateToken($tokenName);
        $this->setFlash('movimientos_success', 'Movimiento creado desde correo con soporte adjunto.');
        Response::redirect($this->buildUrl('/movimientos'));
    }

    private function createSnapshotSupportForEmail($movementId, array $correoData, array $formData)
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
            return array('ok' => false, 'message' => 'GD no esta disponible para generar soporte de correo.', 'stored_name' => '', 'absolute_path' => '');
        }

        $directory = $this->ensureMovementSupportDirectory($movementId);
        if ($directory === '') {
            return array('ok' => false, 'message' => 'No fue posible preparar directorio de soportes.', 'stored_name' => '', 'absolute_path' => '');
        }

        $uid = isset($correoData['uid']) ? (int) $correoData['uid'] : 0;
        $originalName = 'correo_' . ($uid > 0 ? $uid : date('YmdHis')) . '.png';
        $storedName = $this->buildStoredSupportFileName($originalName);
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $storedName;

        $rendered = $this->renderCorreoSnapshotImage($absolutePath, $correoData, $formData);
        if (!$rendered) {
            return array('ok' => false, 'message' => 'No fue posible generar imagen de soporte del correo.', 'stored_name' => '', 'absolute_path' => '');
        }

        return array(
            'ok' => true,
            'message' => '',
            'stored_name' => $storedName,
            'absolute_path' => $absolutePath,
        );
    }

    private function renderCorreoSnapshotImage($absolutePath, array $correoData, array $formData)
    {
        $width = 1240;
        $height = 1754;
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return false;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $dark = imagecolorallocate($image, 26, 47, 74);
        $gray = imagecolorallocate($image, 88, 107, 129);
        $blue = imagecolorallocate($image, 14, 76, 129);
        $line = imagecolorallocate($image, 210, 224, 240);

        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 0, 0, $width, 86, $blue);
        imagestring($image, 5, 24, 30, $this->toImageText('Soporte de correo - PRESUPUESTO'), $white);

        $y = 110;
        $y = $this->drawWrappedLine($image, $this->toImageText('Remitente: ' . (isset($correoData['from']) ? $correoData['from'] : '')), 24, $y, $dark, 4, 160);
        $y = $this->drawWrappedLine($image, $this->toImageText('Asunto: ' . (isset($correoData['subject']) ? $correoData['subject'] : '')), 24, $y, $dark, 4, 160);
        $y = $this->drawWrappedLine($image, $this->toImageText('Fecha correo: ' . (isset($correoData['date_sql']) ? $correoData['date_sql'] : '')), 24, $y, $gray, 3, 180);
        $y += 8;
        imageline($image, 24, $y, $width - 24, $y, $line);
        $y += 18;

        $y = $this->drawWrappedLine($image, $this->toImageText('Formulario sugerido / guardado:'), 24, $y, $blue, 4, 160);
        $y = $this->drawWrappedLine($image, $this->toImageText('Clasificacion ID: ' . (isset($formData['id_clasificacion']) ? (string) $formData['id_clasificacion'] : '0')), 24, $y, $dark, 3, 170);
        $y = $this->drawWrappedLine($image, $this->toImageText('Tipo: ' . (isset($formData['tipo']) ? $formData['tipo'] : '')), 24, $y, $dark, 3, 170);
        $y = $this->drawWrappedLine($image, $this->toImageText('Categoria: ' . (isset($formData['gasto_costo']) ? $formData['gasto_costo'] : '')), 24, $y, $dark, 3, 170);
        $y = $this->drawWrappedLine($image, $this->toImageText('Valor: $ ' . number_format(isset($formData['valor']) ? (float) $formData['valor'] : 0, 0, ',', '.')), 24, $y, $dark, 3, 170);
        $y = $this->drawWrappedLine($image, $this->toImageText('Detalle: ' . (isset($formData['detalle']) ? $formData['detalle'] : '')), 24, $y, $dark, 3, 160);
        $y += 8;
        imageline($image, 24, $y, $width - 24, $y, $line);
        $y += 18;

        $bodyText = isset($correoData['body_plain']) ? (string) $correoData['body_plain'] : '';
        $bodyText = $this->truncateText($bodyText, 6000);
        $y = $this->drawWrappedLine($image, $this->toImageText('Contenido del correo:'), 24, $y, $blue, 4, 160);
        $this->drawWrappedLine($image, $this->toImageText($bodyText), 24, $y, $dark, 2, 170);

        $saved = imagepng($image, $absolutePath);
        imagedestroy($image);

        return $saved;
    }

    private function drawWrappedLine($image, $text, $x, $y, $color, $font, $maxCharsPerLine)
    {
        $content = str_replace(array("\r\n", "\r"), "\n", (string) $text);
        $lines = explode("\n", $content);
        foreach ($lines as $lineText) {
            $lineTrimmed = trim((string) $lineText);
            if ($lineTrimmed === '') {
                $y += 12;
                continue;
            }

            $chunks = $this->wordWrapForImage($lineTrimmed, $maxCharsPerLine);
            foreach ($chunks as $chunk) {
                if ($y > 1710) {
                    return $y;
                }
                imagestring($image, $font, $x, $y, $this->toImageText($chunk), $color);
                $y += 18;
            }
        }

        return $y;
    }

    private function wordWrapForImage($text, $maxLength)
    {
        $content = trim((string) $text);
        $limit = (int) $maxLength;
        if ($content === '' || $limit <= 0) {
            return array();
        }

        $words = preg_split('/\s+/', $content);
        if (!is_array($words)) {
            $words = array($content);
        }

        $lines = array();
        $line = '';

        foreach ($words as $word) {
            $token = trim((string) $word);
            if ($token === '') {
                continue;
            }

            $candidate = $line === '' ? $token : $line . ' ' . $token;
            if (strlen($candidate) > $limit) {
                if ($line !== '') {
                    $lines[] = $line;
                }
                $line = $token;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function toImageText($text)
    {
        $value = (string) $text;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }
        return $value;
    }

    private function filterEmails(array $emails, $searchText)
    {
        $needle = function_exists('mb_strtolower')
            ? mb_strtolower((string) $searchText, 'UTF-8')
            : strtolower((string) $searchText);

        $filtered = array();
        foreach ($emails as $email) {
            $subject = isset($email['subject']) ? (string) $email['subject'] : '';
            $from = isset($email['from']) ? (string) $email['from'] : '';
            $snippet = isset($email['snippet']) ? (string) $email['snippet'] : '';
            $haystack = $subject . ' ' . $from . ' ' . $snippet;
            $haystackNormalized = function_exists('mb_strtolower')
                ? mb_strtolower($haystack, 'UTF-8')
                : strtolower($haystack);

            if (strpos($haystackNormalized, $needle) !== false) {
                $filtered[] = $email;
            }
        }

        return $filtered;
    }

    private function findSelectedEmail(array $emails, $selectedUid)
    {
        $uidInt = (int) $selectedUid;
        if ($uidInt <= 0) {
            return null;
        }

        foreach ($emails as $email) {
            if ((int) (isset($email['uid']) ? $email['uid'] : 0) === $uidInt) {
                return $email;
            }
        }

        return null;
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
            return array('valid' => false, 'message' => 'Selecciona una clasificacion valida.');
        }

        if (strlen($formData['detalle']) < 3) {
            return array('valid' => false, 'message' => 'El detalle debe tener al menos 3 caracteres.');
        }

        if ((float) $formData['valor'] <= 0) {
            return array('valid' => false, 'message' => 'El valor debe ser mayor a cero.');
        }

        if (!in_array($formData['gasto_costo'], array('Ingreso', 'Gasto', 'Costo'), true)) {
            return array('valid' => false, 'message' => 'Categoria principal no valida.');
        }

        if (!in_array($formData['por_pagar_cobrar'], array('COBRAR', 'PAGAR', 'NINGUNO'), true)) {
            return array('valid' => false, 'message' => 'Estado de saldo no valido.');
        }

        if ($formData['tipo'] === '') {
            return array('valid' => false, 'message' => 'Debes seleccionar tipo o medio.');
        }

        return array('valid' => true, 'message' => '');
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
            'usuario' => (string) $userLogin,
        );
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
            $safeOriginal = 'soporte.png';
        }

        $randomPart = $this->generateRandomSegment();
        return date('YmdHis') . '_' . $randomPart . '__' . $safeOriginal;
    }

    private function generateRandomSegment()
    {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(6));
            } catch (\Exception $exception) {
                // Continua con fallback compatible.
            }
        }

        return substr(sha1(uniqid(mt_rand(), true)), 0, 12);
    }

    private function safeDeleteFile($absolutePath)
    {
        $path = (string) $absolutePath;
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    private function truncateText($value, $maxLength)
    {
        $text = trim((string) $value);
        $length = (int) $maxLength;
        if ($text === '' || $length <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $length) {
                return $text;
            }
            return mb_substr($text, 0, $length, 'UTF-8');
        }

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length);
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

        $normalized = ltrim($digitsOnly, '0');
        if ($normalized === '') {
            $normalized = '0';
        }

        $valueFloat = (float) $normalized;
        return $isNegative ? -$valueFloat : $valueFloat;
    }

    private function isDateTimeString($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return false;
        }

        $normalized = str_replace('T', ' ', $text);
        $formats = array('Y-m-d H:i:s', 'Y-m-d H:i');
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $normalized);
            if ($date instanceof \DateTime) {
                return true;
            }
        }

        return strtotime($normalized) !== false;
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
