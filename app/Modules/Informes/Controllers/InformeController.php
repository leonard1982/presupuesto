<?php
/**
 * Proyecto PRESUPUESTO - Controlador de informes y KPIs.
 */

namespace App\Modules\Informes\Controllers;

use App\Core\ViewRenderer;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Informes\Repositories\InformeRepository;

class InformeController
{
    private $informeRepository;
    private $viewRenderer;
    private $appConfig;
    private $authService;

    public function __construct(
        InformeRepository $informeRepository,
        ViewRenderer $viewRenderer,
        array $appConfig,
        AuthService $authService
    ) {
        $this->informeRepository = $informeRepository;
        $this->viewRenderer = $viewRenderer;
        $this->appConfig = $appConfig;
        $this->authService = $authService;
    }

    public function index()
    {
        $filters = $this->resolveFilters($_GET);
        $clasificaciones = $this->informeRepository->getClasificaciones();
        $tiposDisponibles = $this->informeRepository->getTiposDisponibles($filters['fecha_desde'], $filters['fecha_hasta']);

        $kpis = $this->informeRepository->getKpiSummary($filters['fecha_desde'], $filters['fecha_hasta']);
        $categoriaBreakdown = $this->informeRepository->getCategoriaBreakdown($filters['fecha_desde'], $filters['fecha_hasta']);
        $clasificacionBreakdown = $this->informeRepository->getClasificacionBreakdown($filters['fecha_desde'], $filters['fecha_hasta'], 12);
        $monthlyTrend = $this->informeRepository->getMonthlyTrend($filters['fecha_desde'], $filters['fecha_hasta']);
        $detailRows = $this->informeRepository->getDetailedRows(
            $filters['fecha_desde'],
            $filters['fecha_hasta'],
            $filters['categoria'],
            $filters['id_clasificacion'],
            $filters['tipo']
        );

        $chartPayload = $this->buildChartPayload($monthlyTrend, $categoriaBreakdown, $clasificacionBreakdown);

        $this->viewRenderer->render('informes/index', array(
            'pageTitle' => 'Informes y KPIs',
            'baseUrl' => rtrim($this->appConfig['base_url'], '/'),
            'assetVersion' => $this->appConfig['asset_version'],
            'enablePwa' => $this->appConfig['enable_pwa'],
            'currentUser' => $this->authService->getAuthenticatedUser(),
            'activeMenu' => 'informes',
            'filters' => $filters,
            'clasificaciones' => $clasificaciones,
            'tiposDisponibles' => $tiposDisponibles,
            'kpis' => $kpis,
            'categoriaBreakdown' => $categoriaBreakdown,
            'clasificacionBreakdown' => $clasificacionBreakdown,
            'detailRows' => $detailRows,
            'chartPayload' => $chartPayload,
        ));
    }

    private function resolveFilters(array $queryParams)
    {
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-d');

        $fechaDesde = isset($queryParams['fecha_desde']) ? trim((string) $queryParams['fecha_desde']) : $defaultFrom;
        $fechaHasta = isset($queryParams['fecha_hasta']) ? trim((string) $queryParams['fecha_hasta']) : $defaultTo;
        $categoria = isset($queryParams['categoria']) ? trim((string) $queryParams['categoria']) : '';
        $tipo = isset($queryParams['tipo']) ? trim((string) $queryParams['tipo']) : '';
        $idClasificacion = isset($queryParams['id_clasificacion']) ? (int) $queryParams['id_clasificacion'] : 0;

        if (!$this->isDateString($fechaDesde)) {
            $fechaDesde = $defaultFrom;
        }

        if (!$this->isDateString($fechaHasta)) {
            $fechaHasta = $defaultTo;
        }

        if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
            $temp = $fechaDesde;
            $fechaDesde = $fechaHasta;
            $fechaHasta = $temp;
        }

        if (!in_array($categoria, array('', 'Ingreso', 'Gasto', 'Costo'), true)) {
            $categoria = '';
        }

        return array(
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'categoria' => $categoria,
            'id_clasificacion' => $idClasificacion > 0 ? $idClasificacion : 0,
            'tipo' => $tipo,
        );
    }

    private function buildChartPayload(array $monthlyTrend, array $categoriaBreakdown, array $clasificacionBreakdown)
    {
        $categoriaLabels = array();
        $categoriaTotals = array();
        foreach ($categoriaBreakdown as $item) {
            $categoriaLabels[] = isset($item['categoria']) ? (string) $item['categoria'] : 'N/A';
            $categoriaTotals[] = isset($item['total']) ? (float) $item['total'] : 0.0;
        }

        $clasifLabels = array();
        $clasifTotals = array();
        foreach ($clasificacionBreakdown as $item) {
            $clasifLabels[] = isset($item['clasificacion']) ? (string) $item['clasificacion'] : 'N/A';
            $clasifTotals[] = isset($item['total']) ? (float) $item['total'] : 0.0;
        }

        return array(
            'trend' => $monthlyTrend,
            'categorias' => array(
                'labels' => $categoriaLabels,
                'totals' => $categoriaTotals,
            ),
            'clasificaciones' => array(
                'labels' => $clasifLabels,
                'totals' => $clasifTotals,
            ),
        );
    }

    private function isDateString($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return false;
        }

        $timestamp = strtotime($text);
        return $timestamp !== false;
    }
}
