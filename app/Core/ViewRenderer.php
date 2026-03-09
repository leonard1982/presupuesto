<?php
/**
 * Proyecto PRESUPUESTO - Render de vistas con layout principal.
 */

namespace App\Core;

use RuntimeException;

class ViewRenderer
{
    private $viewsRoot;

    public function __construct($viewsRoot)
    {
        $this->viewsRoot = rtrim($viewsRoot, DIRECTORY_SEPARATOR);
    }

    public function render($viewName, array $data = array(), $layoutName = 'layouts/main')
    {
        $viewFile = $this->resolveViewPath($viewName);
        $layoutFile = $this->resolveViewPath($layoutName);

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    private function resolveViewPath($viewName)
    {
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $viewName) . '.php';
        $absolutePath = $this->viewsRoot . DIRECTORY_SEPARATOR . $relativePath;

        if (!is_file($absolutePath)) {
            throw new RuntimeException('Vista no encontrada: ' . $viewName);
        }

        return $absolutePath;
    }
}
