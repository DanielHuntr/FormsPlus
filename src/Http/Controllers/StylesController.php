<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\StylesManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Statamic\Http\Controllers\CP\CpController;

class StylesController extends CpController
{
    public function showPage()
    {
        return view('forms-plus::styles', [
            'stylesApiUrl' => cp_route('forms-plus.styles.api'),
        ]);
    }

    public function show()
    {
        return response()->json(StylesManager::get());
    }

    public function cssFileContent(Request $request)
    {
        $dir  = realpath(resource_path('css'));
        $path = realpath($dir . DIRECTORY_SEPARATOR . $request->query('file', ''));

        if (! $path || ! str_starts_with($path, $dir . DIRECTORY_SEPARATOR) || ! File::isFile($path)) {
            return response('/* file not found */', 404, ['Content-Type' => 'text/css']);
        }

        return response(File::get($path), 200, ['Content-Type' => 'text/css']);
    }

    public function cssFiles()
    {
        $dir = resource_path('css');

        if (! File::isDirectory($dir)) {
            return response()->json([]);
        }

        $files = collect(File::allFiles($dir))
            ->filter(fn ($f) => $f->getExtension() === 'css')
            ->map(function ($f) use ($dir) {
                $relative = ltrim(str_replace($dir, '', $f->getPathname()), DIRECTORY_SEPARATOR);
                $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
                return [
                    'label' => $relative,
                    'url'   => '/css/' . $relative,
                ];
            })
            ->values();

        return response()->json($files);
    }

    public function save(Request $request)
    {
        $request->validate([
            'css'                => 'nullable|string|max:50000',
            'preview_stylesheet' => 'nullable|string|max:500',
        ]);

        StylesManager::save($request->only(array_keys(StylesManager::defaults())));

        $css = trim($request->input('css', ''));
        StylesManager::writeCssSourceFile($css);

        $importAdded = false;
        $importFile  = null;

        if ($css !== '' && $request->filled('preview_stylesheet')) {
            $targetPath = StylesManager::resolvePreviewStylesheetPath($request->input('preview_stylesheet'));

            if ($targetPath) {
                $importAdded = StylesManager::injectImport($targetPath);
                $importFile  = basename($targetPath);
            }
        }

        return response()->json([
            'success'      => true,
            'import_added' => $importAdded,
            'import_file'  => $importFile,
        ]);
    }
}
