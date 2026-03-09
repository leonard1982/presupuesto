<?php
/**
 * Proyecto PRESUPUESTO - Lector simple de variables de entorno.
 */

namespace App\Core;

class Environment
{
    public static function load($environmentFilePath)
    {
        if (!is_file($environmentFilePath)) {
            return;
        }

        $lines = file($environmentFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '' || strpos($trimmedLine, '#') === 0) {
                continue;
            }

            $position = strpos($trimmedLine, '=');
            if ($position === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $position));
            $value = trim(substr($trimmedLine, $position + 1));

            if ($key === '') {
                continue;
            }

            $value = self::stripQuotes($value);
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public static function get($key, $defaultValue = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $defaultValue;
        }

        return $value;
    }

    public static function getBoolean($key, $defaultValue = false)
    {
        $value = strtolower((string) self::get($key, $defaultValue ? 'true' : 'false'));
        return in_array($value, array('1', 'true', 'yes', 'on'), true);
    }

    public static function getInteger($key, $defaultValue = 0)
    {
        $value = self::get($key, null);
        if ($value === null || !is_numeric($value)) {
            return (int) $defaultValue;
        }

        return (int) $value;
    }

    public static function getCsv($key, array $defaultValues = array())
    {
        $value = self::get($key, null);
        if ($value === null || trim($value) === '') {
            return $defaultValues;
        }

        $segments = explode(',', $value);
        $cleanValues = array();

        foreach ($segments as $segment) {
            $trimmedSegment = trim($segment);
            if ($trimmedSegment !== '') {
                $cleanValues[] = $trimmedSegment;
            }
        }

        if (empty($cleanValues)) {
            return $defaultValues;
        }

        return $cleanValues;
    }

    private static function stripQuotes($value)
    {
        $length = strlen($value);
        if ($length < 2) {
            return $value;
        }

        $firstCharacter = $value[0];
        $lastCharacter = $value[$length - 1];

        if (($firstCharacter === '"' && $lastCharacter === '"') || ($firstCharacter === "'" && $lastCharacter === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
