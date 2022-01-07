<?php

namespace phpboot\common\swoole;

use phpboot\common\Cast;
use Throwable;

final class Swoole
{
    private static $server = null;

    private function __construct()
    {
    }

    public static function setServer($server): void
    {
        self::$server = $server;
    }

    public static function getServer()
    {
        return self::$server;
    }

    public static function withTable($server, string $tableName): void
    {
        if (!is_object($server) || $tableName === '') {
            return;
        }

        switch ($tableName) {
            case SwooleTable::cacheTableName():
                $columns = [
                    ['value', SwooleTable::COLUMN_TYPE_STRING, 2 * 1024 * 1024],
                    ['expiry', SwooleTable::COLUMN_TYPE_INT]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns);
                } catch (Throwable) {
                }

                break;
            case SwooleTable::redisLockTableName():
                $columns = [
                    ['contents', SwooleTable::COLUMN_TYPE_STRING, 16]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns, 2048);
                } catch (Throwable) {
                }

                break;
            case SwooleTable::ratelimiterTableName():
                $columns = [
                    ['total', SwooleTable::COLUMN_TYPE_INT],
                    ['remaining', SwooleTable::COLUMN_TYPE_INT],
                    ['resetAt', SwooleTable::COLUMN_TYPE_STRING, 16],
                    ['createAt', SwooleTable::COLUMN_TYPE_STRING, 16]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns, 2048);
                } catch (Throwable) {
                }

                break;
            case SwooleTable::wsTableName():
                $columns = [
                    ['id', SwooleTable::COLUMN_TYPE_STRING, 16],
                    ['source', SwooleTable::COLUMN_TYPE_STRING, 16],
                    ['jwtClaims', SwooleTable::COLUMN_TYPE_STRING, 1024],
                    ['lastPongAt', SwooleTable::COLUMN_TYPE_STRING, 16],
                    ['createAt', SwooleTable::COLUMN_TYPE_STRING, 16]
                ];

                try {
                    $server->$tableName = SwooleTable::buildTable($columns, 40960);
                } catch (Throwable) {
                }

                break;
        }
    }

    public static function isSwooleHttpRequest($arg0): bool
    {
        if (!is_object($arg0)) {
            return false;
        }

        return str_contains(get_class($arg0), "Swoole\\Http\\Request");
    }

    public static function isSwooleHttpResponse($arg0): bool
    {
        if (!is_object($arg0)) {
            return false;
        }

        return str_contains(get_class($arg0), "Swoole\\Http\\Response");
    }

    public static function getWorkerId(): int
    {
        $server = self::$server;

        if (!is_object($server) || !property_exists($server, 'worker_id')) {
            return -1;
        }

        $workerId = Cast::toInt($server->worker_id);
        return $workerId >= 0 ? $workerId : -1;
    }

    public static function inTaskWorker(): bool
    {
        $workerId = self::getWorkerId();

        if ($workerId < 0) {
            return false;
        }

        $server = self::$server;

        if (!is_object($server) || !property_exists($server, 'taskworker')) {
            return false;
        }

        return Cast::toBoolean($server->taskworker);
    }

    public static function getCoroutineId(): int
    {
        try {
            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            $cid = \Swoole\Coroutine::getCid();
            return is_int($cid) && $cid >= 0 ? $cid : -1;
        } catch (Throwable) {
            return -1;
        }
    }

    public static function inCoroutineMode(bool $notTaskWorker = false): bool
    {
        if (self::getCoroutineId() < 0) {
            return false;
        }

        return !$notTaskWorker || !self::inTaskWorker();
    }

    public static function buildGlobalVarKey(?int $workerId = null, bool $notTaskWorker = true): string
    {
        if ($notTaskWorker && !self::inCoroutineMode(true)) {
            return 'noworker';
        }

        if (!is_int($workerId) || $workerId < 0) {
            $workerId = self::getWorkerId();
        }

        return $workerId >= 0 ? "worker$workerId" : 'noworker';
    }
}
