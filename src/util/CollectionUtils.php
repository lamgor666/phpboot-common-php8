<?php

namespace phpboot\common\util;

use Illuminate\Support\Collection;

final class CollectionUtils
{
    private function __construct()
    {
    }

    public static function toCollection($arg0): Collection
    {
        if (is_array($arg0)) {
            return collect($arg0);
        }

        if ($arg0 instanceof Collection) {
            return $arg0;
        }

        return collect([]);
    }

    public static function object2array(Collection $list): Collection
    {
        return $list->map(function ($it) {
            return is_array($it) ? $it : get_object_vars($it);
        });
    }

    /**
     * @param Collection $list
     * @param string[]|string $keys
     * @return Collection
     */
    public static function removeKeys(Collection $list, $keys): Collection
    {
        return $list->map(function ($it) use ($keys) {
            return ArrayUtils::removeKeys($it, $keys);
        });
    }
}
