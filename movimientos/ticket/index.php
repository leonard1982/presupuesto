<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /movimientos/ticket sin mod_rewrite.
 */

$_GET['route'] = 'movimientos/ticket';
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'index.php';
