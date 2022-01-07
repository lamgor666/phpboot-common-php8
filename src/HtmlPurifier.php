<?php

namespace phpboot\common;

use HTMLPurifier as Purifier;
use HTMLPurifier_Config as Config;

final class HtmlPurifier
{
    private function __construct()
    {
    }

    /**
     * @param string|array $arg0
     * @return array|int|string
     */
    public static function purify($arg0)
    {
        if (!is_string($arg0) && !is_array($arg0)) {
            return $arg0;
        }

        if (empty($arg0)) {
            return $arg0;
        }

        if (is_array($arg0)) {
            foreach ($arg0 as $key => $item) {
                $arg0[$key] = self::purify($item);
            }

            return $arg0;
        }

        if (is_numeric($arg0)) {
            return $arg0;
        }

        return (new Purifier(Config::createDefault()))->purify($arg0);
    }
}
