<?php
/**
 * Proyecto PRESUPUESTO - Repositorio de informes y KPIs ejecutivos.
 */

namespace App\Modules\Informes\Repositories;

use App\Core\Logger;
use mysqli;
use mysqli_stmt;

class InformeRepository
{
    private $connection;
    private $logger;

    public function __construct(mysqli $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getClasificaciones()
    {
        $rows = array();
        $query = 'SELECT id, descripcion FROM clasificaciones ORDER BY descripcion ASC';
        $result = $this->connection->query($query);
        if ($result === false) {
            $this->logger->warning('app', 'No fue posible listar clasificaciones para informes.', array(
                'error' => $this->connection->error,
            ));
            return $rows;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getTiposDisponibles($dateFrom, $dateTo)
    {
        $typesMap = array();
        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;

        $queryMovimientos = "SELECT DISTINCT tipo
                             FROM gastos_costos
                             WHERE fecha_periodo >= ? AND fecha_periodo <= ? AND tipo <> ''
                             ORDER BY tipo ASC";
        $statement = $this->connection->prepare($queryMovimientos);
        if ($statement instanceof mysqli_stmt) {
            $statement->bind_param('ss', $fromDate, $toDate);
            $statement->execute();
            $result = $statement->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $label = isset($row['tipo']) ? trim((string) $row['tipo']) : '';
                if ($label !== '') {
                    $typesMap[$this->normalizeKey($label)] = $label;
                }
            }
            $statement->close();
        }

        $queryIngresos = "SELECT DISTINCT tipo
                          FROM ingresos
                          WHERE fecha_periodo >= ? AND fecha_periodo <= ? AND tipo <> ''
                          ORDER BY tipo ASC";
        $statement = $this->connection->prepare($queryIngresos);
        if ($statement instanceof mysqli_stmt) {
            $statement->bind_param('ss', $fromDate, $toDate);
            $statement->execute();
            $result = $statement->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $label = isset($row['tipo']) ? trim((string) $row['tipo']) : '';
                if ($label !== '') {
                    $typesMap[$this->normalizeKey($label)] = $label;
                }
            }
            $statement->close();
        }

        if (empty($typesMap)) {
            return array();
        }

        asort($typesMap, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($typesMap);
    }

    public function getKpiSummary($dateFrom, $dateTo)
    {
        $movimientos = $this->getMovimientosTotals($dateFrom, $dateTo);
        $ingresosLegacy = $this->sumIngresosLegacy($dateFrom, $dateTo);

        $ingresosMovimientos = isset($movimientos['Ingreso']) ? (float) $movimientos['Ingreso'] : 0.0;
        $gastos = isset($movimientos['Gasto']) ? (float) $movimientos['Gasto'] : 0.0;
        $costos = isset($movimientos['Costo']) ? (float) $movimientos['Costo'] : 0.0;
        $ingresosTotales = $ingresosLegacy + $ingresosMovimientos;
        $egresosTotales = $gastos + $costos;
        $balance = $ingresosTotales - $egresosTotales;
        $margenOperativo = $ingresosTotales > 0 ? (($balance / $ingresosTotales) * 100) : 0.0;

        $cuentas = $this->getAccountsSummary($dateFrom, $dateTo);
        $cuentasPagar = isset($cuentas['PAGAR']) ? (float) $cuentas['PAGAR'] : 0.0;
        $cuentasCobrar = isset($cuentas['COBRAR']) ? (float) $cuentas['COBRAR'] : 0.0;

        return array(
            'ingresos_total' => $ingresosTotales,
            'ingresos_legacy' => $ingresosLegacy,
            'ingresos_movimientos' => $ingresosMovimientos,
            'gastos_total' => $gastos,
            'costos_total' => $costos,
            'egresos_total' => $egresosTotales,
            'balance_neto' => $balance,
            'margen_operativo' => $margenOperativo,
            'cuentas_por_pagar' => $cuentasPagar,
            'cuentas_por_cobrar' => $cuentasCobrar,
            'ratio_gasto_ingreso' => $ingresosTotales > 0 ? (($gastos / $ingresosTotales) * 100) : 0.0,
            'ratio_costo_ingreso' => $ingresosTotales > 0 ? (($costos / $ingresosTotales) * 100) : 0.0,
        );
    }

    public function getCategoriaBreakdown($dateFrom, $dateTo)
    {
        $rows = array(
            array('categoria' => 'Ingreso', 'total' => 0.0),
            array('categoria' => 'Gasto', 'total' => 0.0),
            array('categoria' => 'Costo', 'total' => 0.0),
        );

        $kpis = $this->getKpiSummary($dateFrom, $dateTo);
        $rows[0]['total'] = isset($kpis['ingresos_total']) ? (float) $kpis['ingresos_total'] : 0.0;
        $rows[1]['total'] = isset($kpis['gastos_total']) ? (float) $kpis['gastos_total'] : 0.0;
        $rows[2]['total'] = isset($kpis['costos_total']) ? (float) $kpis['costos_total'] : 0.0;

        return $rows;
    }

    public function getClasificacionBreakdown($dateFrom, $dateTo, $limit)
    {
        $limitInt = (int) $limit;
        if ($limitInt < 1) {
            $limitInt = 10;
        }

        $totalsMap = array();
        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;

        $queryMovimientos = 'SELECT c.descripcion AS clasificacion, SUM(gc.valor) AS total
                             FROM gastos_costos gc
                             INNER JOIN clasificaciones c ON c.id = gc.id_clasificacion
                             WHERE gc.fecha_periodo >= ? AND gc.fecha_periodo <= ?
                             GROUP BY gc.id_clasificacion, c.descripcion';
        $statement = $this->connection->prepare($queryMovimientos);
        if ($statement instanceof mysqli_stmt) {
            $statement->bind_param('ss', $fromDate, $toDate);
            $statement->execute();
            $result = $statement->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $label = isset($row['clasificacion']) ? (string) $row['clasificacion'] : 'Sin clasificacion';
                $key = $this->normalizeKey($label);
                if (!isset($totalsMap[$key])) {
                    $totalsMap[$key] = array('clasificacion' => $label, 'total' => 0.0);
                }
                $totalsMap[$key]['total'] += isset($row['total']) ? (float) $row['total'] : 0.0;
            }
            $statement->close();
        }

        $queryIngresos = 'SELECT c.descripcion AS clasificacion, SUM(i.valor) AS total
                          FROM ingresos i
                          INNER JOIN clasificaciones c ON c.id = i.id_clasificacion
                          WHERE i.fecha_periodo >= ? AND i.fecha_periodo <= ?
                          GROUP BY i.id_clasificacion, c.descripcion';
        $statement = $this->connection->prepare($queryIngresos);
        if ($statement instanceof mysqli_stmt) {
            $statement->bind_param('ss', $fromDate, $toDate);
            $statement->execute();
            $result = $statement->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $label = isset($row['clasificacion']) ? (string) $row['clasificacion'] : 'Sin clasificacion';
                $key = $this->normalizeKey($label);
                if (!isset($totalsMap[$key])) {
                    $totalsMap[$key] = array('clasificacion' => $label, 'total' => 0.0);
                }
                $totalsMap[$key]['total'] += isset($row['total']) ? (float) $row['total'] : 0.0;
            }
            $statement->close();
        }

        if (empty($totalsMap)) {
            return array();
        }

        $rows = array_values($totalsMap);
        usort($rows, array($this, 'sortTotalsDesc'));
        if (count($rows) > $limitInt) {
            $rows = array_slice($rows, 0, $limitInt);
        }

        return $rows;
    }

    public function getMonthlyTrend($dateFrom, $dateTo)
    {
        $months = $this->buildMonthKeys($dateFrom, $dateTo);
        if (empty($months)) {
            return array(
                'labels' => array(),
                'ingresos' => array(),
                'gastos' => array(),
                'costos' => array(),
                'balance' => array(),
            );
        }

        $ingresosLegacyMap = $this->sumIngresosLegacyByMonth($dateFrom, $dateTo);
        $movimientosMap = $this->sumMovimientosByMonthAndCategory($dateFrom, $dateTo);

        $labels = array();
        $ingresos = array();
        $gastos = array();
        $costos = array();
        $balance = array();

        foreach ($months as $monthKey) {
            $labels[] = $this->formatMonthLabel($monthKey);

            $legacyIncome = isset($ingresosLegacyMap[$monthKey]) ? (float) $ingresosLegacyMap[$monthKey] : 0.0;
            $movIncome = isset($movimientosMap[$monthKey]['Ingreso']) ? (float) $movimientosMap[$monthKey]['Ingreso'] : 0.0;
            $movGasto = isset($movimientosMap[$monthKey]['Gasto']) ? (float) $movimientosMap[$monthKey]['Gasto'] : 0.0;
            $movCosto = isset($movimientosMap[$monthKey]['Costo']) ? (float) $movimientosMap[$monthKey]['Costo'] : 0.0;

            $incomeTotal = $legacyIncome + $movIncome;
            $balanceValue = $incomeTotal - ($movGasto + $movCosto);

            $ingresos[] = $incomeTotal;
            $gastos[] = $movGasto;
            $costos[] = $movCosto;
            $balance[] = $balanceValue;
        }

        return array(
            'labels' => $labels,
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'costos' => $costos,
            'balance' => $balance,
        );
    }

    public function getDetailedRows($dateFrom, $dateTo, $categoria, $clasificacionId, $tipo)
    {
        $rows = array();
        $categoriaFilter = trim((string) $categoria);
        $clasificacionFilter = (int) $clasificacionId;
        $tipoFilter = trim((string) $tipo);

        $movimientosRows = $this->fetchDetailedMovimientosRows($dateFrom, $dateTo, $categoriaFilter, $clasificacionFilter, $tipoFilter);
        foreach ($movimientosRows as $row) {
            $rows[] = $row;
        }

        if ($categoriaFilter === '' || $categoriaFilter === 'Ingreso') {
            $ingresosRows = $this->fetchDetailedIngresosRows($dateFrom, $dateTo, $clasificacionFilter, $tipoFilter);
            foreach ($ingresosRows as $row) {
                $rows[] = $row;
            }
        }

        usort($rows, array($this, 'sortRowsByDateDesc'));
        return $rows;
    }

    private function getMovimientosTotals($dateFrom, $dateTo)
    {
        $query = 'SELECT gasto_costo, COALESCE(SUM(valor), 0) AS total
                  FROM gastos_costos
                  WHERE fecha_periodo >= ? AND fecha_periodo <= ?
                  GROUP BY gasto_costo';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar totales de movimientos para informes.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;
        $statement->bind_param('ss', $fromDate, $toDate);
        $statement->execute();
        $result = $statement->get_result();

        $map = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $categoria = isset($row['gasto_costo']) ? trim((string) $row['gasto_costo']) : '';
            if ($categoria === '') {
                continue;
            }
            $map[$categoria] = isset($row['total']) ? (float) $row['total'] : 0.0;
        }

        $statement->close();
        return $map;
    }

    private function sumIngresosLegacy($dateFrom, $dateTo)
    {
        $query = 'SELECT COALESCE(SUM(valor), 0) AS total
                  FROM ingresos
                  WHERE fecha_periodo >= ? AND fecha_periodo <= ?';
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar suma de ingresos legacy para informes.', array(
                'error' => $this->connection->error,
            ));
            return 0.0;
        }

        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;
        $statement->bind_param('ss', $fromDate, $toDate);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ? (float) $row['total'] : 0.0;
    }

    private function getAccountsSummary($dateFrom, $dateTo)
    {
        $queryMovimientos = "SELECT por_pagar_cobrar AS estado, COALESCE(SUM(saldo), 0) AS total
                             FROM gastos_costos
                             WHERE fecha_periodo >= ? AND fecha_periodo <= ? AND por_pagar_cobrar IN ('PAGAR','COBRAR')
                             GROUP BY por_pagar_cobrar";
        $queryIngresos = "SELECT por_cobrar_pagar AS estado, COALESCE(SUM(valor), 0) AS total
                          FROM ingresos
                          WHERE fecha_periodo >= ? AND fecha_periodo <= ? AND por_cobrar_pagar IN ('PAGAR','COBRAR')
                          GROUP BY por_cobrar_pagar";

        $map = array(
            'PAGAR' => 0.0,
            'COBRAR' => 0.0,
        );

        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;

        $statement = $this->connection->prepare($queryMovimientos);
        if ($statement instanceof mysqli_stmt) {
            $statement->bind_param('ss', $fromDate, $toDate);
            $statement->execute();
            $result = $statement->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $key = isset($row['estado']) ? trim((string) $row['estado']) : '';
                if ($key !== '' && isset($map[$key])) {
                    $map[$key] += isset($row['total']) ? (float) $row['total'] : 0.0;
                }
            }
            $statement->close();
        }

        $statement = $this->connection->prepare($queryIngresos);
        if ($statement instanceof mysqli_stmt) {
            $statement->bind_param('ss', $fromDate, $toDate);
            $statement->execute();
            $result = $statement->get_result();
            while ($result && ($row = $result->fetch_assoc())) {
                $key = isset($row['estado']) ? trim((string) $row['estado']) : '';
                if ($key !== '' && isset($map[$key])) {
                    $map[$key] += isset($row['total']) ? (float) $row['total'] : 0.0;
                }
            }
            $statement->close();
        }

        return $map;
    }

    private function fetchDetailedMovimientosRows($dateFrom, $dateTo, $categoriaFilter, $clasificacionFilter, $tipoFilter)
    {
        $query = 'SELECT gc.id, gc.fecha, gc.fecha_periodo, gc.id_clasificacion, c.descripcion AS clasificacion, gc.detalle, gc.valor, gc.gasto_costo, gc.tipo, gc.usuario
                  FROM gastos_costos gc
                  LEFT JOIN clasificaciones c ON c.id = gc.id_clasificacion
                  WHERE gc.fecha_periodo >= ? AND gc.fecha_periodo <= ?';

        $types = 'ss';
        $params = array((string) $dateFrom, (string) $dateTo);

        if ($categoriaFilter !== '') {
            $query .= ' AND gc.gasto_costo = ?';
            $types .= 's';
            $params[] = $categoriaFilter;
        }

        if ($clasificacionFilter > 0) {
            $query .= ' AND gc.id_clasificacion = ?';
            $types .= 'i';
            $params[] = $clasificacionFilter;
        }

        if ($tipoFilter !== '') {
            $query .= ' AND gc.tipo = ?';
            $types .= 's';
            $params[] = $tipoFilter;
        }

        $query .= ' ORDER BY gc.fecha DESC LIMIT 3000';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar detalle de movimientos para informes.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $this->bindDynamic($statement, $types, $params);
        $statement->execute();
        $result = $statement->get_result();

        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = array(
                'origen' => 'Movimientos',
                'registro_id' => isset($row['id']) ? (int) $row['id'] : 0,
                'fecha' => isset($row['fecha']) ? (string) $row['fecha'] : '',
                'fecha_periodo' => isset($row['fecha_periodo']) ? (string) $row['fecha_periodo'] : '',
                'clasificacion' => isset($row['clasificacion']) && $row['clasificacion'] !== null ? (string) $row['clasificacion'] : 'Sin clasificacion',
                'detalle' => isset($row['detalle']) ? (string) $row['detalle'] : '',
                'categoria' => isset($row['gasto_costo']) ? (string) $row['gasto_costo'] : '',
                'tipo' => isset($row['tipo']) ? (string) $row['tipo'] : '',
                'valor' => isset($row['valor']) ? (float) $row['valor'] : 0.0,
                'usuario' => isset($row['usuario']) ? (string) $row['usuario'] : '',
            );
        }

        $statement->close();
        return $rows;
    }

    private function fetchDetailedIngresosRows($dateFrom, $dateTo, $clasificacionFilter, $tipoFilter)
    {
        $query = 'SELECT i.id, i.fecha, i.fecha_periodo, i.id_clasificacion, c.descripcion AS clasificacion, i.detalle, i.valor, i.tipo
                  FROM ingresos i
                  LEFT JOIN clasificaciones c ON c.id = i.id_clasificacion
                  WHERE i.fecha_periodo >= ? AND i.fecha_periodo <= ?';

        $types = 'ss';
        $params = array((string) $dateFrom, (string) $dateTo);

        if ($clasificacionFilter > 0) {
            $query .= ' AND i.id_clasificacion = ?';
            $types .= 'i';
            $params[] = $clasificacionFilter;
        }

        if ($tipoFilter !== '') {
            $query .= ' AND i.tipo = ?';
            $types .= 's';
            $params[] = $tipoFilter;
        }

        $query .= ' ORDER BY i.fecha DESC LIMIT 3000';

        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            $this->logger->warning('app', 'No fue posible preparar detalle de ingresos legacy para informes.', array(
                'error' => $this->connection->error,
            ));
            return array();
        }

        $this->bindDynamic($statement, $types, $params);
        $statement->execute();
        $result = $statement->get_result();

        $rows = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = array(
                'origen' => 'Ingresos legacy',
                'registro_id' => isset($row['id']) ? (int) $row['id'] : 0,
                'fecha' => isset($row['fecha']) ? (string) $row['fecha'] : '',
                'fecha_periodo' => isset($row['fecha_periodo']) ? (string) $row['fecha_periodo'] : '',
                'clasificacion' => isset($row['clasificacion']) && $row['clasificacion'] !== null ? (string) $row['clasificacion'] : 'Sin clasificacion',
                'detalle' => isset($row['detalle']) ? (string) $row['detalle'] : '',
                'categoria' => 'Ingreso',
                'tipo' => isset($row['tipo']) ? (string) $row['tipo'] : '',
                'valor' => isset($row['valor']) ? (float) $row['valor'] : 0.0,
                'usuario' => 'legacy',
            );
        }

        $statement->close();
        return $rows;
    }

    private function sumIngresosLegacyByMonth($dateFrom, $dateTo)
    {
        $query = "SELECT DATE_FORMAT(fecha_periodo, '%Y-%m') AS periodo, COALESCE(SUM(valor), 0) AS total
                  FROM ingresos
                  WHERE fecha_periodo >= ? AND fecha_periodo <= ?
                  GROUP BY DATE_FORMAT(fecha_periodo, '%Y-%m')";
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            return array();
        }

        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;
        $statement->bind_param('ss', $fromDate, $toDate);
        $statement->execute();
        $result = $statement->get_result();

        $map = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $period = isset($row['periodo']) ? (string) $row['periodo'] : '';
            if ($period === '') {
                continue;
            }
            $map[$period] = isset($row['total']) ? (float) $row['total'] : 0.0;
        }

        $statement->close();
        return $map;
    }

    private function sumMovimientosByMonthAndCategory($dateFrom, $dateTo)
    {
        $query = "SELECT DATE_FORMAT(fecha_periodo, '%Y-%m') AS periodo, gasto_costo, COALESCE(SUM(valor), 0) AS total
                  FROM gastos_costos
                  WHERE fecha_periodo >= ? AND fecha_periodo <= ?
                  GROUP BY DATE_FORMAT(fecha_periodo, '%Y-%m'), gasto_costo";
        $statement = $this->connection->prepare($query);
        if (!$statement instanceof mysqli_stmt) {
            return array();
        }

        $fromDate = (string) $dateFrom;
        $toDate = (string) $dateTo;
        $statement->bind_param('ss', $fromDate, $toDate);
        $statement->execute();
        $result = $statement->get_result();

        $map = array();
        while ($result && ($row = $result->fetch_assoc())) {
            $period = isset($row['periodo']) ? (string) $row['periodo'] : '';
            $categoria = isset($row['gasto_costo']) ? (string) $row['gasto_costo'] : '';
            if ($period === '' || $categoria === '') {
                continue;
            }

            if (!isset($map[$period])) {
                $map[$period] = array();
            }

            $map[$period][$categoria] = isset($row['total']) ? (float) $row['total'] : 0.0;
        }

        $statement->close();
        return $map;
    }

    private function buildMonthKeys($dateFrom, $dateTo)
    {
        $start = strtotime((string) $dateFrom . ' 00:00:00');
        $end = strtotime((string) $dateTo . ' 00:00:00');
        if ($start === false || $end === false || $start > $end) {
            return array();
        }

        $months = array();
        $current = strtotime(date('Y-m-01', $start));
        $endMonth = strtotime(date('Y-m-01', $end));
        while ($current !== false && $current <= $endMonth) {
            $months[] = date('Y-m', $current);
            $current = strtotime('+1 month', $current);
        }

        return $months;
    }

    private function formatMonthLabel($monthKey)
    {
        $timestamp = strtotime((string) $monthKey . '-01');
        if ($timestamp === false) {
            return (string) $monthKey;
        }

        return date('M y', $timestamp);
    }

    private function normalizeKey($text)
    {
        $value = trim((string) $text);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    private function sortRowsByDateDesc($rowA, $rowB)
    {
        $dateA = isset($rowA['fecha']) ? strtotime((string) $rowA['fecha']) : false;
        $dateB = isset($rowB['fecha']) ? strtotime((string) $rowB['fecha']) : false;

        $timeA = $dateA === false ? 0 : (int) $dateA;
        $timeB = $dateB === false ? 0 : (int) $dateB;

        if ($timeA === $timeB) {
            $idA = isset($rowA['registro_id']) ? (int) $rowA['registro_id'] : 0;
            $idB = isset($rowB['registro_id']) ? (int) $rowB['registro_id'] : 0;
            if ($idA === $idB) {
                return 0;
            }
            return $idA > $idB ? -1 : 1;
        }

        return $timeA > $timeB ? -1 : 1;
    }

    private function sortTotalsDesc($rowA, $rowB)
    {
        $totalA = isset($rowA['total']) ? (float) $rowA['total'] : 0.0;
        $totalB = isset($rowB['total']) ? (float) $rowB['total'] : 0.0;

        if ($totalA === $totalB) {
            return 0;
        }

        return $totalA > $totalB ? -1 : 1;
    }

    private function bindDynamic(mysqli_stmt $statement, $types, array $values)
    {
        $params = array($types);
        foreach ($values as $index => $value) {
            $params[] = &$values[$index];
        }

        call_user_func_array(array($statement, 'bind_param'), $params);
    }
}
