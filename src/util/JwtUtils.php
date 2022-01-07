<?php

namespace phpboot\common\util;

use DateTime;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use phpboot\common\Cast;
use Throwable;

final class JwtUtils
{
    private function __construct()
    {
    }

    public static function getPublicKey(string $pemFilepath): Key
    {
        return Key\InMemory::file($pemFilepath);
    }

    public static function getPrivateKey(string $pemFilepath): Key
    {
        return Key\InMemory::file($pemFilepath);
    }

    public static function verify(Token $jwt, string $issuer): array
    {
        if (!$jwt->hasBeenIssuedBy($issuer)) {
            return [false, -1];
        }

        if ($jwt->isExpired(new DateTime())) {
            return [false, -2];
        }

        return [true, 0];
    }

    public static function intClaim(Token|string $arg0, string $name, int $default = PHP_INT_MIN): int
    {
        return Cast::toInt(self::claim($arg0, $name), $default);
    }

    public static function floatClaim(Token|string $arg0, string $name, float $default = PHP_FLOAT_MIN): float
    {
        return Cast::toFloat(self::claim($arg0, $name), $default);
    }

    public static function booleanClaim(Token|string $arg0, string $name, bool $default = false): bool
    {
        return Cast::toBoolean(self::claim($arg0, $name), $default);
    }

    public static function stringClaim(Token|string $arg0, string $name, string $default = ''): string
    {
        return Cast::toString(self::claim($arg0, $name), $default);
    }

    public static function arrayClaim(Token|string $arg0, string $name): array
    {
        $ret = self::claim($arg0, $name);
        return is_array($ret) ? $ret : [];
    }

    private static function claim(Token|string $arg0, string $name)
    {
        $jwt = null;

        if ($arg0 instanceof Token) {
            $jwt = $arg0;
        } else if (is_string($arg0) && $arg0 !== '') {
            try {
                $jwt = (new Parser(new JoseEncoder()))->parse($arg0);
            } catch (Throwable) {
                $jwt = null;
            }
        }

        if (!($jwt instanceof Token)) {
            return null;
        }

        try {
            return $jwt->claims()->get($name);
        } catch (Throwable) {
            return null;
        }
    }
}
