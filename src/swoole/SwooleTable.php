<?php

namespace phpboot\common\swoole;

final class SwooleTable
{
    const COLUMN_TYPE_INT = 1;
    const COLUMN_TYPE_FLOAT = 2;
    const COLUMN_TYPE_STRING = 3;

    /**
     * @var string
     */
    private static $_cacheTableName = 'tblCache';

    /**
     * @var string
     */
    private static $_redisLockTableName = 'tblRedisLock';

    /**
     * @var string
     */
    private static $_ratelimiterTableName = 'tblRatelimiter';

    /**
     * @var string
     */
    private static $_wsTableName = 'tblWsConnections';

    private function __construct()
    {
    }

    public static function cacheTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_cacheTableName = $name;
            return '';
        }

        return self::$_cacheTableName;
    }

    public static function redisLockTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_redisLockTableName = $name;
            return '';
        }

        return self::$_redisLockTableName;
    }

    public static function ratelimiterTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_ratelimiterTableName = $name;
            return '';
        }

        return self::$_ratelimiterTableName;
    }

    public static function wsTableName(?string $name = null): string
    {
        if (is_string($name) && $name !== '') {
            self::$_wsTableName = $name;
            return '';
        }

        return self::$_wsTableName;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function buildTable(array $columns, int $size = 1024): \Swoole\Table
    {
        $table = new \Swoole\Table($size);

        foreach ($columns as $col) {
            list($name, $type, $dataSize) = $col;
            $type = self::parseColumnType($type);

            if (is_int($dataSize) && $dataSize > 0) {
                $table->column($name, $type, $dataSize);
            } else {
                $table->column($name, $type);
            }
        }

        $table->create();
        return $table;
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function getTable(string $name): ?\Swoole\Table
    {
        $server = Swoole::getServer();

        if (!is_object($server) || !property_exists($server, $name)) {
            return null;
        }

        $table = $server->$name;
        return $table instanceof \Swoole\Table ? $table : null;
    }

    public static function exists(string $tableName, string $key): bool
    {
        $table = self::getTable($tableName);
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        return $table instanceof \Swoole\Table ? $table->exist($key) : false;
    }

    public static function remove(string $tableName, string $key): void
    {
        $table = self::getTable($tableName);

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->del($key);
    }

    public static function setValue(string $tableName, string $key, array $value): void
    {
        $table = self::getTable($tableName);

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->set($key, $value);
    }

    public static function incr(string $tableName, string $key, string $columnName, $num): void
    {
        $table = self::getTable($tableName);

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->incr($key, $columnName, $num);
    }

    public static function decr(string $tableName, string $key, string $columnName, $num): void
    {
        $table = self::getTable($tableName);

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!($table instanceof \Swoole\Table)) {
            return;
        }

        $table->decr($key, $columnName, $num);
    }

    public static function getValue(string $tableName, string $key): ?array
    {
        $table = self::getTable($tableName);

        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if (!($table instanceof \Swoole\Table)) {
            return null;
        }

        $value = $table->get($key);
        return is_array($value) ? $value : null;
    }

    private static function parseColumnType(int $type): int
    {
        switch ($type) {
            case self::COLUMN_TYPE_INT:
                /** @noinspection PhpFullyQualifiedNameUsageInspection */
                return \Swoole\Table::TYPE_INT;
            case self::COLUMN_TYPE_FLOAT:
                /** @noinspection PhpFullyQualifiedNameUsageInspection */
                return \Swoole\Table::TYPE_FLOAT;
            default:
                /** @noinspection PhpFullyQualifiedNameUsageInspection */
                return \Swoole\Table::TYPE_STRING;
        }
    }
}
