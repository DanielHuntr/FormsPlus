<?php

namespace App\FormsPlus;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

class MailSettingsManager
{
    protected static ?array $cache = null;

    protected static function path(): string
    {
        return storage_path('forms-plus/mail.yaml');
    }

    public static function get(): array
    {
        if (static::$cache === null) {
            $path = static::path();

            $saved = File::exists($path)
                ? (YAML::parse(File::get($path)) ?? [])
                : [];

            static::$cache = array_merge(static::defaults(), $saved);
        }

        return static::$cache;
    }

    public static function save(array $settings): void
    {
        $path = static::path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, YAML::dump(array_intersect_key($settings, static::defaults())));
        static::$cache = null;
    }

    public static function isConfigured(): bool
    {
        $config = static::get();

        return ! empty($config['enabled'])
            && ! empty($config['host'])
            && ! empty($config['username'])
            && ! empty($config['from_address']);
    }

    public static function defaults(): array
    {
        return [
            'enabled'      => false,
            'host'         => '',
            'port'         => 587,
            'encryption'   => 'tls',
            'username'     => '',
            'password'     => '',
            'from_address' => '',
            'from_name'    => '',
        ];
    }
}
