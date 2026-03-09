<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /login sin depender de mod_rewrite.
 */

$_GET['route'] = 'login';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
