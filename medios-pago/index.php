<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /medios-pago sin mod_rewrite.
 */

$_GET['route'] = 'medios-pago';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
