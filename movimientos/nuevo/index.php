<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /movimientos/nuevo sin mod_rewrite.
 */

$_GET['route'] = 'movimientos/nuevo';
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'index.php';
