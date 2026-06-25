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
            'form'         => '',
            'wrapper'      => '',
            'label'        => '',
            'input'        => '',
            'textarea'     => '',
            'select'       => '',
            'checkbox'     => '',
            'radio'        => '',
            'choice_label' => '',
            'button'       => '',
            'error'        => '',
            'hint'         => '',
            'custom_css'          => '',
            'preview_stylesheet'  => '',
        ];
    }
}
