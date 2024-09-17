<?php

namespace Statamic\SeoPro;

class SeoPro
{
    protected static bool $isSeoProProcess = false;

    public static function withSeoProFlag(callable $callable): mixed
    {
        self::$isSeoProProcess = true;

        try {
            return $callable();
        } finally {
            self::$isSeoProProcess = false;
        }
    }

    public static function isSeoProProcess(): bool
    {
        return self::$isSeoProProcess;
    }
}
