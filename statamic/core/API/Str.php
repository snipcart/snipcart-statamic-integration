<?php

namespace Statamic\API;

use Stringy\StaticStringy;

/**
 * Manipulating strings
 */
class Str extends \Illuminate\Support\Str
{
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([StaticStringy::class, $method], $parameters);
    }

    public static function studlyToSlug($string)
    {
        return Str::slug(Str::snake($string));
    }

    public static function isUrl($string)
    {
        return self::startsWith($string, ['http://', 'https://', '/']);
    }

    public static function deslugify($string)
    {
        return str_replace(['-', '_'], ' ', $string);
    }

    /**
     * Get the human file size of a given file.
     *
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function fileSizeForHumans($bytes, $decimals = 2)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, $decimals) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, $decimals) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, $decimals) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' B';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' B';
        } else {
            $bytes = '0 B';
        }

        return $bytes;
    }
}
