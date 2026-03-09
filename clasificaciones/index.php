<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /clasificaciones sin mod_rewrite.
 */

$_GET['route'] = 'clasificaciones';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
