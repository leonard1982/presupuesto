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
        return '$ ' . number_format((float) $value, 2, ',', '.');
    }
}
?>
<section class="dashboard-kpis">
    <article class="card kpi-card">
        <p class="kpi-label">Ingresos del periodo</p>
        <h2><?php echo dashboard_money(isset($monthlyTotals['ingresos']) ? $monthlyTotals['ingresos'] : 0); ?></h2>
        <p class="muted">Periodo: <?php echo dashboard_escape(isset($periodLabel) ? $periodLabel : 'Actual'); ?></p>
    </article>
    <article class="card kpi-card">
        <p class="kpi-label">Gastos del periodo</p>
        <h2><?php echo dashboard_money(isset($monthlyTotals['gastos']) ? $monthlyTotals['gastos'] : 0); ?></h2>
        <p class="muted">Control diario de egresos</p>
    </article>
    <article class="card kpi-card">
        <p class="kpi-label">Costos del periodo</p>
        <h2><?php echo dashboard_money(isset($monthlyTotals['costos']) ? $monthlyTotals['costos'] : 0); ?></h2>
        <p class="muted">Seguimiento de operacion</p>
    </article>
    <article class="card kpi-card">
        <p class="kpi-label">Balance estimado</p>
        <h2><?php echo dashboard_money(isset($balance) ? $balance : 0); ?></h2>
        <p class="muted">Ingresos - (gastos + costos)</p>
    </article>
</section>

<section class="grid-cards">
    <article class="card">
        <h2>Accesos rapidos</h2>
        <div class="quick-actions">
            <a class="btn btn-primary" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=movimientos/nuevo">Registrar movimiento</a>
            <a class="btn btn-secondary" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=clasificaciones">Administrar clasificaciones</a>
            <a class="btn btn-secondary" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=medios-pago">Administrar medios de pago</a>
        </div>
    </article>

    <article class="card">
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

<section class="card table-card">
    <div class="table-header">
        <h2>Movimientos recientes</h2>
        <a class="link-action" href="<?php echo dashboard_escape($baseUrl); ?>/index.php?route=movimientos">Ver todos</a>
    </div>
    <div class="table-wrapper">
        <table class="table-professional">
            <thead>
            <tr>
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
                    <td colspan="6" class="muted">Sin movimientos recientes.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($recentMovements as $movement) : ?>
                    <tr>
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
    </article>
</section>
