<?php
/**
 * Proyecto PRESUPUESTO - Respuestas HTTP basicas.
 */

namespace App\Core;

class Response
{
    public static function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    public static function json(array $payload, $statusCode = 200)
    {
        http_response_code((int) $statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
