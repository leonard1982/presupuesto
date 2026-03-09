<?php
/**
 * Proyecto PRESUPUESTO - Controlador del panel principal.
 */

namespace App\Modules\Dashboard\Controllers;

use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Dashboard\Repositories\DashboardRepository;

class DashboardController
{
    private $dashboardRepository;
    private $viewRenderer;
    private $appConfig;
    private $authService;

    public function __construct(
        DashboardRepository $dashboardRepository,
        ViewRenderer $viewRenderer,
        array $appConfig,
        AuthService $authService
    ) {
        $this->dashboardRepository = $dashboardRepository;
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
    }

    public function show()
    {
        $periodStartDate = date('Y-m-01');
        $periodEndExclusive = date('Y-m-d', strtotime($periodStartDate . ' +1 month'));
        $periodStartDateTime = $periodStartDate . ' 00:00:00';
        $periodEndDateTime = $periodEndExclusive . ' 00:00:00';

        $monthlyTotals = $this->dashboardRepository->getMonthlyTotals($periodStartDateTime, $periodEndDateTime);
        $recentMovements = $this->dashboardRepository->getRecentMovements(10);
        $topClasificaciones = $this->dashboardRepository->getTopClasificaciones($periodStartDate, $periodEndExclusive, 6);

        $balance = (float) $monthlyTotals['ingresos'] - ((float) $monthlyTotals['gastos'] + (float) $monthlyTotals['costos']);

        $this->viewRenderer->render('dashboard/index', array(
            'pageTitle' => 'Panel principal',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'dashboard',
            'monthlyTotals' => $monthlyTotals,
            'balance' => $balance,
            'periodLabel' => date('F Y'),
            'recentMovements' => $recentMovements,
            'topClasificaciones' => $topClasificaciones,
        ));
    }
}
