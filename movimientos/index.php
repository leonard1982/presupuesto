<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /movimientos sin mod_rewrite.
 */

$_GET['route'] = 'movimientos';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
