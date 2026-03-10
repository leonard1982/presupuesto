<?php
/**
 * Proyecto PRESUPUESTO - Vista de informes corporativos A4.
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

if (!function_exists('inf_status_class')) {
    function inf_status_class($status)
    {
        $label = trim((string) $status);
        if ($label === 'Optimo') {
            return 'report-badge report-badge-good';
        }
        if ($label === 'Control') {
            return 'report-badge report-badge-warn';
        }
        return 'report-badge report-badge-risk';
    }
}

$reports = isset($corporateReports) && is_array($corporateReports) ? $corporateReports : array();
$reportOne = isset($reports['report_1']) && is_array($reports['report_1']) ? $reports['report_1'] : array();
$reportTwo = isset($reports['report_2']) && is_array($reports['report_2']) ? $reports['report_2'] : array();
$reportThree = isset($reports['report_3']) && is_array($reports['report_3']) ? $reports['report_3'] : array();
$reportFour = isset($reports['report_4']) && is_array($reports['report_4']) ? $reports['report_4'] : array();
$reportFive = isset($reports['report_5']) && is_array($reports['report_5']) ? $reports['report_5'] : array();
$reportPeriod = isset($reports['period_label']) ? (string) $reports['period_label'] : (isset($periodLabel) ? (string) $periodLabel : '');
?>

<section class="page-header card">
    <div>
        <span class="title-chip"><i class="bi bi-journal-text"></i> Reporteria corporativa</span>
        <h2>Informes Ejecutivos</h2>
        <p class="muted">Cinco informes tipo hoja A4 para control financiero y operativo.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-inline" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimir A4
    </button>
</section>

<section class="card movement-filters-card report-filters-card">
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
            <div class="report-nav">
                <a href="#reporte-1" class="btn btn-ghost btn-inline btn-mini"><i class="bi bi-file-earmark-text"></i> R1</a>
                <a href="#reporte-2" class="btn btn-ghost btn-inline btn-mini"><i class="bi bi-speedometer2"></i> R2</a>
                <a href="#reporte-3" class="btn btn-ghost btn-inline btn-mini"><i class="bi bi-cash-coin"></i> R3</a>
                <a href="#reporte-4" class="btn btn-ghost btn-inline btn-mini"><i class="bi bi-pie-chart"></i> R4</a>
                <a href="#reporte-5" class="btn btn-ghost btn-inline btn-mini"><i class="bi bi-journal-richtext"></i> R5</a>
            </div>
        </div>
    </form>
</section>

<section class="corporate-reports-grid">
    <article id="reporte-1" class="report-sheet card">
        <header class="report-sheet-header">
            <div>
                <h3><i class="bi bi-file-earmark-ruled"></i> <?php echo inf_escape(isset($reportOne['title']) ? $reportOne['title'] : 'Informe 1'); ?></h3>
                <p class="muted"><?php echo inf_escape(isset($reportOne['subtitle']) ? $reportOne['subtitle'] : ''); ?></p>
            </div>
            <span class="report-period"><i class="bi bi-calendar-range"></i> <?php echo inf_escape($reportPeriod); ?></span>
        </header>
        <div class="table-wrapper">
            <table class="table-professional report-table-static">
                <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Valor</th>
                </tr>
                </thead>
                <tbody>
                <?php $reportOneRows = isset($reportOne['rows']) && is_array($reportOne['rows']) ? $reportOne['rows'] : array(); ?>
                <?php foreach ($reportOneRows as $row) : ?>
                    <tr>
                        <td><?php echo inf_escape(isset($row['concepto']) ? $row['concepto'] : ''); ?></td>
                        <td>
                            <?php if (isset($row['valor_percent'])) : ?>
                                <?php echo inf_percent($row['valor_percent']); ?>
                            <?php else : ?>
                                <?php echo inf_money(isset($row['valor']) ? $row['valor'] : 0); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <footer class="report-note">
            <strong>Dictamen:</strong> <?php echo inf_escape(isset($reportOne['highlight']) ? $reportOne['highlight'] : ''); ?>
        </footer>
    </article>

    <article id="reporte-2" class="report-sheet card">
        <header class="report-sheet-header">
            <div>
                <h3><i class="bi bi-graph-up-arrow"></i> <?php echo inf_escape(isset($reportTwo['title']) ? $reportTwo['title'] : 'Informe 2'); ?></h3>
                <p class="muted"><?php echo inf_escape(isset($reportTwo['subtitle']) ? $reportTwo['subtitle'] : ''); ?></p>
            </div>
            <span class="report-period"><i class="bi bi-calendar-range"></i> <?php echo inf_escape($reportPeriod); ?></span>
        </header>
        <div class="table-wrapper">
            <table class="table-professional report-table-static">
                <thead>
                <tr>
                    <th>Indicador</th>
                    <th>Valor</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php $reportTwoRows = isset($reportTwo['rows']) && is_array($reportTwo['rows']) ? $reportTwo['rows'] : array(); ?>
                <?php foreach ($reportTwoRows as $row) : ?>
                    <?php $status = isset($row['estado']) ? (string) $row['estado'] : 'Riesgo'; ?>
                    <tr>
                        <td><?php echo inf_escape(isset($row['indicador']) ? $row['indicador'] : ''); ?></td>
                        <td><?php echo inf_percent(isset($row['valor']) ? $row['valor'] : 0); ?></td>
                        <td><span class="<?php echo inf_escape(inf_status_class($status)); ?>"><?php echo inf_escape($status); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article id="reporte-3" class="report-sheet card">
        <header class="report-sheet-header">
            <div>
                <h3><i class="bi bi-wallet2"></i> <?php echo inf_escape(isset($reportThree['title']) ? $reportThree['title'] : 'Informe 3'); ?></h3>
                <p class="muted"><?php echo inf_escape(isset($reportThree['subtitle']) ? $reportThree['subtitle'] : ''); ?></p>
            </div>
            <span class="report-period"><i class="bi bi-calendar-range"></i> <?php echo inf_escape($reportPeriod); ?></span>
        </header>
        <div class="table-wrapper">
            <table class="table-professional report-table-static">
                <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Valor</th>
                </tr>
                </thead>
                <tbody>
                <?php $reportThreeRows = isset($reportThree['rows']) && is_array($reportThree['rows']) ? $reportThree['rows'] : array(); ?>
                <?php foreach ($reportThreeRows as $row) : ?>
                    <tr>
                        <td><?php echo inf_escape(isset($row['concepto']) ? $row['concepto'] : ''); ?></td>
                        <td>
                            <?php if (isset($row['valor_percent'])) : ?>
                                <?php echo inf_percent($row['valor_percent']); ?>
                            <?php else : ?>
                                <?php echo inf_money(isset($row['valor']) ? $row['valor'] : 0); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <footer class="report-note">
            <strong>Lectura:</strong> <?php echo inf_escape(isset($reportThree['highlight']) ? $reportThree['highlight'] : ''); ?>
        </footer>
    </article>

    <article id="reporte-4" class="report-sheet card">
        <header class="report-sheet-header">
            <div>
                <h3><i class="bi bi-pie-chart-fill"></i> <?php echo inf_escape(isset($reportFour['title']) ? $reportFour['title'] : 'Informe 4'); ?></h3>
                <p class="muted"><?php echo inf_escape(isset($reportFour['subtitle']) ? $reportFour['subtitle'] : ''); ?></p>
            </div>
            <span class="report-period"><i class="bi bi-calendar-range"></i> <?php echo inf_escape($reportPeriod); ?></span>
        </header>
        <div class="report-split-grid">
            <div class="table-wrapper">
                <table class="table-professional report-table-static">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Clasificacion</th>
                        <th>Total</th>
                        <th>Participacion</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $reportFourClasif = isset($reportFour['clasificaciones']) && is_array($reportFour['clasificaciones']) ? $reportFour['clasificaciones'] : array(); ?>
                    <?php if (empty($reportFourClasif)) : ?>
                        <tr>
                            <td colspan="4" class="muted">No hay datos para mostrar.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($reportFourClasif as $row) : ?>
                            <tr>
                                <td><?php echo (int) (isset($row['orden']) ? $row['orden'] : 0); ?></td>
                                <td><?php echo inf_escape(isset($row['clasificacion']) ? $row['clasificacion'] : ''); ?></td>
                                <td><?php echo inf_money(isset($row['total']) ? $row['total'] : 0); ?></td>
                                <td><?php echo inf_percent(isset($row['participacion']) ? $row['participacion'] : 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-wrapper">
                <table class="table-professional report-table-static">
                    <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Total</th>
                        <th>Participacion</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $reportFourCategorias = isset($reportFour['categorias']) && is_array($reportFour['categorias']) ? $reportFour['categorias'] : array(); ?>
                    <?php if (empty($reportFourCategorias)) : ?>
                        <tr>
                            <td colspan="3" class="muted">No hay datos para mostrar.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($reportFourCategorias as $row) : ?>
                            <tr>
                                <td><?php echo inf_escape(isset($row['categoria']) ? $row['categoria'] : ''); ?></td>
                                <td><?php echo inf_money(isset($row['total']) ? $row['total'] : 0); ?></td>
                                <td><?php echo inf_percent(isset($row['participacion']) ? $row['participacion'] : 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <article id="reporte-5" class="report-sheet card">
        <header class="report-sheet-header">
            <div>
                <h3><i class="bi bi-journal-check"></i> <?php echo inf_escape(isset($reportFive['title']) ? $reportFive['title'] : 'Informe 5'); ?></h3>
                <p class="muted"><?php echo inf_escape(isset($reportFive['subtitle']) ? $reportFive['subtitle'] : ''); ?></p>
            </div>
            <span class="report-period"><i class="bi bi-calendar-range"></i> <?php echo inf_escape($reportPeriod); ?></span>
        </header>

        <div class="report-source-summary">
            <?php $sourceSummary = isset($reportFive['source_summary']) && is_array($reportFive['source_summary']) ? $reportFive['source_summary'] : array(); ?>
            <?php foreach ($sourceSummary as $sourceLabel => $sourceCount) : ?>
                <span class="report-source-chip">
                    <i class="bi bi-dot"></i>
                    <?php echo inf_escape($sourceLabel); ?>: <?php echo (int) $sourceCount; ?>
                </span>
            <?php endforeach; ?>
            <span class="report-source-chip report-source-chip-total">
                <i class="bi bi-collection"></i>
                Total registros: <?php echo (int) (isset($reportFive['total_registros']) ? $reportFive['total_registros'] : 0); ?>
            </span>
        </div>

        <div class="table-wrapper">
            <table class="table-professional report-table-static">
                <thead>
                <tr>
                    <th>Periodo</th>
                    <th>Ingresos</th>
                    <th>Gastos</th>
                    <th>Costos</th>
                    <th>Balance</th>
                </tr>
                </thead>
                <tbody>
                <?php $trendRows = isset($reportFive['trend_rows']) && is_array($reportFive['trend_rows']) ? $reportFive['trend_rows'] : array(); ?>
                <?php if (empty($trendRows)) : ?>
                    <tr>
                        <td colspan="5" class="muted">Sin tendencia para el periodo filtrado.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($trendRows as $row) : ?>
                        <tr>
                            <td><?php echo inf_escape(isset($row['periodo']) ? $row['periodo'] : ''); ?></td>
                            <td><?php echo inf_money(isset($row['ingresos']) ? $row['ingresos'] : 0); ?></td>
                            <td><?php echo inf_money(isset($row['gastos']) ? $row['gastos'] : 0); ?></td>
                            <td><?php echo inf_money(isset($row['costos']) ? $row['costos'] : 0); ?></td>
                            <td><?php echo inf_money(isset($row['balance']) ? $row['balance'] : 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-wrapper">
            <table class="table-professional js-data-table js-indexed-table" data-page-length="20" data-export-name="informe_5_trazabilidad_operacional" data-preference-key="informes_table_length">
                <thead>
                <tr>
                    <th class="no-export">#</th>
                    <th>Origen</th>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Clasificacion</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Usuario</th>
                </tr>
                </thead>
                <tbody>
                <?php $operationalRows = isset($reportFive['operational_rows']) && is_array($reportFive['operational_rows']) ? $reportFive['operational_rows'] : array(); ?>
                <?php if (empty($operationalRows)) : ?>
                    <tr>
                        <td colspan="9" class="muted">No hay registros para el periodo seleccionado.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($operationalRows as $row) : ?>
                        <tr>
                            <td></td>
                            <td><?php echo inf_escape(isset($row['origen']) ? $row['origen'] : ''); ?></td>
                            <td><?php echo (int) (isset($row['registro_id']) ? $row['registro_id'] : 0); ?></td>
                            <td><?php echo inf_escape(isset($row['fecha']) ? $row['fecha'] : ''); ?></td>
                            <td><?php echo inf_escape(isset($row['clasificacion']) ? $row['clasificacion'] : ''); ?></td>
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
    </article>
</section>
