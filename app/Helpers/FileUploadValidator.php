<?php
/**
 * Proyecto PRESUPUESTO - Validador reutilizable para carga de archivos.
 */

namespace App\Helpers;

class FileUploadValidator
{
    public static function validate(array $file, array $appConfig)
    {
        $result = array(
            'is_valid' => true,
            'errors' => array(),
            'extension' => '',
            'detected_mime' => '',
            'size_bytes' => 0,
        );

        if (!isset($file['error']) || !isset($file['name']) || !isset($file['tmp_name']) || !isset($file['size'])) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Estructura de archivo invalida.';
            return $result;
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $result['is_valid'] = false;
            $result['errors'][] = 'El archivo no se pudo subir correctamente.';
            return $result;
        }

        $sizeBytes = (int) $file['size'];
        $result['size_bytes'] = $sizeBytes;
        $maxSizeBytes = ((int) $appConfig['files_max_upload_mb']) * 1024 * 1024;
        if ($sizeBytes <= 0 || $sizeBytes > $maxSizeBytes) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Tamano de archivo fuera del limite permitido.';
        }

        $originalName = (string) $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $result['extension'] = $extension;
        if ($extension === '' || !in_array($extension, $appConfig['files_allowed_extensions'], true)) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Extension de archivo no permitida.';
        }

        $detectedMime = self::detectMimeType($file['tmp_name']);
        $result['detected_mime'] = $detectedMime;
        if ($detectedMime === '' || !in_array($detectedMime, $appConfig['files_allowed_mime'], true)) {
            $result['is_valid'] = false;
            $result['errors'][] = 'Tipo MIME de archivo no permitido.';
        }

        return $result;
    }

    private static function detectMimeType($tmpFilePath)
    {
        if (!is_file($tmpFilePath)) {
            return '';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpFilePath);
                finfo_close($finfo);
                if (is_string($mime)) {
                    return trim($mime);
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($tmpFilePath);
            if (is_string($mime)) {
                return trim($mime);
            }
        }

        return '';
    }
}
