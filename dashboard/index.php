<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /dashboard sin depender de mod_rewrite.
 */

$_GET['route'] = 'dashboard';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
