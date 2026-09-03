<?php

namespace Webparaguay\Schema;

/**
 * Acceso al contrato formal y su versión.
 */
final class Schema
{
    public const VERSION = '0.1';

    public static function path(): string
    {
        return dirname(__DIR__).'/site.schema.json';
    }

    public static function examplePath(): string
    {
        return dirname(__DIR__).'/example-site.json';
    }

    /** @return array<string,mixed> */
    public static function decoded(): array
    {
        return json_decode((string) file_get_contents(self::path()), true, flags: JSON_THROW_ON_ERROR);
    }
}
