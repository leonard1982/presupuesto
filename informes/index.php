<?php
/**
 * Proyecto PRESUPUESTO - Entrada directa para ruta /informes sin depender de mod_rewrite.
 */

$_GET['route'] = 'informes';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
