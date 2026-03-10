<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /movimientos/editar sin mod_rewrite.
 */

$_GET['route'] = 'movimientos/editar';
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'index.php';
