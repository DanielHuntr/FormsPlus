<?php

namespace App\FormsPlus;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

class SettingsManager
{
    protected static array $cache = [];

    protected static function path(string $handle): string
    {
        return storage_path("forms-plus/{$handle}.yaml");
    }

    public static function get(string $handle): array
    {
        if (! isset(static::$cache[$handle])) {
            $path = static::path($handle);

            $saved = File::exists($path)
                ? (YAML::parse(File::get($path)) ?? [])
                : [];

            static::$cache[$handle] = array_merge(static::defaults(), $saved);
        }

        return static::$cache[$handle];
    }

    public static function save(string $handle, array $settings): void
    {
        $path = static::path($handle);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, YAML::dump(array_intersect_key($settings, static::defaults())));

        // Bust cache so the next get() reflects the new values
        unset(static::$cache[$handle]);
    }

    /**
     * Resolve a raw redirect_url value (which may be a Statamic link value
     * like "entry::uuid") to a plain URL string.
     */
    public static function resolveRedirectUrl(string $raw): string
    {
        if (! $raw) {
            return '';
        }

        if (str_starts_with($raw, 'entry::')) {
            $entry = \Statamic\Facades\Entry::find(substr($raw, 7));

            return $entry ? ($entry->url() ?? '') : '';
        }

        // asset:: and @child aren't meaningful as redirect targets — skip them
        if (str_starts_with($raw, 'asset::') || $raw === '@child') {
            return '';
        }

        return $raw;
    }

    public static function defaults(): array
    {
        return [
            'enabled'                           => true,
            'submit_label'                      => 'Submit',
            'notification_email'                => '',
            'notification_subject'              => 'New form submission',
            'reply_to_field'                    => '',
            'on_submit'                         => 'message',
            'success_title'                     => 'Message sent!',
            'success_message'                   => "Thank you for getting in touch. We'll be in touch soon.",
            'redirect_url'                      => '',
            'redirect_query_params'             => [],
            'use_default_notification_template' => true,
            'use_default_confirmation_template' => true,
            'confirmation_email_enabled'        => false,
            'confirmation_email_field'          => '',
        ];
    }
}
