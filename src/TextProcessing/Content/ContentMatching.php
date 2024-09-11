<?php

namespace Statamic\SeoPro\TextProcessing\Content;

class ContentMatching
{
    public static function isUuidLike(string $value): bool
    {
        return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/i', trim($value));
    }

    public static function isLikelyFilePath(string $value): bool
    {
        if (!str_contains($value, '/') && !str_contains($value, '\\')) {
            return false;
        }

        if (! preg_match('/\.[A-Za-z0-9]+$/', $value)) {
            return false;
        }

        return true;
    }
}