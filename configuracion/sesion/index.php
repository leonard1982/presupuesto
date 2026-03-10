<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /configuracion/sesion sin mod_rewrite.
 */

$_GET['route'] = 'configuracion/sesion';
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'index.php';
