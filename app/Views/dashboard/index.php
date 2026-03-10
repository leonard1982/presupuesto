<?php
/**
 * Proyecto PRESUPUESTO - Vista principal de dashboard.
 */

if (!function_exists('dashboard_escape')) {
    function dashboard_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dashboard_money')) {
    function dashboard_money($value)
    {
        return '$ ' . number_format((float) $value, 0, ',', '.');
    }
}

$topLabels = array();
$topTotals = array();

if (!empty($topClasificaciones) && is_array($topClasificaciones)) {
    foreach ($topClasificaciones as $item) {
        $topLabels[] = isset($item['clasificacion']) ? (string) $item['clasificacion'] : 'Sin clasificacion';
        $topTotals[] = isset($item['total']) ? (float) $item['total'] : 0;
    }
}

$chartPayload = array(
    'trend' => isset($monthlyTrend) ? $monthlyTrend : array(),
    'topClasificaciones' => array(
        'labels' => $topLabels,
        'totals' => $topTotals,
    ),
);
?>

<section class="dashboard-kpis">
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-cash-coin"></i></span>
        <p class="kpi-label">Ingresos del periodo</p>
        <h2><?php echo dashboard_money(isset($monthlyTotals['ingresos']) ? $monthlyTotals['ingresos'] : 0); ?></h2>
        <p class="muted">Periodo: <?php echo dashboard_escape(isset($periodLabel) ? $periodLabel : 'Actual'); ?></p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-cart-dash"></i></span>
        <p class="kpi-label">Gastos del periodo</p>
        <h2><?php echo dashboard_money(isset($monthlyTotals['gastos']) ? $monthlyTotals['gastos'] : 0); ?></h2>
        <p class="muted">Control diario de egresos</p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-building-gear"></i></span>
        <p class="kpi-label">Costos del periodo</p>
        <h2><?php echo dashboard_money(isset($monthlyTotals['costos']) ? $monthlyTotals['costos'] : 0); ?></h2>
        <p class="muted">Seguimiento operativo</p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>
        <p class="kpi-label">Balance estimado</p>
        <h2><?php echo dashboard_money(isset($balance) ? $balance : 0); ?></h2>
        <p class="muted">Ingresos - (gastos + costos)</p>
    </article>
</section>

<section class="grid-cards">
    <article class="card">
        <span class="title-chip"><i class="bi bi-lightning-charge"></i> Accesos rapidos</span>
        <h2>Acciones principales</h2>
        <div class="quick-actions">
            <a class="btn btn-primary btn-inline" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=movimientos/nuevo">
                <i class="bi bi-plus-circle"></i> Registrar movimiento
            </a>
            <a class="btn btn-secondary btn-inline" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=clasificaciones">
                <i class="bi bi-tags"></i> Clasificaciones
            </a>
            <a class="btn btn-secondary btn-inline" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=medios-pago">
                <i class="bi bi-credit-card-2-front"></i> Medios de pago
            </a>
        </div>
    </article>

    <article class="card">
        <span class="title-chip"><i class="bi bi-star"></i> Ranking</span>
        <h2>Clasificaciones con mayor valor</h2>
        <?php if (empty($topClasificaciones)) : ?>
            <p class="muted">No hay datos suficientes en este periodo.</p>
        <?php else : ?>
            <ul class="compact-list">
                <?php foreach ($topClasificaciones as $item) : ?>
                    <li>
                        <?php echo dashboard_escape($item['clasificacion']); ?>:
                        <strong><?php echo dashboard_money($item['total']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>

<section class="chart-grid">
    <article class="card chart-card">
        <h3><i class="bi bi-bar-chart-line"></i> Tendencia ultimos 6 meses</h3>
        <div class="chart-box">
            <canvas id="chart-trend"></canvas>
        </div>
    </article>
    <article class="card chart-card">
        <h3><i class="bi bi-pie-chart"></i> Distribucion por clasificacion</h3>
        <div class="chart-box">
            <canvas id="chart-top-clasificaciones"></canvas>
        </div>
    </article>
</section>

<section class="card table-card">
    <div class="table-header">
        <h2><i class="bi bi-clock-history"></i> Movimientos recientes</h2>
        <a class="link-action" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=movimientos">
            <i class="bi bi-arrow-right-circle"></i> Ver todos
        </a>
    </div>
    <div class="table-wrapper">
        <table class="table-professional js-data-table js-indexed-table" data-page-length="10" data-export-name="dashboard_movimientos_recientes">
            <thead>
            <tr>
                <th class="no-export">#</th>
                <th>Fecha</th>
                <th>Clasificacion</th>
                <th>Detalle</th>
                <th>Categoria</th>
                <th>Valor</th>
                <th>Usuario</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($recentMovements)) : ?>
                <tr>
                    <td colspan="7" class="muted">Sin movimientos recientes.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($recentMovements as $movement) : ?>
                    <tr>
                        <td></td>
                        <td><?php echo dashboard_escape($movement['fecha']); ?></td>
                        <td><?php echo dashboard_escape($movement['clasificacion'] !== null ? $movement['clasificacion'] : 'Sin clasificacion'); ?></td>
                        <td><?php echo dashboard_escape($movement['detalle']); ?></td>
                        <td><?php echo dashboard_escape($movement['gasto_costo']); ?></td>
                        <td><?php echo dashboard_money($movement['valor']); ?></td>
                        <td><?php echo dashboard_escape($movement['usuario']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script id="dashboard-chart-data" type="application/json"><?php echo json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
