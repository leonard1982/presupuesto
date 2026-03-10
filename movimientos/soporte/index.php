<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /movimientos/soporte sin mod_rewrite.
 */

$_GET['route'] = 'movimientos/soporte';
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'index.php';
