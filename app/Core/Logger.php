<?php
/**
 * Proyecto PRESUPUESTO - Registro de eventos en archivos de log.
 */

namespace App\Core;

class Logger
{
    private $logPaths;

    public function __construct(array $pathsConfig)
    {
        $this->logPaths = array(
            'app' => $pathsConfig['logs_app'],
            'api' => $pathsConfig['logs_api'],
        );
    }

    public function info($channel, $message, array $context = array())
    {
        $this->write($channel, 'INFO', $message, $context);
    }

    public function warning($channel, $message, array $context = array())
    {
        $this->write($channel, 'WARNING', $message, $context);
    }

    public function error($channel, $message, array $context = array())
    {
        $this->write($channel, 'ERROR', $message, $context);
    }

    public function write($channel, $level, $message, array $context = array())
    {
        if (!isset($this->logPaths[$channel])) {
            $channel = 'app';
        }

        $directory = $this->logPaths[$channel];
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $logFilePath = $directory . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
        $payload = array(
            'datetime' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        );

        $line = json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($logFilePath, $line, FILE_APPEND);
    }
}
