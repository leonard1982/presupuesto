<?php
/**
 * Proyecto PRESUPUESTO - Lectura de bandeja IMAP para sugerencias de movimientos.
 */

namespace App\Services\Mail;

use App\Core\Logger;

class InboxReaderService
{
    private $config;
    private $logger;

    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function fetchRecentMessages()
    {
        if (!function_exists('imap_open')) {
            return array(
                'ok' => false,
                'message' => 'La extension IMAP no esta disponible en el servidor.',
                'messages' => array(),
            );
        }

        $mailbox = $this->buildMailboxString();
        $username = isset($this->config['username']) ? trim((string) $this->config['username']) : '';
        $password = isset($this->config['password']) ? (string) $this->config['password'] : '';

        if ($mailbox === '' || $username === '' || $password === '') {
            return array(
                'ok' => false,
                'message' => 'Configura credenciales de bandeja IMAP para consultar correos.',
                'messages' => array(),
            );
        }

        $stream = @imap_open($mailbox, $username, $password, OP_READONLY, 1, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'));
        if ($stream === false) {
            $error = imap_last_error();
            $this->logger->warning('app', 'No fue posible abrir la bandeja IMAP.', array('error' => $error));

            return array(
                'ok' => false,
                'message' => 'No fue posible abrir la bandeja de correos con las credenciales actuales.',
                'messages' => array(),
            );
        }

        $uids = imap_search($stream, 'ALL', SE_UID);
        if (!is_array($uids) || empty($uids)) {
            imap_close($stream);
            return array(
                'ok' => true,
                'message' => 'No hay correos disponibles en la bandeja.',
                'messages' => array(),
            );
        }

        rsort($uids, SORT_NUMERIC);
        $limit = isset($this->config['max_messages']) ? (int) $this->config['max_messages'] : 35;
        if ($limit <= 0) {
            $limit = 35;
        }

        $selectedUids = array_slice($uids, 0, $limit);
        $messages = array();

        foreach ($selectedUids as $uid) {
            $messageNumber = imap_msgno($stream, (int) $uid);
            if ($messageNumber <= 0) {
                continue;
            }

            $overviewRows = imap_fetch_overview($stream, (string) $messageNumber, 0);
            $overview = (is_array($overviewRows) && isset($overviewRows[0])) ? $overviewRows[0] : null;
            if ($overview === null) {
                continue;
            }

            $subject = $this->decodeMimeHeader(isset($overview->subject) ? $overview->subject : '');
            $from = $this->decodeMimeHeader(isset($overview->from) ? $overview->from : '');
            $dateRaw = isset($overview->date) ? trim((string) $overview->date) : '';
            $dateSql = $this->normalizeMessageDate($dateRaw);
            $bodyPlain = $this->extractBodyAsPlainText($stream, $messageNumber);
            $snippet = $this->buildSnippet($bodyPlain);

            $messages[] = array(
                'uid' => (int) $uid,
                'subject' => $subject !== '' ? $subject : '(Sin asunto)',
                'from' => $from,
                'date_raw' => $dateRaw,
                'date_sql' => $dateSql,
                'body_plain' => $bodyPlain,
                'snippet' => $snippet,
                'hash' => sha1((string) $uid . '|' . $dateRaw . '|' . $subject . '|' . $from),
            );
        }

        imap_close($stream);

        return array(
            'ok' => true,
            'message' => '',
            'messages' => $messages,
        );
    }

    private function buildMailboxString()
    {
        $host = isset($this->config['host']) ? trim((string) $this->config['host']) : '';
        $port = isset($this->config['port']) ? (int) $this->config['port'] : 0;
        $folder = isset($this->config['folder']) ? trim((string) $this->config['folder']) : 'INBOX';
        $encryption = isset($this->config['encryption']) ? strtolower(trim((string) $this->config['encryption'])) : 'ssl';
        $novalidate = !empty($this->config['novalidate_cert']);

        if ($host === '' || $port <= 0) {
            return '';
        }

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        if ($novalidate) {
            $flags .= '/novalidate-cert';
        }

        if ($folder === '') {
            $folder = 'INBOX';
        }

        return '{' . $host . ':' . $port . $flags . '}' . $folder;
    }

    private function extractBodyAsPlainText($stream, $messageNumber)
    {
        $structure = imap_fetchstructure($stream, (int) $messageNumber);
        if (!is_object($structure)) {
            $raw = imap_body($stream, (int) $messageNumber, FT_PEEK);
            return $this->sanitizeBodyText((string) $raw);
        }

        $parts = $this->flattenStructureParts($structure);
        $plainPart = $this->findPartBySubtype($parts, 'PLAIN');
        $htmlPart = $this->findPartBySubtype($parts, 'HTML');

        if ($plainPart !== null) {
            $raw = imap_fetchbody($stream, (int) $messageNumber, $plainPart['part_number'], FT_PEEK);
            $decoded = $this->decodePartBody((string) $raw, (int) $plainPart['encoding']);
            return $this->sanitizeBodyText($decoded);
        }

        if ($htmlPart !== null) {
            $raw = imap_fetchbody($stream, (int) $messageNumber, $htmlPart['part_number'], FT_PEEK);
            $decoded = $this->decodePartBody((string) $raw, (int) $htmlPart['encoding']);
            return $this->sanitizeBodyText(strip_tags($decoded));
        }

        $rawFallback = imap_body($stream, (int) $messageNumber, FT_PEEK);
        return $this->sanitizeBodyText((string) $rawFallback);
    }

    private function flattenStructureParts($structure, $prefix = '')
    {
        $flat = array();

        if (!is_object($structure) || !isset($structure->parts) || !is_array($structure->parts)) {
            return $flat;
        }

        foreach ($structure->parts as $index => $part) {
            $partNumber = $prefix === '' ? (string) ($index + 1) : $prefix . '.' . ($index + 1);
            $flat[] = array(
                'part_number' => $partNumber,
                'type' => isset($part->type) ? (int) $part->type : 0,
                'subtype' => isset($part->subtype) ? strtoupper((string) $part->subtype) : '',
                'encoding' => isset($part->encoding) ? (int) $part->encoding : 0,
            );

            if (isset($part->parts) && is_array($part->parts)) {
                $nested = $this->flattenStructureParts($part, $partNumber);
                foreach ($nested as $nestedItem) {
                    $flat[] = $nestedItem;
                }
            }
        }

        return $flat;
    }

    private function findPartBySubtype(array $parts, $subtype)
    {
        $targetSubtype = strtoupper((string) $subtype);
        foreach ($parts as $partInfo) {
            if (!isset($partInfo['type']) || !isset($partInfo['subtype'])) {
                continue;
            }

            if ((int) $partInfo['type'] === 0 && strtoupper((string) $partInfo['subtype']) === $targetSubtype) {
                return $partInfo;
            }
        }

        return null;
    }

    private function decodePartBody($rawBody, $encoding)
    {
        $value = (string) $rawBody;
        $encodingInt = (int) $encoding;

        if ($encodingInt === 3) {
            $decoded = base64_decode($value, true);
            return $decoded !== false ? (string) $decoded : $value;
        }

        if ($encodingInt === 4) {
            return quoted_printable_decode($value);
        }

        return $value;
    }

    private function sanitizeBodyText($value)
    {
        $text = (string) $value;
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        if (!is_string($text)) {
            $text = '';
        }

        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        if (!is_string($text)) {
            $text = '';
        }

        return trim($text);
    }

    private function buildSnippet($bodyPlain)
    {
        $text = trim((string) $bodyPlain);
        if ($text === '') {
            return '(Sin contenido visible)';
        }

        if (function_exists('mb_substr')) {
            $snippet = mb_substr($text, 0, 280, 'UTF-8');
        } else {
            $snippet = substr($text, 0, 280);
        }

        return trim($snippet);
    }

    private function decodeMimeHeader($value)
    {
        $source = (string) $value;
        if ($source === '') {
            return '';
        }

        if (function_exists('imap_mime_header_decode')) {
            $parts = imap_mime_header_decode($source);
            if (is_array($parts) && !empty($parts)) {
                $decodedText = '';
                foreach ($parts as $part) {
                    $chunk = isset($part->text) ? (string) $part->text : '';
                    $decodedText .= $chunk;
                }
                if ($decodedText !== '') {
                    return trim($decodedText);
                }
            }
        }

        return trim($source);
    }

    private function normalizeMessageDate($value)
    {
        $dateValue = trim((string) $value);
        if ($dateValue === '') {
            return date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($dateValue);
        if ($timestamp === false) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
