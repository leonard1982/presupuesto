<?php
/**
 * Proyecto PRESUPUESTO - Asesor KPI con motor hibrido (reglas + OpenAI opcional).
 */

namespace App\Services\Ai;

use App\Core\Logger;

class KpiAdvisorService
{
    private $config;
    private $logger;

    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function generateAdvice(array $snapshot)
    {
        $ruleBased = $this->buildRuleBasedAdvice($snapshot);
        $openAiAdvice = $this->requestOpenAiAdvice($snapshot, $ruleBased);

        if ($openAiAdvice !== null && !empty($openAiAdvice['items'])) {
            return array(
                'title' => isset($openAiAdvice['title']) ? $openAiAdvice['title'] : 'Consejo KPI',
                'items' => $openAiAdvice['items'],
                'source' => 'OpenAI + reglas internas',
                'generated_at' => date('Y-m-d H:i:s'),
            );
        }

        return array(
            'title' => 'Consejo KPI',
            'items' => $ruleBased,
            'source' => 'Reglas internas',
            'generated_at' => date('Y-m-d H:i:s'),
        );
    }

    private function buildRuleBasedAdvice(array $snapshot)
    {
        $tips = array();

        $totals = isset($snapshot['monthly_totals']) && is_array($snapshot['monthly_totals']) ? $snapshot['monthly_totals'] : array();
        $ingresos = isset($totals['ingresos']) ? (float) $totals['ingresos'] : 0.0;
        $gastos = isset($totals['gastos']) ? (float) $totals['gastos'] : 0.0;
        $costos = isset($totals['costos']) ? (float) $totals['costos'] : 0.0;
        $balance = isset($snapshot['balance']) ? (float) $snapshot['balance'] : 0.0;

        $expenseRatio = $ingresos > 0 ? (($gastos + $costos) / $ingresos) : 0.0;

        if ($ingresos <= 0) {
            $tips[] = 'No hay ingresos registrados en el periodo. Prioriza registrar entradas para calcular indicadores confiables.';
        } elseif ($expenseRatio >= 1.0) {
            $tips[] = 'Los egresos superan los ingresos. Congela gastos no esenciales y revisa pagos recurrentes de mayor valor.';
        } elseif ($expenseRatio >= 0.85) {
            $tips[] = 'El margen esta ajustado. Define un tope semanal de gastos para proteger liquidez.';
        } else {
            $tips[] = 'El margen es saludable. Aprovecha para crear un fondo de reserva con parte del balance.';
        }

        if ($balance < 0) {
            $tips[] = 'El balance es negativo. Recomiendo priorizar cobros pendientes y renegociar pagos por vencer.';
        } elseif ($balance > 0 && $ingresos > 0 && ($balance / $ingresos) >= 0.2) {
            $tips[] = 'El balance del periodo es positivo y alto. Puedes asignar una fraccion a inversion o amortizacion de deudas.';
        }

        $top = isset($snapshot['top_clasificaciones']) && is_array($snapshot['top_clasificaciones']) ? $snapshot['top_clasificaciones'] : array();
        if (!empty($top)) {
            $topName = isset($top[0]['clasificacion']) ? (string) $top[0]['clasificacion'] : 'Sin clasificacion';
            $topValue = isset($top[0]['total']) ? (float) $top[0]['total'] : 0.0;
            if ($gastos + $costos > 0 && ($topValue / ($gastos + $costos)) >= 0.35) {
                $tips[] = 'La clasificacion "' . $topName . '" concentra gran parte de egresos. Revisa proveedores y frecuencia de compras en esa categoria.';
            }
        }

        if (count($tips) < 3) {
            $tips[] = 'Mantener clasificaciones bien definidas mejora la precision de reportes y recomendaciones futuras.';
        }

        return array_values(array_slice(array_unique($tips), 0, 4));
    }

    private function requestOpenAiAdvice(array $snapshot, array $fallbackAdvice)
    {
        $apiKey = isset($this->config['openai_key']) ? trim((string) $this->config['openai_key']) : '';
        $model = isset($this->config['openai_model']) ? trim((string) $this->config['openai_model']) : 'gpt-4o-mini';
        $timeout = isset($this->config['openai_timeout_seconds']) ? (int) $this->config['openai_timeout_seconds'] : 20;

        if ($apiKey === '' || !function_exists('curl_init')) {
            return null;
        }

        $period = isset($snapshot['period_label']) ? (string) $snapshot['period_label'] : 'Periodo actual';
        $totals = isset($snapshot['monthly_totals']) && is_array($snapshot['monthly_totals']) ? $snapshot['monthly_totals'] : array();
        $payload = array(
            'periodo' => $period,
            'ingresos' => isset($totals['ingresos']) ? (float) $totals['ingresos'] : 0.0,
            'gastos' => isset($totals['gastos']) ? (float) $totals['gastos'] : 0.0,
            'costos' => isset($totals['costos']) ? (float) $totals['costos'] : 0.0,
            'balance' => isset($snapshot['balance']) ? (float) $snapshot['balance'] : 0.0,
            'top_clasificaciones' => isset($snapshot['top_clasificaciones']) ? $snapshot['top_clasificaciones'] : array(),
        );

        $userPrompt = "Genera recomendaciones KPI para una empresa. Responde SOLO JSON con formato: {\"titulo\":\"...\",\"recomendaciones\":[\"...\",\"...\"]}. Maximo 4 recomendaciones. Datos: " . json_encode($payload);

        $requestBody = array(
            'model' => $model,
            'temperature' => 0.2,
            'max_tokens' => 350,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Eres un asesor financiero ejecutivo que entrega recomendaciones accionables, breves y claras.',
                ),
                array(
                    'role' => 'user',
                    'content' => $userPrompt,
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
            $this->logger->warning('app', 'OpenAI no disponible para asesor KPI, se usa fallback.', array(
                'status' => $statusCode,
                'error' => $curlError,
            ));
            return null;
        }

        $decodedResponse = json_decode($rawResponse, true);
        if (!is_array($decodedResponse)) {
            return null;
        }

        $messageContent = '';
        if (isset($decodedResponse['choices'][0]['message']['content']) && is_string($decodedResponse['choices'][0]['message']['content'])) {
            $messageContent = trim($decodedResponse['choices'][0]['message']['content']);
        }

        if ($messageContent === '') {
            return null;
        }

        $parsed = $this->parseOpenAiContent($messageContent, $fallbackAdvice);
        return $parsed;
    }

    private function parseOpenAiContent($content, array $fallbackAdvice)
    {
        $jsonCandidate = trim((string) $content);
        if (substr($jsonCandidate, 0, 3) === '```') {
            $jsonCandidate = trim(str_replace(array('```json', '```'), '', $jsonCandidate));
        }

        $decoded = json_decode($jsonCandidate, true);
        if (!is_array($decoded)) {
            return null;
        }

        $title = isset($decoded['titulo']) ? trim((string) $decoded['titulo']) : 'Consejo KPI';
        $items = array();

        if (isset($decoded['recomendaciones']) && is_array($decoded['recomendaciones'])) {
            foreach ($decoded['recomendaciones'] as $tip) {
                $tipText = trim((string) $tip);
                if ($tipText !== '') {
                    $items[] = $tipText;
                }
            }
        }

        if (empty($items)) {
            $items = $fallbackAdvice;
        }

        return array(
            'title' => $title !== '' ? $title : 'Consejo KPI',
            'items' => array_values(array_slice($items, 0, 4)),
        );
    }
}
