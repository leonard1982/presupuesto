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
        $periodLabel = $filters['fecha_desde'] . ' a ' . $filters['fecha_hasta'];
        $corporateReports = $this->buildCorporateReports(
            $periodLabel,
            $kpis,
            $categoriaBreakdown,
            $clasificacionBreakdown,
            $monthlyTrend,
            $detailRows
        );

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
            'periodLabel' => $periodLabel,
            'corporateReports' => $corporateReports,
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

    private function buildCorporateReports($periodLabel, array $kpis, array $categoriaBreakdown, array $clasificacionBreakdown, array $monthlyTrend, array $detailRows)
    {
        $ingresosTotal = isset($kpis['ingresos_total']) ? (float) $kpis['ingresos_total'] : 0.0;
        $gastosTotal = isset($kpis['gastos_total']) ? (float) $kpis['gastos_total'] : 0.0;
        $costosTotal = isset($kpis['costos_total']) ? (float) $kpis['costos_total'] : 0.0;
        $egresosTotal = isset($kpis['egresos_total']) ? (float) $kpis['egresos_total'] : ($gastosTotal + $costosTotal);
        $balanceNeto = isset($kpis['balance_neto']) ? (float) $kpis['balance_neto'] : ($ingresosTotal - $egresosTotal);
        $margenOperativo = isset($kpis['margen_operativo']) ? (float) $kpis['margen_operativo'] : 0.0;
        $cuentasCobrar = isset($kpis['cuentas_por_cobrar']) ? (float) $kpis['cuentas_por_cobrar'] : 0.0;
        $cuentasPagar = isset($kpis['cuentas_por_pagar']) ? (float) $kpis['cuentas_por_pagar'] : 0.0;
        $ingresosLegacy = isset($kpis['ingresos_legacy']) ? (float) $kpis['ingresos_legacy'] : 0.0;
        $ingresosMovimientos = isset($kpis['ingresos_movimientos']) ? (float) $kpis['ingresos_movimientos'] : 0.0;

        $ratioGastoIngreso = isset($kpis['ratio_gasto_ingreso']) ? (float) $kpis['ratio_gasto_ingreso'] : 0.0;
        $ratioCostoIngreso = isset($kpis['ratio_costo_ingreso']) ? (float) $kpis['ratio_costo_ingreso'] : 0.0;
        $ratioEgresoIngreso = $ingresosTotal > 0 ? ($egresosTotal / $ingresosTotal) * 100 : 0.0;
        $ratioCoberturaPagar = $cuentasPagar > 0 ? ($cuentasCobrar / $cuentasPagar) * 100 : 0.0;
        $presionCartera = $ingresosTotal > 0 ? (($cuentasPagar + $cuentasCobrar) / $ingresosTotal) * 100 : 0.0;

        $netoCartera = $cuentasCobrar - $cuentasPagar;
        $exposicionNeta = $ingresosTotal > 0 ? ($netoCartera / $ingresosTotal) * 100 : 0.0;
        $coberturaIngresoCobrar = $ingresosTotal > 0 ? ($cuentasCobrar / $ingresosTotal) * 100 : 0.0;
        $coberturaIngresoPagar = $ingresosTotal > 0 ? ($cuentasPagar / $ingresosTotal) * 100 : 0.0;

        $clasificacionTotal = 0.0;
        foreach ($clasificacionBreakdown as $item) {
            $clasificacionTotal += isset($item['total']) ? (float) $item['total'] : 0.0;
        }

        $clasificacionesRows = array();
        foreach ($clasificacionBreakdown as $index => $item) {
            $total = isset($item['total']) ? (float) $item['total'] : 0.0;
            $participacion = $clasificacionTotal > 0 ? ($total / $clasificacionTotal) * 100 : 0.0;
            $clasificacionesRows[] = array(
                'orden' => $index + 1,
                'clasificacion' => isset($item['clasificacion']) ? (string) $item['clasificacion'] : 'Sin clasificacion',
                'total' => $total,
                'participacion' => $participacion,
            );
        }

        $categoriasRows = array();
        $categoriasTotal = 0.0;
        foreach ($categoriaBreakdown as $item) {
            $categoriasTotal += isset($item['total']) ? (float) $item['total'] : 0.0;
        }
        foreach ($categoriaBreakdown as $item) {
            $total = isset($item['total']) ? (float) $item['total'] : 0.0;
            $participacion = $categoriasTotal > 0 ? ($total / $categoriasTotal) * 100 : 0.0;
            $categoriasRows[] = array(
                'categoria' => isset($item['categoria']) ? (string) $item['categoria'] : 'N/A',
                'total' => $total,
                'participacion' => $participacion,
            );
        }

        $trendRows = array();
        $labels = isset($monthlyTrend['labels']) && is_array($monthlyTrend['labels']) ? $monthlyTrend['labels'] : array();
        $ingresosSeries = isset($monthlyTrend['ingresos']) && is_array($monthlyTrend['ingresos']) ? $monthlyTrend['ingresos'] : array();
        $gastosSeries = isset($monthlyTrend['gastos']) && is_array($monthlyTrend['gastos']) ? $monthlyTrend['gastos'] : array();
        $costosSeries = isset($monthlyTrend['costos']) && is_array($monthlyTrend['costos']) ? $monthlyTrend['costos'] : array();
        $balanceSeries = isset($monthlyTrend['balance']) && is_array($monthlyTrend['balance']) ? $monthlyTrend['balance'] : array();

        $seriesLength = count($labels);
        for ($index = 0; $index < $seriesLength; $index += 1) {
            $incomeValue = isset($ingresosSeries[$index]) ? (float) $ingresosSeries[$index] : 0.0;
            $expenseValue = isset($gastosSeries[$index]) ? (float) $gastosSeries[$index] : 0.0;
            $costValue = isset($costosSeries[$index]) ? (float) $costosSeries[$index] : 0.0;
            $balanceValue = isset($balanceSeries[$index]) ? (float) $balanceSeries[$index] : ($incomeValue - ($expenseValue + $costValue));

            $trendRows[] = array(
                'periodo' => isset($labels[$index]) ? (string) $labels[$index] : '',
                'ingresos' => $incomeValue,
                'gastos' => $expenseValue,
                'costos' => $costValue,
                'balance' => $balanceValue,
            );
        }

        $operationalRows = array_slice($detailRows, 0, 40);
        $sourceSummary = array(
            'Movimientos' => 0,
            'Ingresos legacy' => 0,
        );
        foreach ($detailRows as $row) {
            $source = isset($row['origen']) ? (string) $row['origen'] : '';
            if ($source === '') {
                continue;
            }
            if (!isset($sourceSummary[$source])) {
                $sourceSummary[$source] = 0;
            }
            $sourceSummary[$source] += 1;
        }

        return array(
            'period_label' => (string) $periodLabel,
            'report_1' => array(
                'title' => 'Informe 1 - Estado de resultados ejecutivo',
                'subtitle' => 'Consolidado financiero del periodo evaluado.',
                'rows' => array(
                    array('concepto' => 'Ingresos totales', 'valor' => $ingresosTotal),
                    array('concepto' => 'Ingresos legacy', 'valor' => $ingresosLegacy),
                    array('concepto' => 'Ingresos nuevos en movimientos', 'valor' => $ingresosMovimientos),
                    array('concepto' => 'Gastos', 'valor' => $gastosTotal),
                    array('concepto' => 'Costos', 'valor' => $costosTotal),
                    array('concepto' => 'Egresos totales', 'valor' => $egresosTotal),
                    array('concepto' => 'Balance neto', 'valor' => $balanceNeto),
                    array('concepto' => 'Margen operativo', 'valor_percent' => $margenOperativo),
                ),
                'highlight' => $balanceNeto >= 0 ? 'Resultado positivo del periodo.' : 'Resultado negativo. Revisar gastos/costos y liquidez.',
            ),
            'report_2' => array(
                'title' => 'Informe 2 - Ratios y productividad financiera',
                'subtitle' => 'Indicadores de eficiencia para control gerencial.',
                'rows' => array(
                    array('indicador' => 'Ratio gasto / ingreso', 'valor' => $ratioGastoIngreso, 'estado' => $this->resolveRatioStatus($ratioGastoIngreso, 45, 65)),
                    array('indicador' => 'Ratio costo / ingreso', 'valor' => $ratioCostoIngreso, 'estado' => $this->resolveRatioStatus($ratioCostoIngreso, 35, 55)),
                    array('indicador' => 'Ratio egreso / ingreso', 'valor' => $ratioEgresoIngreso, 'estado' => $this->resolveRatioStatus($ratioEgresoIngreso, 75, 95)),
                    array('indicador' => 'Cobertura cobrar/pagar', 'valor' => $ratioCoberturaPagar, 'estado' => $this->resolveCoverageStatus($ratioCoberturaPagar)),
                    array('indicador' => 'Presion de cartera sobre ingresos', 'valor' => $presionCartera, 'estado' => $this->resolveRatioStatus($presionCartera, 35, 55)),
                ),
            ),
            'report_3' => array(
                'title' => 'Informe 3 - Cartera y compromisos',
                'subtitle' => 'Seguimiento de cuentas por cobrar, por pagar y exposicion neta.',
                'rows' => array(
                    array('concepto' => 'Cuentas por cobrar', 'valor' => $cuentasCobrar),
                    array('concepto' => 'Cuentas por pagar', 'valor' => $cuentasPagar),
                    array('concepto' => 'Neto de cartera (cobrar - pagar)', 'valor' => $netoCartera),
                    array('concepto' => 'Cobertura cobrar sobre ingresos', 'valor_percent' => $coberturaIngresoCobrar),
                    array('concepto' => 'Cobertura pagar sobre ingresos', 'valor_percent' => $coberturaIngresoPagar),
                    array('concepto' => 'Exposicion neta sobre ingresos', 'valor_percent' => $exposicionNeta),
                ),
                'highlight' => $netoCartera >= 0 ? 'Cartera neta favorable para flujo.' : 'Cartera neta comprometida: priorizar recaudo y pagos criticos.',
            ),
            'report_4' => array(
                'title' => 'Informe 4 - Impacto por clasificacion y categoria',
                'subtitle' => 'Distribucion corporativa para decisiones de ajuste presupuestal.',
                'clasificaciones' => $clasificacionesRows,
                'categorias' => $categoriasRows,
            ),
            'report_5' => array(
                'title' => 'Informe 5 - Trazabilidad operacional',
                'subtitle' => 'Resumen de tendencia mensual y bitacora de registros del periodo.',
                'trend_rows' => $trendRows,
                'operational_rows' => $operationalRows,
                'source_summary' => $sourceSummary,
                'total_registros' => count($detailRows),
            ),
        );
    }

    private function resolveRatioStatus($value, $goodMax, $warnMax)
    {
        $number = (float) $value;
        if ($number <= (float) $goodMax) {
            return 'Optimo';
        }
        if ($number <= (float) $warnMax) {
            return 'Control';
        }
        return 'Riesgo';
    }

    private function resolveCoverageStatus($value)
    {
        $number = (float) $value;
        if ($number >= 120.0) {
            return 'Optimo';
        }
        if ($number >= 90.0) {
            return 'Control';
        }
        return 'Riesgo';
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
