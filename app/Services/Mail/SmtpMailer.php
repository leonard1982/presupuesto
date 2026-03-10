<?php
/**
 * Proyecto PRESUPUESTO - Envio SMTP simple sin dependencias externas.
 */

namespace App\Services\Mail;

use App\Core\Logger;

class SmtpMailer
{
    private $config;
    private $logger;

    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function sendHtml($toEmail, $subject, $htmlBody, $textBody)
    {
        $recipient = trim((string) $toEmail);
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return array('ok' => false, 'message' => 'Correo destino invalido.');
        }

        $host = isset($this->config['host']) ? trim((string) $this->config['host']) : '';
        $port = isset($this->config['port']) ? (int) $this->config['port'] : 25;
        $username = isset($this->config['username']) ? (string) $this->config['username'] : '';
        $password = isset($this->config['password']) ? (string) $this->config['password'] : '';
        $from = isset($this->config['from']) ? trim((string) $this->config['from']) : '';
        $encryption = isset($this->config['encryption']) ? trim((string) $this->config['encryption']) : 'none';
        $timeout = isset($this->config['timeout_seconds']) ? (int) $this->config['timeout_seconds'] : 20;

        if ($host === '' || $port <= 0 || $from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return array('ok' => false, 'message' => 'Configuracion de correo incompleta.');
        }

        $remote = $host . ':' . $port;
        if ($encryption === 'ssl') {
            $remote = 'ssl://' . $remote;
        }

        $socket = @stream_socket_client($remote, $errorCode, $errorMessage, $timeout);
        if (!is_resource($socket)) {
            $this->logger->error('app', 'No fue posible abrir conexion SMTP.', array(
                'host' => $host,
                'port' => $port,
                'error_code' => $errorCode,
                'error' => $errorMessage,
            ));
            return array('ok' => false, 'message' => 'No se pudo conectar al servidor de correo.');
        }

        stream_set_timeout($socket, $timeout);

        $firstResponse = $this->readResponse($socket);
        if ((int) $firstResponse['code'] !== 220) {
            fclose($socket);
            return array('ok' => false, 'message' => 'Servidor de correo no disponible.');
        }

        $helloHost = function_exists('gethostname') ? gethostname() : 'localhost';
        if (!is_string($helloHost) || trim($helloHost) === '') {
            $helloHost = 'localhost';
        }

        if (!$this->sendCommand($socket, 'EHLO ' . $helloHost, array(250))) {
            fclose($socket);
            return array('ok' => false, 'message' => 'No fue posible iniciar sesion SMTP.');
        }

        if ($encryption === 'tls') {
            if (!$this->sendCommand($socket, 'STARTTLS', array(220))) {
                fclose($socket);
                return array('ok' => false, 'message' => 'No fue posible iniciar canal seguro TLS.');
            }

            $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoEnabled !== true) {
                fclose($socket);
                return array('ok' => false, 'message' => 'Fallo al activar cifrado TLS.');
            }

            if (!$this->sendCommand($socket, 'EHLO ' . $helloHost, array(250))) {
                fclose($socket);
                return array('ok' => false, 'message' => 'No fue posible reiniciar sesion SMTP segura.');
            }
        }

        if ($username !== '') {
            if (!$this->sendCommand($socket, 'AUTH LOGIN', array(334))) {
                fclose($socket);
                return array('ok' => false, 'message' => 'No fue posible autenticar en servidor de correo.');
            }

            if (!$this->sendCommand($socket, base64_encode($username), array(334))) {
                fclose($socket);
                return array('ok' => false, 'message' => 'Usuario SMTP rechazado.');
            }

            if (!$this->sendCommand($socket, base64_encode($password), array(235))) {
                fclose($socket);
                return array('ok' => false, 'message' => 'Credenciales SMTP invalidas.');
            }
        }

        if (!$this->sendCommand($socket, 'MAIL FROM:<' . $from . '>', array(250))) {
            fclose($socket);
            return array('ok' => false, 'message' => 'No fue posible definir remitente del correo.');
        }

        if (!$this->sendCommand($socket, 'RCPT TO:<' . $recipient . '>', array(250, 251))) {
            fclose($socket);
            return array('ok' => false, 'message' => 'Correo destino rechazado por servidor.');
        }

        if (!$this->sendCommand($socket, 'DATA', array(354))) {
            fclose($socket);
            return array('ok' => false, 'message' => 'No fue posible enviar contenido del correo.');
        }

        $mimeMessage = $this->buildMimeMessage($from, $recipient, $subject, $htmlBody, $textBody);
        fwrite($socket, $this->dotStuff($mimeMessage) . "\r\n.\r\n");

        $dataResponse = $this->readResponse($socket);
        if ((int) $dataResponse['code'] !== 250) {
            fclose($socket);
            return array('ok' => false, 'message' => 'Servidor rechazo el correo generado.');
        }

        $this->sendCommand($socket, 'QUIT', array(221));
        fclose($socket);

        return array('ok' => true, 'message' => 'Informe enviado correctamente a ' . $recipient . '.');
    }

    private function buildMimeMessage($from, $to, $subject, $htmlBody, $textBody)
    {
        $boundary = '=_Presupuesto_' . md5((string) microtime(true));
        $encodedSubject = $this->encodeHeader($subject);
        $safeText = trim((string) $textBody);
        if ($safeText === '') {
            $safeText = 'Tu cliente de correo no soporta formato HTML.';
        }

        $headers = array(
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        );

        $body = array();
        $body[] = 'This is a multi-part message in MIME format.';
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/plain; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: 8bit';
        $body[] = '';
        $body[] = $safeText;
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/html; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: 8bit';
        $body[] = '';
        $body[] = (string) $htmlBody;
        $body[] = '--' . $boundary . '--';

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $body);
    }

    private function encodeHeader($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            $text = 'Informe PRESUPUESTO';
        }

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($text, 'UTF-8', 'B');
        }

        return $text;
    }

    private function dotStuff($message)
    {
        $normalized = str_replace(array("\r\n", "\r"), "\n", (string) $message);
        $lines = explode("\n", $normalized);

        foreach ($lines as $index => $line) {
            if (strpos($line, '.') === 0) {
                $lines[$index] = '.' . $line;
            }
        }

        return implode("\r\n", $lines);
    }

    private function sendCommand($socket, $command, array $expectedCodes)
    {
        fwrite($socket, $command . "\r\n");
        $response = $this->readResponse($socket);
        $code = (int) $response['code'];

        if (in_array($code, $expectedCodes, true)) {
            return true;
        }

        $this->logger->warning('app', 'Comando SMTP rechazado.', array(
            'command' => $command,
            'response_code' => $code,
            'response' => $response['message'],
        ));

        return false;
    }

    private function readResponse($socket)
    {
        $responseText = '';
        $statusCode = 0;

        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if (!is_string($line)) {
                break;
            }

            $responseText .= $line;
            if (strlen($line) >= 3 && is_numeric(substr($line, 0, 3))) {
                $statusCode = (int) substr($line, 0, 3);
            }

            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
        }

        return array(
            'code' => $statusCode,
            'message' => trim($responseText),
        );
    }
}
