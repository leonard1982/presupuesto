<?php
/**
 * Proyecto PRESUPUESTO - Controlador del panel principal.
 */

namespace App\Modules\Dashboard\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Logger;
use App\Core\Response;
use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Dashboard\Repositories\DashboardRepository;
use App\Services\Ai\KpiAdvisorService;
use App\Services\Dashboard\DashboardReportComposer;
use App\Services\Mail\SmtpMailer;

class DashboardController
{
    private $dashboardRepository;
    private $viewRenderer;
    private $appConfig;
    private $authService;
    private $logger;
    private $mailer;
    private $reportComposer;
    private $kpiAdvisor;

    public function __construct(
        DashboardRepository $dashboardRepository,
        ViewRenderer $viewRenderer,
        array $appConfig,
        AuthService $authService,
        Logger $logger
    ) {
        $this->dashboardRepository = $dashboardRepository;
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
        $this->logger = $logger;
        $this->mailer = new SmtpMailer(isset($appConfig['mail']) ? $appConfig['mail'] : array(), $logger);
        $this->reportComposer = new DashboardReportComposer();
        $this->kpiAdvisor = new KpiAdvisorService(isset($appConfig['ai']) ? $appConfig['ai'] : array(), $logger);
    }

    public function show()
    {
        $snapshot = $this->buildDashboardSnapshot(10);
        $csrfTokenName = $this->appConfig['csrf_token_name'];
        $csrfToken = CsrfTokenManager::generateToken($csrfTokenName);

        $successMessage = $this->pullFlash('dashboard_success');
        $errorMessage = $this->pullFlash('dashboard_error');
        $aiAdvice = $this->pullFlash('dashboard_ai_advice');
        $reportForm = $this->pullFlash('dashboard_report_form');

        if ((!is_array($aiAdvice) || empty($aiAdvice)) && isset($_SESSION['dashboard_last_ai_advice']) && is_array($_SESSION['dashboard_last_ai_advice'])) {
            $aiAdvice = $_SESSION['dashboard_last_ai_advice'];
        }

        $this->viewRenderer->render('dashboard/index', array(
            'pageTitle' => 'Panel principal',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'dashboard',
            'monthlyTotals' => $snapshot['monthly_totals'],
            'balance' => $snapshot['balance'],
            'periodLabel' => $snapshot['period_label'],
            'recentMovements' => $snapshot['recent_movements'],
            'topClasificaciones' => $snapshot['top_clasificaciones'],
            'monthlyTrend' => $snapshot['monthly_trend'],
            'dashboardSuccessMessage' => is_string($successMessage) ? $successMessage : '',
            'dashboardErrorMessage' => is_string($errorMessage) ? $errorMessage : '',
            'kpiAdvice' => is_array($aiAdvice) ? $aiAdvice : array(),
            'reportForm' => is_array($reportForm) ? $reportForm : array(),
            'csrfTokenName' => $csrfTokenName,
            'csrfToken' => $csrfToken,
        ));
    }

    public function sendReportEmail()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('dashboard_error', 'La sesion del formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/dashboard'));
        }

        $recipient = isset($_POST['correo_destino']) ? trim((string) $_POST['correo_destino']) : '';
        $subject = isset($_POST['asunto_informe']) ? trim((string) $_POST['asunto_informe']) : '';

        $this->setFlash('dashboard_report_form', array(
            'correo_destino' => $recipient,
            'asunto_informe' => $subject,
        ));

        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('dashboard_error', 'Debes ingresar un correo destino valido.');
            Response::redirect($this->buildUrl('/dashboard'));
        }

        $snapshot = $this->buildDashboardSnapshot(10);
        $mailPayload = $this->reportComposer->compose($snapshot, array(
            'subject' => $subject,
        ));

        $sendResult = $this->mailer->sendHtml(
            $recipient,
            isset($mailPayload['subject']) ? $mailPayload['subject'] : 'Informe PRESUPUESTO',
            isset($mailPayload['html']) ? $mailPayload['html'] : '',
            isset($mailPayload['text']) ? $mailPayload['text'] : ''
        );

        if (!empty($sendResult['ok'])) {
            $this->setFlash('dashboard_success', isset($sendResult['message']) ? (string) $sendResult['message'] : 'Informe enviado.');
        } else {
            $this->setFlash('dashboard_error', isset($sendResult['message']) ? (string) $sendResult['message'] : 'No fue posible enviar el informe.');
        }

        CsrfTokenManager::rotateToken($tokenName);
        Response::redirect($this->buildUrl('/dashboard'));
    }

    public function generateKpiAdvice()
    {
        $tokenName = $this->appConfig['csrf_token_name'];
        $providedToken = isset($_POST[$tokenName]) ? (string) $_POST[$tokenName] : '';
        if (!CsrfTokenManager::validateToken($tokenName, $providedToken)) {
            $this->setFlash('dashboard_error', 'La sesion del formulario caduco. Intenta nuevamente.');
            Response::redirect($this->buildUrl('/dashboard'));
        }

        $snapshot = $this->buildDashboardSnapshot(10);
        $advice = $this->kpiAdvisor->generateAdvice($snapshot);
        $this->setFlash('dashboard_ai_advice', $advice);
        $_SESSION['dashboard_last_ai_advice'] = $advice;
        $this->setFlash('dashboard_success', 'Consejo KPI actualizado.');

        CsrfTokenManager::rotateToken($tokenName);
        Response::redirect($this->buildUrl('/dashboard'));
    }

    private function buildDashboardSnapshot($recentLimit)
    {
        $periodStartDate = date('Y-m-01');
        $periodEndExclusive = date('Y-m-d', strtotime($periodStartDate . ' +1 month'));
        $periodStartDateTime = $periodStartDate . ' 00:00:00';
        $periodEndDateTime = $periodEndExclusive . ' 00:00:00';

        $monthlyTotals = $this->dashboardRepository->getMonthlyTotals($periodStartDateTime, $periodEndDateTime);
        $recentMovements = $this->dashboardRepository->getRecentMovements((int) $recentLimit);
        $topClasificaciones = $this->dashboardRepository->getTopClasificaciones($periodStartDate, $periodEndExclusive, 6);
        $monthlyTrend = $this->dashboardRepository->getMonthlyTrend(6);
        $balance = (float) $monthlyTotals['ingresos'] - ((float) $monthlyTotals['gastos'] + (float) $monthlyTotals['costos']);

        return array(
            'period_start' => $periodStartDate,
            'period_end_exclusive' => $periodEndExclusive,
            'period_label' => $this->formatPeriodLabel($periodStartDate),
            'monthly_totals' => $monthlyTotals,
            'balance' => $balance,
            'recent_movements' => $recentMovements,
            'top_clasificaciones' => $topClasificaciones,
            'monthly_trend' => $monthlyTrend,
            'generated_at' => date('Y-m-d H:i:s'),
        );
    }

    private function formatPeriodLabel($periodStartDate)
    {
        $timestamp = strtotime((string) $periodStartDate);
        if ($timestamp === false) {
            return '';
        }

        $months = array(
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        );

        $monthNumber = (int) date('n', $timestamp);
        $monthLabel = isset($months[$monthNumber]) ? $months[$monthNumber] : date('F', $timestamp);

        return $monthLabel . ' ' . date('Y', $timestamp);
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
