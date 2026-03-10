<?php
/**
 * Proyecto PRESUPUESTO - Bootstrap principal de aplicacion.
 */

namespace App\Core;

use App\Modules\Auth\Controllers\LoginController;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Catalogos\Controllers\CatalogoController;
use App\Modules\Catalogos\Repositories\CatalogoRepository;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Dashboard\Repositories\DashboardRepository;
use App\Modules\Movimientos\Controllers\MovimientoController;
use App\Modules\Movimientos\Repositories\MovimientoRepository;
use Throwable;

class Bootstrap
{
    private $config;
    private $logger;
    private $viewRenderer;
    private $authService;
    private $databaseConnection;

    public function run()
    {
        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->applyRuntimeConfiguration();

        SessionManager::start($this->config['app']);

        $this->databaseConnection = DatabaseConnection::getConnection($this->config['database'], $this->logger);
        $userRepository = new UserRepository($this->databaseConnection, $this->logger);
        $this->authService = new AuthService($userRepository, $this->logger);
        $this->viewRenderer = new ViewRenderer($this->config['paths']['views_root']);

        $this->dispatchRequest();
    }

    private function loadEnvironment()
    {
        Environment::load(PROJECT_ROOT . DIRECTORY_SEPARATOR . '.env');
    }

    private function loadConfiguration()
    {
        $pathsConfig = require PROJECT_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'paths.php';
        $appConfig = require PROJECT_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'app.php';
        $databaseConfig = require PROJECT_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'database.php';

        $this->config = array(
            'paths' => $pathsConfig,
            'app' => $appConfig,
            'database' => $databaseConfig,
        );

        $this->normalizeBaseUrl();

        $this->logger = new Logger($pathsConfig);
    }

    private function applyRuntimeConfiguration()
    {
        date_default_timezone_set($this->config['app']['timezone']);

        if ($this->config['app']['debug']) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        }
    }

    private function dispatchRequest()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        $routePath = $this->resolveRoutePath();

        $loginController = new LoginController($this->authService, $this->viewRenderer, $this->config['app']);
        $dashboardController = new DashboardController(
            new DashboardRepository($this->databaseConnection, $this->logger),
            $this->viewRenderer,
            $this->config['app'],
            $this->authService
        );
        $movimientoController = new MovimientoController(
            new MovimientoRepository($this->databaseConnection, $this->logger),
            $this->viewRenderer,
            $this->config['app'],
            $this->authService
        );
        $catalogoController = new CatalogoController(
            new CatalogoRepository($this->databaseConnection, $this->logger),
            $this->viewRenderer,
            $this->config['app'],
            $this->authService
        );

        try {
            if ($routePath === '/' || $routePath === '') {
                if ($this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/dashboard'));
                }

                Response::redirect($this->buildUrl('/login'));
            }

            if ($routePath === '/login' && $method === 'GET') {
                $loginController->show();
                return;
            }

            if ($routePath === '/login' && $method === 'POST') {
                $loginController->authenticate();
                return;
            }

            if ($routePath === '/logout') {
                $loginController->logout();
                return;
            }

            if ($routePath === '/dashboard') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $dashboardController->show();
                return;
            }

            if ($routePath === '/movimientos' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->index();
                return;
            }

            if ($routePath === '/movimientos/nuevo' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->createForm();
                return;
            }

            if ($routePath === '/movimientos/editar' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->editForm();
                return;
            }

            if ($routePath === '/movimientos/soporte' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->downloadSupport();
                return;
            }

            if ($routePath === '/movimientos/ticket' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->ticketView();
                return;
            }

            if ($routePath === '/movimientos' && $method === 'POST') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->store();
                return;
            }

            if ($routePath === '/movimientos/actualizar' && $method === 'POST') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->update();
                return;
            }

            if ($routePath === '/movimientos/eliminar' && $method === 'POST') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $movimientoController->delete();
                return;
            }

            if ($routePath === '/clasificaciones' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $catalogoController->clasificacionesIndex();
                return;
            }

            if ($routePath === '/clasificaciones' && $method === 'POST') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $catalogoController->clasificacionesStore();
                return;
            }

            if ($routePath === '/medios-pago' && $method === 'GET') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $catalogoController->mediosIndex();
                return;
            }

            if ($routePath === '/medios-pago' && $method === 'POST') {
                if (!$this->authService->isAuthenticated()) {
                    Response::redirect($this->buildUrl('/login'));
                }

                $catalogoController->mediosStore();
                return;
            }

            $this->renderNotFound();
        } catch (Throwable $exception) {
            $this->logger->error('app', 'Error no controlado en bootstrap.', array(
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ));

            http_response_code(500);
            echo 'Ocurrio un error interno. Revisa el log de aplicacion.';
        }
    }

    private function renderNotFound()
    {
        http_response_code(404);
        echo 'Ruta no encontrada.';
    }

    private function resolveRoutePath()
    {
        if (isset($_GET['route']) && is_string($_GET['route']) && trim($_GET['route']) !== '') {
            return '/' . trim($_GET['route'], '/');
        }

        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = (string) parse_url($requestUri, PHP_URL_PATH);

        $scriptDirectory = str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME']));
        if ($scriptDirectory !== '/' && $scriptDirectory !== '' && strpos($path, $scriptDirectory) === 0) {
            $path = substr($path, strlen($scriptDirectory));
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        if (strpos($path, '/index.php/') === 0) {
            $path = substr($path, strlen('/index.php'));
            if ($path === '') {
                $path = '/';
            }
        }

        return $path;
    }

    private function buildUrl($path)
    {
        $baseUrl = rtrim($this->config['app']['base_url'], '/');
        $route = trim((string) $path, '/');

        if ($route === '') {
            return $baseUrl . '/index.php';
        }

        $encodedRoute = implode('/', array_map('rawurlencode', explode('/', $route)));
        return $baseUrl . '/index.php?route=' . $encodedRoute;
    }

    private function normalizeBaseUrl()
    {
        $configuredBaseUrl = trim((string) $this->config['app']['base_url']);

        if ($configuredBaseUrl === '' || strtoupper($configuredBaseUrl) === 'AUTO') {
            $this->config['app']['base_url'] = $this->detectBaseUrlFromRequest();
            return;
        }

        $this->config['app']['base_url'] = rtrim($configuredBaseUrl, '/');
    }

    private function detectBaseUrlFromRequest()
    {
        $httpsEnabled = false;

        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            $httpsEnabled = true;
        } elseif (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            $httpsEnabled = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            $httpsEnabled = true;
        }

        $scheme = $httpsEnabled ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '' ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
        $basePath = $this->detectProjectBasePathFromFilesystem();

        if ($basePath === '') {
            $basePath = $this->detectProjectBasePathFromScriptName();
        }

        return $scheme . '://' . $host . $basePath;
    }

    private function detectProjectBasePathFromFilesystem()
    {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? (string) $_SERVER['DOCUMENT_ROOT'] : '';
        $projectRoot = (string) PROJECT_ROOT;

        $normalizedDocumentRoot = $this->normalizePath($documentRoot);
        $normalizedProjectRoot = $this->normalizePath($projectRoot);

        if ($normalizedDocumentRoot === '' || $normalizedProjectRoot === '') {
            return '';
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $matchesPrefix = $isWindows
            ? stripos($normalizedProjectRoot, $normalizedDocumentRoot) === 0
            : strpos($normalizedProjectRoot, $normalizedDocumentRoot) === 0;

        if (!$matchesPrefix) {
            return '';
        }

        $relativePath = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
        $relativePath = str_replace('\\', '/', (string) $relativePath);
        $relativePath = trim($relativePath, '/');

        if ($relativePath === '') {
            return '';
        }

        return '/' . $relativePath;
    }

    private function detectProjectBasePathFromScriptName()
    {
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        $directory = str_replace('\\', '/', dirname($scriptName));

        if ($directory === '/' || $directory === '.' || $directory === '\\') {
            return '';
        }

        if (substr($directory, -7) === '/public') {
            $directory = substr($directory, 0, -7);
        }

        if (substr($directory, -6) === '/login') {
            $directory = substr($directory, 0, -6);
        } elseif (substr($directory, -10) === '/dashboard') {
            $directory = substr($directory, 0, -10);
        } elseif (substr($directory, -7) === '/logout') {
            $directory = substr($directory, 0, -7);
        } elseif (substr($directory, -12) === '/movimientos') {
            $directory = substr($directory, 0, -12);
        } elseif (substr($directory, -18) === '/movimientos/nuevo') {
            $directory = substr($directory, 0, -18);
        } elseif (substr($directory, -18) === '/movimientos/editar') {
            $directory = substr($directory, 0, -18);
        } elseif (substr($directory, -20) === '/movimientos/soporte') {
            $directory = substr($directory, 0, -20);
        } elseif (substr($directory, -19) === '/movimientos/ticket') {
            $directory = substr($directory, 0, -19);
        } elseif (substr($directory, -16) === '/clasificaciones') {
            $directory = substr($directory, 0, -16);
        } elseif (substr($directory, -12) === '/medios-pago') {
            $directory = substr($directory, 0, -12);
        }

        $directory = rtrim($directory, '/');
        if ($directory === '') {
            return '';
        }

        return $directory;
    }

    private function normalizePath($path)
    {
        if (!is_string($path) || trim($path) === '') {
            return '';
        }

        $realPath = realpath($path);
        $normalized = $realPath !== false ? $realPath : $path;
        $normalized = str_replace('\\', '/', (string) $normalized);
        $normalized = rtrim($normalized, '/');

        return $normalized;
    }
}
