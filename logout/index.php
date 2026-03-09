<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /logout sin depender de mod_rewrite.
 */

$_GET['route'] = 'logout';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
