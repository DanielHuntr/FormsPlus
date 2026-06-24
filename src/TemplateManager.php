<?php

namespace App\FormsPlus;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

class TemplateManager
{
    protected static function path(string $name): string
    {
        return storage_path("forms-plus/templates/{$name}.yaml");
    }

    /**
     * Load a template. For per-form templates, falls back to the default
     * if use_default is true or no custom template exists.
     *
     * @param  string       $type   'notification' | 'confirmation'
     * @param  string|null  $handle  Form handle, or null for the global default
     */
    public static function get(string $type, ?string $handle = null): array
    {
        if ($handle) {
            $settings   = SettingsManager::get($handle);
            $useDefault = $settings["use_default_{$type}_template"] ?? true;

            if (! $useDefault) {
                $path = static::path("{$handle}-{$type}");
                if (File::exists($path)) {
                    return YAML::parse(File::get($path)) ?? static::builtIn($type);
                }
            }
        }

        // Global default
        $path = static::path("default-{$type}");

        return File::exists($path)
            ? (YAML::parse(File::get($path)) ?? static::builtIn($type))
            : static::builtIn($type);
    }

    /**
     * Load the raw per-form template, falling back to global default (not use_default flag).
     * Used by the CP editor to pre-populate the form's custom template editor.
     */
    public static function getRaw(string $type, string $handle): array
    {
        $path = static::path("{$handle}-{$type}");
        if (File::exists($path)) {
            return YAML::parse(File::get($path)) ?? static::builtIn($type);
        }

        // Pre-populate from global default so the editor has content to start with
        $defaultPath = static::path("default-{$type}");

        return File::exists($defaultPath)
            ? (YAML::parse(File::get($defaultPath)) ?? static::builtIn($type))
            : static::builtIn($type);
    }

    /**
     * Save a template to disk.
     */
    public static function save(string $type, array $template, ?string $handle = null): void
    {
        $name = $handle ? "{$handle}-{$type}" : "default-{$type}";
        $path = static::path($name);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, YAML::dump($template));
    }

    /**
     * Hard-coded fallback templates (used before the user has customised anything).
     */
    public static function builtIn(string $type): array
    {
        if ($type === 'notification') {
            return [
                'subject' => 'New submission: {{form_title}}',
                'blocks'  => [
                    ['type' => 'heading', 'text' => '{{form_title}}', 'level' => 'h2', 'align' => 'left', 'color' => '#18181b'],
                    ['type' => 'text', 'content' => 'A new submission was received on {{submitted_at}}.', 'align' => 'left', 'color' => '#374151'],
                    ['type' => 'submission_data', 'title' => 'Submission Details', 'show_title' => true],
                    ['type' => 'footer', 'content' => 'Sent by Forms Plus · {{site_name}}', 'align' => 'center', 'color' => '#a1a1aa'],
                ],
            ];
        }

        return [
            'subject' => 'Thank you for your message',
            'blocks'  => [
                ['type' => 'heading', 'text' => 'Thank you!', 'level' => 'h2', 'align' => 'left', 'color' => '#18181b'],
                ['type' => 'text', 'content' => "We've received your message and will get back to you soon.", 'align' => 'left', 'color' => '#374151'],
                ['type' => 'divider', 'color' => '#e4e4e7'],
                ['type' => 'footer', 'content' => "You're receiving this because you submitted a form on {{site_name}}.", 'align' => 'center', 'color' => '#a1a1aa'],
            ],
        ];
    }
}
