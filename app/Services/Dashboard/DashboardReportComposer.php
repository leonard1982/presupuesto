<?php
/**
 * Proyecto PRESUPUESTO - Compositor de informe visual para correo.
 */

namespace App\Services\Dashboard;

class DashboardReportComposer
{
    public function compose(array $snapshot, array $options = array())
    {
        $periodLabel = isset($snapshot['period_label']) ? (string) $snapshot['period_label'] : 'Periodo actual';
        $generatedAt = isset($snapshot['generated_at']) ? (string) $snapshot['generated_at'] : date('Y-m-d H:i:s');
        $totals = isset($snapshot['monthly_totals']) && is_array($snapshot['monthly_totals']) ? $snapshot['monthly_totals'] : array();
        $balance = isset($snapshot['balance']) ? (float) $snapshot['balance'] : 0.0;
        $top = isset($snapshot['top_clasificaciones']) && is_array($snapshot['top_clasificaciones']) ? $snapshot['top_clasificaciones'] : array();
        $recent = isset($snapshot['recent_movements']) && is_array($snapshot['recent_movements']) ? $snapshot['recent_movements'] : array();

        $subject = isset($options['subject']) ? trim((string) $options['subject']) : '';
        if ($subject === '') {
            $subject = 'Informe financiero - ' . $periodLabel;
        }

        $ingresos = isset($totals['ingresos']) ? (float) $totals['ingresos'] : 0.0;
        $gastos = isset($totals['gastos']) ? (float) $totals['gastos'] : 0.0;
        $costos = isset($totals['costos']) ? (float) $totals['costos'] : 0.0;

        $html = $this->buildHtml($periodLabel, $generatedAt, $ingresos, $gastos, $costos, $balance, $top, $recent);
        $text = $this->buildText($periodLabel, $generatedAt, $ingresos, $gastos, $costos, $balance, $top);

        return array(
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        );
    }

    private function buildHtml($periodLabel, $generatedAt, $ingresos, $gastos, $costos, $balance, array $topClasificaciones, array $recentMovements)
    {
        $topRows = '';
        if (empty($topClasificaciones)) {
            $topRows = '<tr><td colspan="2" style="padding:10px;color:#4b5563;">Sin clasificaciones para este periodo.</td></tr>';
        } else {
            foreach ($topClasificaciones as $item) {
                $name = isset($item['clasificacion']) ? $this->escapeHtml($item['clasificacion']) : 'Sin clasificacion';
                $total = isset($item['total']) ? (float) $item['total'] : 0.0;
                $topRows .= '<tr><td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . $name . '</td><td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:right;">' . $this->money($total) . '</td></tr>';
            }
        }

        $recentRows = '';
        $maxRecent = 8;
        $counter = 0;
        foreach ($recentMovements as $movement) {
            if ($counter >= $maxRecent) {
                break;
            }

            $counter += 1;
            $fecha = isset($movement['fecha']) ? $this->escapeHtml($movement['fecha']) : '';
            $detalle = isset($movement['detalle']) ? $this->escapeHtml($movement['detalle']) : '';
            $categoria = isset($movement['gasto_costo']) ? $this->escapeHtml($movement['gasto_costo']) : '';
            $valor = isset($movement['valor']) ? (float) $movement['valor'] : 0.0;

            $recentRows .= '<tr>';
            $recentRows .= '<td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . $fecha . '</td>';
            $recentRows .= '<td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . $detalle . '</td>';
            $recentRows .= '<td style="padding:8px;border-bottom:1px solid #e2e8f0;">' . $categoria . '</td>';
            $recentRows .= '<td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:right;">' . $this->money($valor) . '</td>';
            $recentRows .= '</tr>';
        }

        if ($recentRows === '') {
            $recentRows = '<tr><td colspan="4" style="padding:10px;color:#4b5563;">Sin movimientos recientes.</td></tr>';
        }

        $balanceColor = $balance >= 0 ? '#0f6b3a' : '#b42318';

        return '<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Informe PRESUPUESTO</title>
</head>
<body style="margin:0;background:#edf2f9;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
  <div style="max-width:900px;margin:0 auto;padding:20px 14px;">
    <div style="background:#0f4c81;color:#ffffff;border-radius:14px;padding:18px 20px;">
      <h1 style="margin:0;font-size:22px;">Informe financiero</h1>
      <p style="margin:8px 0 0;font-size:14px;opacity:0.95;">Periodo: ' . $this->escapeHtml($periodLabel) . ' | Generado: ' . $this->escapeHtml($generatedAt) . '</p>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:14px;">
      <div style="flex:1 1 200px;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;padding:12px;">
        <div style="font-size:12px;color:#405b7a;">Ingresos</div>
        <div style="margin-top:4px;font-size:21px;font-weight:700;">' . $this->money($ingresos) . '</div>
      </div>
      <div style="flex:1 1 200px;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;padding:12px;">
        <div style="font-size:12px;color:#405b7a;">Gastos</div>
        <div style="margin-top:4px;font-size:21px;font-weight:700;">' . $this->money($gastos) . '</div>
      </div>
      <div style="flex:1 1 200px;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;padding:12px;">
        <div style="font-size:12px;color:#405b7a;">Costos</div>
        <div style="margin-top:4px;font-size:21px;font-weight:700;">' . $this->money($costos) . '</div>
      </div>
      <div style="flex:1 1 200px;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;padding:12px;">
        <div style="font-size:12px;color:#405b7a;">Balance</div>
        <div style="margin-top:4px;font-size:21px;font-weight:700;color:' . $balanceColor . ';">' . $this->money($balance) . '</div>
      </div>
    </div>

    <div style="margin-top:14px;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;padding:14px;">
      <h2 style="margin:0 0 8px;font-size:17px;color:#153f67;">Clasificaciones con mayor valor</h2>
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead>
          <tr>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #d0dbe8;">Clasificacion</th>
            <th style="text-align:right;padding:8px;border-bottom:1px solid #d0dbe8;">Total</th>
          </tr>
        </thead>
        <tbody>' . $topRows . '</tbody>
      </table>
    </div>

    <div style="margin-top:14px;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;padding:14px;">
      <h2 style="margin:0 0 8px;font-size:17px;color:#153f67;">Movimientos recientes</h2>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #d0dbe8;">Fecha</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #d0dbe8;">Detalle</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #d0dbe8;">Categoria</th>
            <th style="text-align:right;padding:8px;border-bottom:1px solid #d0dbe8;">Valor</th>
          </tr>
        </thead>
        <tbody>' . $recentRows . '</tbody>
      </table>
    </div>
  </div>
</body>
</html>';
    }

    private function buildText($periodLabel, $generatedAt, $ingresos, $gastos, $costos, $balance, array $topClasificaciones)
    {
        $lines = array();
        $lines[] = 'Informe financiero PRESUPUESTO';
        $lines[] = 'Periodo: ' . $periodLabel;
        $lines[] = 'Generado: ' . $generatedAt;
        $lines[] = '';
        $lines[] = 'Ingresos: ' . $this->money($ingresos);
        $lines[] = 'Gastos: ' . $this->money($gastos);
        $lines[] = 'Costos: ' . $this->money($costos);
        $lines[] = 'Balance: ' . $this->money($balance);
        $lines[] = '';
        $lines[] = 'Top clasificaciones:';

        if (empty($topClasificaciones)) {
            $lines[] = '- Sin clasificaciones para este periodo.';
        } else {
            foreach ($topClasificaciones as $item) {
                $name = isset($item['clasificacion']) ? (string) $item['clasificacion'] : 'Sin clasificacion';
                $total = isset($item['total']) ? (float) $item['total'] : 0.0;
                $lines[] = '- ' . $name . ': ' . $this->money($total);
            }
        }

        return implode("\n", $lines);
    }

    private function money($amount)
    {
        return '$ ' . number_format((float) $amount, 0, ',', '.');
    }

    private function escapeHtml($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
