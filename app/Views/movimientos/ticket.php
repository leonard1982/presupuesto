<?php
/**
 * Proyecto PRESUPUESTO - Ticket visual de movimiento en formato media hoja.
 */

if (!function_exists('ticket_escape')) {
    function ticket_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ticket_money')) {
    function ticket_money($value)
    {
        return '$ ' . number_format((float) $value, 0, ',', '.');
    }
}

$movementId = isset($movement['id']) ? (int) $movement['id'] : 0;
$movementDate = isset($movement['fecha']) ? (string) $movement['fecha'] : '';
$movementClass = isset($movement['clasificacion']) && $movement['clasificacion'] !== null ? (string) $movement['clasificacion'] : 'Sin clasificacion';
$movementDetail = isset($movement['detalle']) ? (string) $movement['detalle'] : '';
$movementCategory = isset($movement['gasto_costo']) ? (string) $movement['gasto_costo'] : '';
$movementType = isset($movement['tipo']) ? (string) $movement['tipo'] : '';
$movementValue = isset($movement['valor']) ? (float) $movement['valor'] : 0;
$movementNet = isset($movement['valor_neto']) ? (float) $movement['valor_neto'] : 0;
$movementSaldo = isset($movement['saldo']) ? (float) $movement['saldo'] : 0;
$movementEstado = isset($movement['por_pagar_cobrar']) ? (string) $movement['por_pagar_cobrar'] : 'NINGUNO';
$movementUser = isset($movement['usuario']) ? (string) $movement['usuario'] : '';
?>
<section class="ticket-sheet">
    <header class="ticket-header">
        <div class="ticket-brand">
            <span class="ticket-brand-icon"><i class="bi bi-receipt"></i></span>
            <div>
                <h1>Ticket de movimiento</h1>
                <p class="muted">Comprobante de registro operativo</p>
            </div>
        </div>
        <div class="ticket-header-actions no-print">
            <button type="button" class="btn btn-secondary btn-inline" onclick="window.print();">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a class="btn btn-ghost btn-inline" href="<?php echo ticket_escape($baseUrl); ?>/index.php?route=movimientos" target="_self">
                <i class="bi bi-arrow-left-circle"></i> Volver
            </a>
        </div>
    </header>

    <div class="ticket-code-row">
        <span class="ticket-code">Movimiento #<?php echo $movementId; ?></span>
        <span class="ticket-date"><i class="bi bi-calendar3"></i> <?php echo ticket_escape($movementDate); ?></span>
    </div>

    <div class="ticket-grid">
        <article class="ticket-item">
            <span>Clasificacion</span>
            <strong><?php echo ticket_escape($movementClass); ?></strong>
        </article>
        <article class="ticket-item">
            <span>Categoria</span>
            <strong><?php echo ticket_escape($movementCategory); ?></strong>
        </article>
        <article class="ticket-item">
            <span>Tipo / Medio</span>
            <strong><?php echo ticket_escape($movementType); ?></strong>
        </article>
        <article class="ticket-item">
            <span>Estado saldo</span>
            <strong><?php echo ticket_escape($movementEstado); ?></strong>
        </article>
        <article class="ticket-item ticket-item-wide">
            <span>Detalle</span>
            <strong><?php echo ticket_escape($movementDetail); ?></strong>
        </article>
    </div>

    <div class="ticket-values">
        <article class="ticket-value-card">
            <span>Valor</span>
            <strong><?php echo ticket_money($movementValue); ?></strong>
        </article>
        <article class="ticket-value-card">
            <span>Valor neto</span>
            <strong><?php echo ticket_money($movementNet); ?></strong>
        </article>
        <article class="ticket-value-card">
            <span>Saldo</span>
            <strong><?php echo ticket_money($movementSaldo); ?></strong>
        </article>
    </div>

    <section class="ticket-supports">
        <h2><i class="bi bi-paperclip"></i> Soportes</h2>
        <?php if (empty($supports)) : ?>
            <p class="muted">Sin soportes adjuntos en este movimiento.</p>
        <?php else : ?>
            <ul class="ticket-support-list">
                <?php foreach ($supports as $support) : ?>
                    <?php
                    $supportId = isset($support['support_id']) ? (int) $support['support_id'] : 0;
                    $supportName = isset($support['original_name']) ? (string) $support['original_name'] : '';
                    $supportUrl = ticket_escape($baseUrl) . '/index.php?route=movimientos/soporte&id=' . $movementId . '&sid=' . $supportId;
                    ?>
                    <?php if ($supportId > 0) : ?>
                        <li class="ticket-support-item">
                            <span class="ticket-support-name">
                                <i class="bi bi-file-earmark"></i>
                                <?php echo ticket_escape($supportName); ?>
                            </span>
                            <span class="ticket-support-actions">
                                <a class="btn btn-ghost btn-inline btn-mini btn-icon-only" href="<?php echo $supportUrl; ?>" target="_blank" title="Ver soporte">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a class="btn btn-ghost btn-inline btn-mini btn-icon-only" href="<?php echo $supportUrl; ?>" target="_blank" download title="Descargar soporte">
                                    <i class="bi bi-download"></i>
                                </a>
                            </span>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <footer class="ticket-footer">
        <span><i class="bi bi-person-circle"></i> Registrado por: <?php echo ticket_escape($movementUser); ?></span>
        <span><i class="bi bi-clock-history"></i> Generado: <?php echo ticket_escape($generatedAt); ?> por <?php echo ticket_escape($generatedBy); ?></span>
    </footer>
</section>
