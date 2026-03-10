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

        $username = isset($this->config['username']) ? trim((string) $this->config['username']) : '';
        $password = isset($this->config['password']) ? (string) $this->config['password'] : '';
        $mailboxCandidates = $this->buildMailboxCandidates();

        if (empty($mailboxCandidates) || $username === '' || $password === '') {
            return array(
                'ok' => false,
                'message' => 'Configura credenciales de bandeja IMAP para consultar correos.',
                'messages' => array(),
            );
        }

        $stream = false;
        $openErrors = array();
        $attempts = array();

        foreach ($mailboxCandidates as $candidate) {
            $attempts[] = isset($candidate['label']) ? (string) $candidate['label'] : 'imap';
            $warningText = '';
            $opened = $this->openMailboxStream($candidate, $username, $password, $warningText);

            if ($opened !== false) {
                $stream = $opened;
                break;
            }

            $imapError = imap_last_error();
            $errorParts = array();
            if (is_string($imapError) && trim($imapError) !== '') {
                $errorParts[] = trim($imapError);
            }
            if ($warningText !== '') {
                $errorParts[] = $warningText;
            }

            if (!empty($errorParts)) {
                $openErrors[] = implode(' | ', array_unique($errorParts));
            }
        }

        if ($stream === false) {
            $error = !empty($openErrors) ? implode(' || ', $openErrors) : (string) imap_last_error();
            $this->logger->warning('app', 'No fue posible abrir la bandeja IMAP.', array(
                'error' => $error,
                'attempts' => $attempts,
            ));

            return array(
                'ok' => false,
                'message' => 'No fue posible abrir la bandeja de correos. Verifica servidor IMAP, puerto y cifrado.',
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

    private function buildMailboxCandidates()
    {
        $host = isset($this->config['host']) ? trim((string) $this->config['host']) : '';
        $port = isset($this->config['port']) ? (int) $this->config['port'] : 0;
        $folder = isset($this->config['folder']) ? trim((string) $this->config['folder']) : 'INBOX';
        $encryption = isset($this->config['encryption']) ? strtolower(trim((string) $this->config['encryption'])) : 'ssl';
        $novalidate = !empty($this->config['novalidate_cert']);

        if ($host === '' || $port <= 0) {
            return array();
        }

        if ($folder === '') {
            $folder = 'INBOX';
        }

        $candidates = array();
        $seen = array();

        $candidateList = array(
            array('port' => $port, 'encryption' => $encryption),
            array('port' => 993, 'encryption' => 'ssl'),
            array('port' => 143, 'encryption' => 'tls'),
            array('port' => 143, 'encryption' => 'none'),
        );

        foreach ($candidateList as $candidateConfig) {
            $candidatePort = isset($candidateConfig['port']) ? (int) $candidateConfig['port'] : 0;
            $candidateEncryption = isset($candidateConfig['encryption']) ? strtolower(trim((string) $candidateConfig['encryption'])) : 'ssl';
            if ($candidatePort <= 0) {
                continue;
            }
            if (!in_array($candidateEncryption, array('ssl', 'tls', 'none'), true)) {
                $candidateEncryption = 'ssl';
            }

            $candidateKey = $candidatePort . '|' . $candidateEncryption;
            if (isset($seen[$candidateKey])) {
                continue;
            }
            $seen[$candidateKey] = true;

            $mailbox = $this->buildMailboxString($host, $candidatePort, $folder, $candidateEncryption, $novalidate);
            if ($mailbox === '') {
                continue;
            }

            $candidates[] = array(
                'mailbox' => $mailbox,
                'label' => $host . ':' . $candidatePort . ' (' . strtoupper($candidateEncryption) . ')',
            );
        }

        return $candidates;
    }

    private function buildMailboxString($host, $port, $folder, $encryption, $novalidate)
    {
        $hostSafe = trim((string) $host);
        $portInt = (int) $port;
        $folderSafe = trim((string) $folder);
        $encryptionSafe = strtolower(trim((string) $encryption));
        $novalidateCert = (bool) $novalidate;

        if ($hostSafe === '' || $portInt <= 0) {
            return '';
        }

        $flags = '/imap';
        if ($encryptionSafe === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryptionSafe === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        if ($novalidateCert) {
            $flags .= '/novalidate-cert';
        }

        if ($folderSafe === '') {
            $folderSafe = 'INBOX';
        }

        return '{' . $hostSafe . ':' . $portInt . $flags . '}' . $folderSafe;
    }

    private function openMailboxStream(array $candidate, $username, $password, &$warningText)
    {
        $mailbox = isset($candidate['mailbox']) ? (string) $candidate['mailbox'] : '';
        if ($mailbox === '') {
            return false;
        }

        $warningText = '';
        $errorCollector = '';
        set_error_handler(function ($severity, $message) use (&$errorCollector) {
            $errorCollector = trim((string) $message);
            return true;
        });

        $stream = imap_open($mailbox, $username, $password, OP_READONLY, 1, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'));
        restore_error_handler();

        $warningText = $errorCollector;
        return $stream;
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
            $decoded = $this->decodePartBody(
                (string) $raw,
                (int) $plainPart['encoding'],
                isset($plainPart['charset']) ? (string) $plainPart['charset'] : ''
            );
            return $this->sanitizeBodyText($decoded);
        }

        if ($htmlPart !== null) {
            $raw = imap_fetchbody($stream, (int) $messageNumber, $htmlPart['part_number'], FT_PEEK);
            $decoded = $this->decodePartBody(
                (string) $raw,
                (int) $htmlPart['encoding'],
                isset($htmlPart['charset']) ? (string) $htmlPart['charset'] : ''
            );
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
                'charset' => $this->extractPartCharset($part),
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

    private function extractPartCharset($part)
    {
        if (!is_object($part)) {
            return '';
        }

        $sources = array('parameters', 'dparameters');
        foreach ($sources as $sourceName) {
            if (!isset($part->{$sourceName}) || !is_array($part->{$sourceName})) {
                continue;
            }

            foreach ($part->{$sourceName} as $parameter) {
                if (!is_object($parameter)) {
                    continue;
                }

                $attribute = isset($parameter->attribute) ? strtolower(trim((string) $parameter->attribute)) : '';
                if ($attribute !== 'charset') {
                    continue;
                }

                return isset($parameter->value) ? trim((string) $parameter->value) : '';
            }
        }

        return '';
    }

    private function decodePartBody($rawBody, $encoding, $charsetHint)
    {
        $value = (string) $rawBody;
        $encodingInt = (int) $encoding;

        if ($encodingInt === 3) {
            $decoded = base64_decode($value, true);
            return $decoded !== false ? (string) $decoded : $value;
        }

        if ($encodingInt === 4) {
            $value = quoted_printable_decode($value);
        }

        return $this->normalizeEncodingToUtf8($value, (string) $charsetHint);
    }

    private function normalizeEncodingToUtf8($value, $charsetHint)
    {
        $text = (string) $value;
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $hint = strtoupper(str_replace('_', '-', trim((string) $charsetHint)));
        $encodings = array();

        if ($hint !== '' && $hint !== 'DEFAULT') {
            if ($hint === 'US-ASCII') {
                $hint = 'ASCII';
            } elseif ($hint === 'ISO8859-1') {
                $hint = 'ISO-8859-1';
            } elseif ($hint === 'CP1252') {
                $hint = 'WINDOWS-1252';
            }
            $encodings[] = $hint;
        }

        $encodings[] = 'WINDOWS-1252';
        $encodings[] = 'ISO-8859-1';
        $encodings[] = 'UTF-8';
        $encodings = array_values(array_unique($encodings));

        if (function_exists('mb_convert_encoding')) {
            foreach ($encodings as $encoding) {
                $converted = @mb_convert_encoding($text, 'UTF-8', $encoding);
                if (!is_string($converted) || $converted === '') {
                    continue;
                }
                if (!function_exists('mb_check_encoding') || mb_check_encoding($converted, 'UTF-8')) {
                    return $converted;
                }
            }
        }

        if (function_exists('iconv')) {
            foreach ($encodings as $encoding) {
                $converted = @iconv($encoding, 'UTF-8//IGNORE', $text);
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
            }
        }

        return $text;
    }

    private function sanitizeBodyText($value)
    {
        $text = (string) $value;
        $text = $this->decodeLikelyBase64Payload($text);

        // Correos con quoted-printable pueden traer HTML y secuencias como =3D.
        if (strpos($text, '=3D') !== false || preg_match('/=\r?\n/', $text)) {
            $decodedQuoted = quoted_printable_decode($text);
            if (is_string($decodedQuoted) && $decodedQuoted !== '') {
                $text = $decodedQuoted;
            }
        }

        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', ' ', $text);
        if (!is_string($text)) {
            $text = '';
        }

        $looksLikeHtml = stripos($text, '<html') !== false
            || stripos($text, '<body') !== false
            || stripos($text, '<div') !== false
            || stripos($text, '<p') !== false
            || preg_match('/<[^>]+>/', $text);

        if ($looksLikeHtml) {
            if (function_exists('html_entity_decode')) {
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
            $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $text);
            $text = preg_replace('/<!--.*?-->/s', ' ', $text);
            if (!is_string($text)) {
                $text = '';
            }
            $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text);
            $text = preg_replace('/<\s*\/p\s*>/i', "\n", $text);
            $text = preg_replace('/<\s*\/div\s*>/i', "\n", $text);
            if (!is_string($text)) {
                $text = '';
            }
            $text = strip_tags($text);
        }

        if (function_exists('html_entity_decode')) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $text = $this->normalizeEncodingToUtf8($text, '');
        $text = $this->removeCssNoise($text);
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

    private function decodeLikelyBase64Payload($value)
    {
        $text = trim((string) $value);
        if ($text === '' || strlen($text) < 120) {
            return (string) $value;
        }

        $compact = preg_replace('/\s+/', '', $text);
        if (!is_string($compact) || $compact === '') {
            return (string) $value;
        }

        if (strlen($compact) < 120 || (strlen($compact) % 4) !== 0) {
            return (string) $value;
        }

        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $compact)) {
            return (string) $value;
        }

        $decoded = base64_decode($compact, true);
        if (!is_string($decoded) || $decoded === '') {
            return (string) $value;
        }

        $decodedTrimmed = trim($decoded);
        if ($decodedTrimmed === '') {
            return (string) $value;
        }

        if (preg_match('/<html|<body|<!doctype|<div|<p|<table/i', $decodedTrimmed)) {
            return $decodedTrimmed;
        }

        if (preg_match('/[A-Za-z0-9].{40,}/s', $decodedTrimmed) && strpos($decodedTrimmed, '=3D') === false) {
            return $decodedTrimmed;
        }

        return (string) $value;
    }

    private function removeCssNoise($value)
    {
        $text = (string) $value;
        if ($text === '') {
            return '';
        }

        $lines = preg_split('/\r?\n/', $text);
        if (!is_array($lines) || empty($lines)) {
            return trim($text);
        }

        $filtered = array();
        foreach ($lines as $line) {
            $current = trim((string) $line);
            if ($current === '') {
                continue;
            }

            $lower = strtolower($current);
            if (strpos($lower, 'readmsgbody') !== false
                || strpos($lower, 'externalclass') !== false
                || strpos($lower, '#outlook') !== false
                || strpos($lower, 'mso-') !== false
            ) {
                continue;
            }

            if (preg_match('/^@media\b/i', $current)) {
                continue;
            }

            if (preg_match('/^[\.\#a-z0-9\-\_\[\]\(\)\,\s:>]+\{[^\}]*\}\s*$/i', $current)) {
                continue;
            }

            if (strpos($current, '{') !== false
                && strpos($current, '}') !== false
                && strpos($current, ':') !== false
                && strpos($current, ';') !== false
                && strlen($current) > 18
            ) {
                continue;
            }

            $filtered[] = $current;
        }

        if (empty($filtered)) {
            return trim($text);
        }

        return implode("\n", $filtered);
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
                    $chunkCharset = isset($part->charset) ? (string) $part->charset : '';
                    $chunk = $this->normalizeEncodingToUtf8($chunk, $chunkCharset);
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
