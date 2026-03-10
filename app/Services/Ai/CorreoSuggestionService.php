<?php
/**
 * Proyecto PRESUPUESTO - Sugerencias IA para convertir correos en movimientos.
 */

namespace App\Services\Ai;

use App\Core\Logger;

class CorreoSuggestionService
{
    private $aiConfig;
    private $logger;

    public function __construct(array $aiConfig, Logger $logger)
    {
        $this->aiConfig = $aiConfig;
        $this->logger = $logger;
    }

    public function suggest(array $correo, array $clasificaciones, array $mediosPago)
    {
        $baseSuggestion = $this->buildRuleBasedSuggestion($correo, $clasificaciones, $mediosPago);
        $openAiSuggestion = $this->requestOpenAiSuggestion($correo, $clasificaciones, $mediosPago, $baseSuggestion);

        if (is_array($openAiSuggestion)) {
            return $this->mergeSuggestions($baseSuggestion, $openAiSuggestion, $clasificaciones);
        }

        return $baseSuggestion;
    }

    private function buildRuleBasedSuggestion(array $correo, array $clasificaciones, array $mediosPago)
    {
        $subject = isset($correo['subject']) ? (string) $correo['subject'] : '';
        $bodyPlain = isset($correo['body_plain']) ? (string) $correo['body_plain'] : '';
        $fullText = trim($subject . "\n" . $bodyPlain);
        $normalizedText = function_exists('mb_strtolower')
            ? mb_strtolower($fullText, 'UTF-8')
            : strtolower($fullText);

        $valor = $this->extractBestAmount($fullText);
        $detalle = $subject !== '' ? $subject : 'Correo importado sin asunto';
        $detalle = $this->truncateText($detalle, 220);

        $tipo = 'Compra';
        if (strpos($normalizedText, 'transfer') !== false) {
            $tipo = 'Transferencia';
        } elseif (strpos($normalizedText, 'suscrip') !== false) {
            $tipo = 'Suscripcion';
        } elseif (strpos($normalizedText, 'nomina') !== false) {
            $tipo = 'Nomina';
        } elseif (strpos($normalizedText, 'tarjeta') !== false || strpos($normalizedText, 'compraste') !== false) {
            $tipo = 'Tarjeta';
        } elseif (strpos($normalizedText, 'pago') !== false) {
            $tipo = 'Pago proveedor';
        }

        $gastoCosto = 'Gasto';
        if (strpos($normalizedText, 'costo') !== false) {
            $gastoCosto = 'Costo';
        } elseif (strpos($normalizedText, 'ingreso') !== false || strpos($normalizedText, 'abono') !== false || strpos($normalizedText, 'deposito') !== false || strpos($normalizedText, 'consignacion') !== false) {
            $gastoCosto = 'Ingreso';
        }

        $medioSugerido = $this->detectBestMedio($normalizedText, $mediosPago);
        if ($medioSugerido !== '') {
            $tipo = $medioSugerido;
        }

        $clasificacionId = $this->detectBestClasificacionId($normalizedText, $clasificaciones);
        if ($clasificacionId <= 0 && !empty($clasificaciones)) {
            $clasificacionId = isset($clasificaciones[0]['id']) ? (int) $clasificaciones[0]['id'] : 0;
        }

        $confidence = 0.45;
        if ($valor > 0) {
            $confidence += 0.2;
        }
        if ($clasificacionId > 0) {
            $confidence += 0.15;
        }
        if ($medioSugerido !== '') {
            $confidence += 0.1;
        }
        if (strpos($normalizedText, 'compraste') !== false || strpos($normalizedText, 'transfer') !== false) {
            $confidence += 0.1;
        }
        if ($confidence > 0.95) {
            $confidence = 0.95;
        }

        $fecha = isset($correo['date_sql']) ? (string) $correo['date_sql'] : date('Y-m-d H:i:s');
        if (!$this->isDateTimeString($fecha)) {
            $fecha = date('Y-m-d H:i:s');
        }

        return array(
            'fecha' => $fecha,
            'id_clasificacion' => $clasificacionId,
            'detalle' => $detalle,
            'valor' => $valor > 0 ? $valor : 0.0,
            'id_presupuesto' => 0,
            'gasto_costo' => $gastoCosto,
            'tipo' => $tipo,
            'por_pagar_cobrar' => 'NINGUNO',
            'valor_neto' => $valor > 0 ? $valor : 0.0,
            'saldo' => 0.0,
            'confidence' => $confidence,
            'source' => 'Reglas internas',
        );
    }

    private function requestOpenAiSuggestion(array $correo, array $clasificaciones, array $mediosPago, array $baseSuggestion)
    {
        $apiKey = isset($this->aiConfig['openai_key']) ? trim((string) $this->aiConfig['openai_key']) : '';
        $model = isset($this->aiConfig['openai_model']) ? trim((string) $this->aiConfig['openai_model']) : 'gpt-4o-mini';
        $timeout = isset($this->aiConfig['openai_timeout_seconds']) ? (int) $this->aiConfig['openai_timeout_seconds'] : 20;

        if ($apiKey === '' || !function_exists('curl_init')) {
            return null;
        }

        $clasificacionesLabel = array();
        foreach ($clasificaciones as $item) {
            $clasificacionesLabel[] = isset($item['descripcion']) ? (string) $item['descripcion'] : '';
        }

        $mediosLabel = array();
        foreach ($mediosPago as $item) {
            $mediosLabel[] = isset($item['medio']) ? (string) $item['medio'] : '';
        }

        $payload = array(
            'from' => isset($correo['from']) ? (string) $correo['from'] : '',
            'subject' => isset($correo['subject']) ? (string) $correo['subject'] : '',
            'body_plain' => $this->truncateText(isset($correo['body_plain']) ? (string) $correo['body_plain'] : '', 2500),
            'date' => isset($correo['date_sql']) ? (string) $correo['date_sql'] : '',
            'clasificaciones_disponibles' => $clasificacionesLabel,
            'medios_disponibles' => $mediosLabel,
            'sugerencia_base' => $baseSuggestion,
        );

        $prompt = "Analiza este correo bancario/compra y responde SOLO JSON con llaves: fecha, clasificacion, detalle, valor, gasto_costo, tipo, por_pagar_cobrar, valor_neto, saldo, confidence. Sin texto adicional. Datos: " . json_encode($payload);

        $requestBody = array(
            'model' => $model,
            'temperature' => 0.1,
            'max_tokens' => 350,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Eres un analista financiero. Extraes datos para un formulario de movimientos.',
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        if ($ch === false) {
            return null;
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout > 0 ? $timeout : 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($rawResponse) || $rawResponse === '' || $statusCode < 200 || $statusCode >= 300) {
            $this->logger->warning('app', 'OpenAI no disponible para sugerencia de correo.', array(
                'status' => $statusCode,
                'error' => $curlError,
            ));
            return null;
        }

        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            return null;
        }

        $content = '';
        if (isset($decoded['choices'][0]['message']['content']) && is_string($decoded['choices'][0]['message']['content'])) {
            $content = trim($decoded['choices'][0]['message']['content']);
        }
        if ($content === '') {
            return null;
        }

        if (substr($content, 0, 3) === '```') {
            $content = trim(str_replace(array('```json', '```'), '', $content));
        }

        $parsed = json_decode($content, true);
        return is_array($parsed) ? $parsed : null;
    }

    private function mergeSuggestions(array $baseSuggestion, array $openAiSuggestion, array $clasificaciones)
    {
        $final = $baseSuggestion;

        if (isset($openAiSuggestion['fecha']) && $this->isDateTimeString((string) $openAiSuggestion['fecha'])) {
            $final['fecha'] = (string) $openAiSuggestion['fecha'];
        }

        if (isset($openAiSuggestion['detalle']) && trim((string) $openAiSuggestion['detalle']) !== '') {
            $final['detalle'] = $this->truncateText((string) $openAiSuggestion['detalle'], 220);
        }

        if (isset($openAiSuggestion['valor'])) {
            $valor = $this->toFloat($openAiSuggestion['valor']);
            if ($valor > 0) {
                $final['valor'] = $valor;
                $final['valor_neto'] = $valor;
            }
        }

        if (isset($openAiSuggestion['gasto_costo']) && in_array((string) $openAiSuggestion['gasto_costo'], array('Ingreso', 'Gasto', 'Costo'), true)) {
            $final['gasto_costo'] = (string) $openAiSuggestion['gasto_costo'];
        }

        if (isset($openAiSuggestion['tipo']) && trim((string) $openAiSuggestion['tipo']) !== '') {
            $final['tipo'] = trim((string) $openAiSuggestion['tipo']);
        }

        if (isset($openAiSuggestion['por_pagar_cobrar']) && in_array((string) $openAiSuggestion['por_pagar_cobrar'], array('COBRAR', 'PAGAR', 'NINGUNO'), true)) {
            $final['por_pagar_cobrar'] = (string) $openAiSuggestion['por_pagar_cobrar'];
        }

        if (isset($openAiSuggestion['saldo'])) {
            $final['saldo'] = $this->toFloat($openAiSuggestion['saldo']);
        }

        if (isset($openAiSuggestion['confidence'])) {
            $confidence = $this->toFloat($openAiSuggestion['confidence']);
            if ($confidence > 0) {
                if ($confidence > 1) {
                    $confidence = 1;
                }
                $final['confidence'] = $confidence;
            }
        }

        if (isset($openAiSuggestion['clasificacion']) && trim((string) $openAiSuggestion['clasificacion']) !== '') {
            $clasificacionText = trim((string) $openAiSuggestion['clasificacion']);
            $clasificacionId = $this->findClasificacionIdByText($clasificacionText, $clasificaciones);
            if ($clasificacionId > 0) {
                $final['id_clasificacion'] = $clasificacionId;
            }
        }

        $final['source'] = 'OpenAI + reglas internas';
        return $final;
    }

    private function findClasificacionIdByText($clasificacionText, array $clasificaciones)
    {
        $needle = function_exists('mb_strtolower')
            ? mb_strtolower(trim((string) $clasificacionText), 'UTF-8')
            : strtolower(trim((string) $clasificacionText));

        if ($needle === '') {
            return 0;
        }

        foreach ($clasificaciones as $item) {
            $desc = isset($item['descripcion']) ? (string) $item['descripcion'] : '';
            $candidate = function_exists('mb_strtolower')
                ? mb_strtolower(trim($desc), 'UTF-8')
                : strtolower(trim($desc));

            if ($candidate !== '' && ($candidate === $needle || strpos($candidate, $needle) !== false || strpos($needle, $candidate) !== false)) {
                return isset($item['id']) ? (int) $item['id'] : 0;
            }
        }

        return 0;
    }

    private function detectBestClasificacionId($normalizedText, array $clasificaciones)
    {
        $bestId = 0;
        $bestScore = 0;
        foreach ($clasificaciones as $item) {
            $id = isset($item['id']) ? (int) $item['id'] : 0;
            $descripcion = isset($item['descripcion']) ? (string) $item['descripcion'] : '';
            if ($id <= 0 || trim($descripcion) === '') {
                continue;
            }

            $tokens = preg_split('/[^a-z0-9]+/i', function_exists('mb_strtolower') ? mb_strtolower($descripcion, 'UTF-8') : strtolower($descripcion));
            if (!is_array($tokens)) {
                $tokens = array();
            }

            $score = 0;
            foreach ($tokens as $token) {
                $tokenText = trim((string) $token);
                if (strlen($tokenText) < 3) {
                    continue;
                }
                if (strpos($normalizedText, $tokenText) !== false) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $id;
            }
        }

        return $bestId;
    }

    private function detectBestMedio($normalizedText, array $mediosPago)
    {
        $bestLabel = '';
        $bestScore = 0;

        foreach ($mediosPago as $item) {
            $medio = isset($item['medio']) ? trim((string) $item['medio']) : '';
            if ($medio === '') {
                continue;
            }

            $medioNormalized = function_exists('mb_strtolower')
                ? mb_strtolower($medio, 'UTF-8')
                : strtolower($medio);
            $tokens = preg_split('/[^a-z0-9]+/i', $medioNormalized);
            if (!is_array($tokens)) {
                $tokens = array();
            }

            $score = 0;
            foreach ($tokens as $token) {
                $tokenText = trim((string) $token);
                if (strlen($tokenText) < 3) {
                    continue;
                }
                if (strpos($normalizedText, $tokenText) !== false) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLabel = $medio;
            }
        }

        return $bestLabel;
    }

    private function extractBestAmount($text)
    {
        $content = (string) $text;
        $matches = array();
        preg_match_all('/(?:\\$\\s*)?([0-9]{1,3}(?:[\\.,][0-9]{3})+(?:[\\.,][0-9]{1,2})?|[0-9]+(?:[\\.,][0-9]{1,2})?)/', $content, $matches);

        if (!isset($matches[1]) || !is_array($matches[1])) {
            return 0.0;
        }

        $best = 0.0;
        foreach ($matches[1] as $rawAmount) {
            $value = $this->toFloat($rawAmount);
            if ($value > $best) {
                $best = $value;
            }
        }

        return $best;
    }

    private function toFloat($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace(' ', '', $text);
        $hasComma = strpos($text, ',') !== false;
        $hasDot = strpos($text, '.') !== false;

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($text, ',');
            $lastDot = strrpos($text, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($hasComma) {
            $text = str_replace(',', '.', $text);
        }

        if (!is_numeric($text)) {
            return 0.0;
        }

        return (float) $text;
    }

    private function truncateText($value, $maxLength)
    {
        $text = trim((string) $value);
        $length = (int) $maxLength;
        if ($length <= 0 || $text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $length) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, $length - 1, 'UTF-8')) . '...';
        }

        if (strlen($text) <= $length) {
            return $text;
        }

        return rtrim(substr($text, 0, $length - 1)) . '...';
    }

    private function isDateTimeString($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return false;
        }

        $normalized = str_replace('T', ' ', $text);
        $formats = array('Y-m-d H:i:s', 'Y-m-d H:i');
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $normalized);
            if ($date instanceof \DateTime) {
                return true;
            }
        }

        return strtotime($normalized) !== false;
    }
}
