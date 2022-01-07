<?php

namespace phpboot\common\util;

final class TokenizeUtils
{
    private function __construct()
    {
    }

    public static function getQualifiedClassName(array $tokens): string
    {
        $namespace = self::getNamespace($tokens);
        $className = self::getSimpleClassName($tokens);

        if (empty($className)) {
            return '';
        }

        if (empty($namespace)) {
            return StringUtils::ensureLeft($className, "\\");
        }

        return StringUtils::ensureLeft($namespace, "\\") . StringUtils::ensureLeft($className, "\\");
    }

    public static function getNamespace(array $tokens): string
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getNamespacePhp7($tokens);
        }

        $n1 = -1;

        foreach ($tokens as $token) {
            if (!self::isToken($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $n1 = $token[2];
                break;
            }
        }

        if ($n1 < 0) {
            return '';
        }

        foreach ($tokens as $token) {
            if (!self::isToken($token)) {
                continue;
            }

            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            if ($token[0] === T_NAME_QUALIFIED && $token[2] === $n1) {
                return $token[1];
            }
        }

        return '';
    }

    private static function getNamespacePhp7(array $tokens): string
    {
        $n1 = -1;
        $idx = -1;
        $sb = [];

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $n1 = $token[2];
                $idx = $i;
                break;
            }
        }

        if ($n1 < 0) {
            return '';
        }

        $cnt = count($tokens);

        for ($i = $idx + 1; $i < $cnt; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[2] > $n1) {
                break;
            }

            if ($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR) {
                $sb[] = $token[1];
            }
        }

        return empty($sb) ? '' : implode('', $sb);
    }

    public static function getUsedClasses(array $tokens): array
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getUsedClassesPhp7($tokens);
        }

        $nums = [];

        foreach ($tokens as $token) {
            if (!self::isToken($token)) {
                continue;
            }

            if ($token[0] !== T_USE) {
                continue;
            }

            $nums[] = $token[2];
        }

        if (empty($nums)) {
            return [];
        }

        $classes = [];

        foreach ($tokens as $token) {
            if (!self::isToken($token)) {
                continue;
            }

            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            if ($token[0] !== T_NAME_QUALIFIED || !in_array($token[2], $nums)) {
                continue;
            }

            $classes[] = $token[1];
        }

        return $classes;
    }

    private static function getUsedClassesPhp7(array $tokens): array
    {
        $lineNumbers = [];
        $classes = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] !== T_USE) {
                continue;
            }

            $lineNumbers[] = (int) $token[2];
        }

        foreach ($lineNumbers as $lineNumber) {
            $sb = [];

            foreach ($tokens as $token) {
                if (!is_array($token) || $token[2] !== $lineNumber) {
                    continue;
                }

                if (!in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    continue;
                }

                $sb[] = $token[1];
            }

            if (empty($sb)) {
                continue;
            }

            $classes[] = StringUtils::ensureLeft(implode('', $sb), "\\");
        }

        return $classes;
    }

    private static function getSimpleClassName(array $tokens): string
    {
        if (version_compare(PHP_VERSION, '8.0.0') === -1) {
            return self::getSimpleClassNamePhp7($tokens);
        }

        $n1 = -1;

        foreach ($tokens as $token) {
            if (!self::isToken($token)) {
                continue;
            }

            if ($token[0] === T_CLASS) {
                $n1 = $token[2];
                break;
            }
        }

        if ($n1 < 0) {
            return '';
        }

        foreach ($tokens as $token) {
            if (!self::isToken($token)) {
                continue;
            }

            if ($token[0] === T_STRING && $token[2] === $n1) {
                return $token[1];
            }
        }

        return '';
    }

    private static function getSimpleClassNamePhp7(array $tokens): string
    {
        $n1 = -1;
        $idx = -1;

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_CLASS) {
                $n1 = $token[2];
                $idx = $i;
                break;
            }
        }

        if ($n1 < 0) {
            return '';
        }

        $cnt = count($tokens);
        $className = '';

        for ($i = $idx + 1; $i < $cnt; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[2] === $n1 && $token[0] === T_STRING) {
                $className = $token[1];
                break;
            }
        }

        return $className;
    }

    private static function isToken($arg0): bool
    {
        return is_array($arg0) && count($arg0) >= 3 && is_int($arg0[0]) && is_string($arg0[1]) && is_int($arg0[2]);
    }
}
