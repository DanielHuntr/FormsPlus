<?php

namespace App\FormsPlus;

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

class StylesManager
{
    protected static function path(): string
    {
        return storage_path('forms-plus/styles.yaml');
    }

    public static function get(): array
    {
        $path = static::path();

        $saved = File::exists($path)
            ? (YAML::parse(File::get($path)) ?? [])
            : [];

        return array_merge(static::defaults(), $saved);
    }

    public static function save(array $styles): void
    {
        $path = static::path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, YAML::dump(array_intersect_key($styles, static::defaults())));
    }

    public static function defaults(): array
    {
        return [
            'css'                => '',
            'preview_stylesheet' => '',
            'tailwind_output'    => false,
        ];
    }

    public static function renderStyles(): string
    {
        if (request()->attributes->get('fp_styles_rendered')) {
            return '';
        }
        request()->attributes->set('fp_styles_rendered', true);

        $styles = static::get();
        $css    = trim($styles['css'] ?? '');

        if ($css === '') {
            return '';
        }

        $tag = ($styles['tailwind_output'] ?? false) ? 'text/tailwindcss' : null;

        return $tag
            ? '<style type="' . $tag . '">' . $css . '</style>'
            : '<style>' . $css . '</style>';
    }
}
