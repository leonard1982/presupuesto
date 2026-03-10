<?php
/**
 * Proyecto PRESUPUESTO - Vista de informes y KPIs ejecutivos.
 */

if (!function_exists('inf_escape')) {
    function inf_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('inf_money')) {
    function inf_money($value)
    {
        return '$ ' . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('inf_percent')) {
    function inf_percent($value)
    {
        return number_format((float) $value, 1, ',', '.') . '%';
    }
}
?>
<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-bar-chart-line"></i> Analitica ejecutiva</span>
        <h2>Informes y KPIs</h2>
        <p class="muted">Panel profesional para analizar ingresos, gastos, costos, balance y comportamiento financiero.</p>
    </div>
</section>

<section class="card movement-filters-card">
    <form method="get" action="<?php echo inf_escape($baseUrl); ?>/index.php" class="compact-form">
        <input type="hidden" name="route" value="informes">
        <div class="movement-filters-grid">
            <div class="form-field">
                <label for="fecha_desde">Fecha desde</label>
                <input id="fecha_desde" name="fecha_desde" type="date" value="<?php echo inf_escape($filters['fecha_desde']); ?>">
            </div>
            <div class="form-field">
                <label for="fecha_hasta">Fecha hasta</label>
                <input id="fecha_hasta" name="fecha_hasta" type="date" value="<?php echo inf_escape($filters['fecha_hasta']); ?>">
            </div>
            <div class="form-field">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="" <?php echo $filters['categoria'] === '' ? 'selected' : ''; ?>>Todas</option>
                    <option value="Ingreso" <?php echo $filters['categoria'] === 'Ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                    <option value="Gasto" <?php echo $filters['categoria'] === 'Gasto' ? 'selected' : ''; ?>>Gasto</option>
                    <option value="Costo" <?php echo $filters['categoria'] === 'Costo' ? 'selected' : ''; ?>>Costo</option>
                </select>
            </div>
            <div class="form-field">
                <label for="id_clasificacion">Clasificacion</label>
                <select id="id_clasificacion" name="id_clasificacion" class="js-searchable-select" data-placeholder="Todas las clasificaciones">
                    <option value="0">Todas</option>
                    <?php foreach ($clasificaciones as $clasificacion) : ?>
                        <?php $clasificacionId = isset($clasificacion['id']) ? (int) $clasificacion['id'] : 0; ?>
                        <option value="<?php echo $clasificacionId; ?>" <?php echo ((int) $filters['id_clasificacion'] === $clasificacionId) ? 'selected' : ''; ?>>
                            <?php echo inf_escape(isset($clasificacion['descripcion']) ? $clasificacion['descripcion'] : ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="tipo">Tipo o medio</label>
                <select id="tipo" name="tipo" class="js-searchable-select" data-placeholder="Todos los tipos">
                    <option value="">Todos</option>
                    <?php foreach ($tiposDisponibles as $tipoLabel) : ?>
                        <option value="<?php echo inf_escape($tipoLabel); ?>" <?php echo ((string) $filters['tipo'] === (string) $tipoLabel) ? 'selected' : ''; ?>>
                            <?php echo inf_escape($tipoLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="movement-filters-actions">
            <button type="submit" class="btn btn-primary btn-inline">
                <i class="bi bi-funnel"></i> Aplicar filtros
            </button>
            <a class="btn btn-secondary btn-inline" href="<?php echo inf_escape($baseUrl); ?>/index.php?route=informes">
                <i class="bi bi-arrow-counterclockwise"></i> Limpiar
            </a>
        </div>
    </form>
</section>

<section class="dashboard-kpis">
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-cash-stack"></i></span>
        <p class="kpi-label">Ingresos totales</p>
        <h2><?php echo inf_money(isset($kpis['ingresos_total']) ? $kpis['ingresos_total'] : 0); ?></h2>
        <p class="muted">Legacy + nuevos ingresos</p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-cart-x"></i></span>
        <p class="kpi-label">Gastos</p>
        <h2><?php echo inf_money(isset($kpis['gastos_total']) ? $kpis['gastos_total'] : 0); ?></h2>
        <p class="muted">Ratio: <?php echo inf_percent(isset($kpis['ratio_gasto_ingreso']) ? $kpis['ratio_gasto_ingreso'] : 0); ?></p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-tools"></i></span>
        <p class="kpi-label">Costos</p>
        <h2><?php echo inf_money(isset($kpis['costos_total']) ? $kpis['costos_total'] : 0); ?></h2>
        <p class="muted">Ratio: <?php echo inf_percent(isset($kpis['ratio_costo_ingreso']) ? $kpis['ratio_costo_ingreso'] : 0); ?></p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>
        <p class="kpi-label">Balance neto</p>
        <h2><?php echo inf_money(isset($kpis['balance_neto']) ? $kpis['balance_neto'] : 0); ?></h2>
        <p class="muted">Margen: <?php echo inf_percent(isset($kpis['margen_operativo']) ? $kpis['margen_operativo'] : 0); ?></p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-wallet2"></i></span>
        <p class="kpi-label">Egresos totales</p>
        <h2><?php echo inf_money(isset($kpis['egresos_total']) ? $kpis['egresos_total'] : 0); ?></h2>
        <p class="muted">Gastos + costos</p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-hourglass-split"></i></span>
        <p class="kpi-label">Cuentas por cobrar</p>
        <h2><?php echo inf_money(isset($kpis['cuentas_por_cobrar']) ? $kpis['cuentas_por_cobrar'] : 0); ?></h2>
        <p class="muted">Saldo pendiente de cobro</p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-hourglass-bottom"></i></span>
        <p class="kpi-label">Cuentas por pagar</p>
        <h2><?php echo inf_money(isset($kpis['cuentas_por_pagar']) ? $kpis['cuentas_por_pagar'] : 0); ?></h2>
        <p class="muted">Compromisos pendientes</p>
    </article>
    <article class="card kpi-card">
        <span class="kpi-icon"><i class="bi bi-piggy-bank"></i></span>
        <p class="kpi-label">Ingresos legacy</p>
        <h2><?php echo inf_money(isset($kpis['ingresos_legacy']) ? $kpis['ingresos_legacy'] : 0); ?></h2>
        <p class="muted">Tabla historica `ingresos`</p>
    </article>
</section>

<section class="chart-grid">
    <article class="card chart-card">
        <h3><i class="bi bi-activity"></i> Tendencia del periodo</h3>
        <div class="chart-box">
            <canvas id="chart-informes-trend"></canvas>
        </div>
    </article>
    <article class="card chart-card">
        <h3><i class="bi bi-pie-chart"></i> Distribucion por categoria</h3>
        <div class="chart-box">
            <canvas id="chart-informes-categorias"></canvas>
        </div>
    </article>
</section>

<section class="grid-cards">
    <article class="card">
        <span class="title-chip"><i class="bi bi-trophy"></i> Top clasificaciones</span>
        <h2>Clasificaciones con mayor impacto</h2>
        <?php if (empty($clasificacionBreakdown)) : ?>
            <p class="muted">No hay datos para el filtro seleccionado.</p>
        <?php else : ?>
            <ul class="compact-list">
                <?php foreach ($clasificacionBreakdown as $item) : ?>
                    <li>
                        <?php echo inf_escape(isset($item['clasificacion']) ? $item['clasificacion'] : 'Sin clasificacion'); ?>:
                        <strong><?php echo inf_money(isset($item['total']) ? $item['total'] : 0); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="card">
        <span class="title-chip"><i class="bi bi-clipboard2-data"></i> Lectura ejecutiva</span>
        <h2>KPIs recomendados para control profesional</h2>
        <ul class="compact-list">
            <li>Margen operativo y variacion mensual de balance.</li>
            <li>Ratio gasto/ingreso y costo/ingreso por periodo.</li>
            <li>Cuentas por cobrar y por pagar con tendencia.</li>
            <li>Top clasificaciones por valor acumulado.</li>
            <li>Trazabilidad por origen: movimientos vs ingresos legacy.</li>
        </ul>
    </article>
</section>

<section class="card table-card">
    <div class="table-header">
        <h2><i class="bi bi-table"></i> Detalle de registros</h2>
        <span class="muted"><?php echo count($detailRows); ?> registros</span>
    </div>
    <div class="table-wrapper">
        <table class="table-professional js-data-table js-indexed-table" data-page-length="20" data-export-name="informes_kpis_detalle" data-preference-key="informes_table_length">
            <thead>
            <tr>
                <th class="no-export">#</th>
                <th>Origen</th>
                <th>ID</th>
                <th>Fecha</th>
                <th>Clasificacion</th>
                <th>Detalle</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Usuario</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($detailRows)) : ?>
                <tr>
                    <td colspan="10" class="muted">No hay registros para los filtros aplicados.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($detailRows as $row) : ?>
                    <tr>
                        <td></td>
                        <td><?php echo inf_escape(isset($row['origen']) ? $row['origen'] : ''); ?></td>
                        <td><?php echo (int) (isset($row['registro_id']) ? $row['registro_id'] : 0); ?></td>
                        <td><?php echo inf_escape(isset($row['fecha']) ? $row['fecha'] : ''); ?></td>
                        <td><?php echo inf_escape(isset($row['clasificacion']) ? $row['clasificacion'] : ''); ?></td>
                        <td><?php echo inf_escape(isset($row['detalle']) ? $row['detalle'] : ''); ?></td>
                        <td><?php echo inf_escape(isset($row['categoria']) ? $row['categoria'] : ''); ?></td>
                        <td><?php echo inf_escape(isset($row['tipo']) ? $row['tipo'] : ''); ?></td>
                        <td><?php echo inf_money(isset($row['valor']) ? $row['valor'] : 0); ?></td>
                        <td><?php echo inf_escape(isset($row['usuario']) ? $row['usuario'] : ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script id="informes-chart-data" type="application/json"><?php echo json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
