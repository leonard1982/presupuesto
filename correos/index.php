<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /correos sin depender de mod_rewrite.
 */

$_GET['route'] = 'correos';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
