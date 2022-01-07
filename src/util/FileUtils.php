<?php

namespace phpboot\common\util;

use Dflydev\ApacheMimeTypes\Parser;

final class FileUtils
{
    private function __construct()
    {
    }

    public static function scanFiles(string $dir, array &$list): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $dir = str_replace("\\", '/', $dir);
        }

        $entries = scandir($dir);

        if (!is_array($entries) || empty($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fpath = "$dir/$entry";

            if (is_dir($fpath)) {
                self::scanFiles($fpath, $list);
                continue;
            }

            array_push($list, $fpath);
        }
    }

    public static function getExtension(string $filepath): string
    {
        if (strpos($filepath, '.') === false) {
            return '';
        }

        return strtolower(StringUtils::substringAfterLast($filepath, '.'));
    }

    public static function getMimeType(string $filepath, bool $strictMode = false): string
    {
        if (!$strictMode) {
            return self::getMimeTypeByExtension(self::getExtension($filepath));
        }

        if (!extension_loaded('fileinfo')) {
            return '';
        }

        if (!is_file($filepath)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME);

        if ($finfo === false) {
            return '';
        }

        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        if (empty($mimeType)) {
            return '';
        }

        return strpos($mimeType, ';') !== false ? StringUtils::substringBefore($mimeType, ';') : $mimeType;
    }

    public static function getRealpath(string $path): string
    {
        if (StringUtils::startsWith($path, 'classpath:') ||
            StringUtils::startsWith($path, '@ProjectRoot:') ||
            StringUtils::startsWith($path, '@AppRoot:')) {
            $s1 = StringUtils::substringAfter($path, ':');
            $s1 = trim($s1);
        } else {
            return $path;
        }

        $rootPath = self::getRootPath();

        if ($rootPath === '' || $rootPath === '/') {
            return StringUtils::ensureLeft($s1, '/');
        }

        return rtrim($rootPath, '/') . StringUtils::ensureLeft($s1, '/');
    }

    private static function getMimeTypeByExtension(string $fileExt): string
    {
        if (empty($fileExt)) {
            return '';
        }

        $parser = new Parser();
        $mineTypesFile = __DIR__ . '/mime.types';
        $map1 = $parser->parse($mineTypesFile);

        foreach ($map1 as $mimeType => $extensions) {
            if (in_array($fileExt, $extensions)) {
                return $mimeType;
            }
        }

        return '';
    }

    private static function getRootPath(): string
    {
        if (defined('_ROOT_')) {
            $dir = _ROOT_;

            if (is_dir($dir)) {
                $dir = str_replace("\\", '/', $dir);
                return $dir === '/' ? $dir : rtrim($dir, '/');
            }
        }

        $dir = __DIR__;

        if (!is_dir($dir)) {
            return '';
        }

        while (true) {
            $dir = str_replace("\\", '/', $dir);

            if ($dir !== '/') {
                $dir = trim($dir, '/');
            }

            if (StringUtils::endsWith($dir, '/vendor')) {
                break;
            }

            $dir = realpath("$dir/../");

            if (!is_string($dir) || $dir === '' || !is_dir($dir)) {
                return '';
            }
        }

        $dir = str_replace("\\", '/', $dir);

        if ($dir !== '/') {
            $dir = trim($dir, '/');
        }

        $dir = realpath("$dir/../");

        if (!is_string($dir) || $dir === '' || !is_dir($dir)) {
            return '';
        }

        $dir = str_replace("\\", '/', $dir);
        return $dir === '/' ? $dir : rtrim($dir, '/');
    }
}
