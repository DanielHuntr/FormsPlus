<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\StylesManager;
use Illuminate\Http\Request;
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

    public function save(Request $request)
    {
        $request->validate([
            'css'                => 'nullable|string|max:50000',
            'preview_stylesheet' => 'nullable|string|max:500',
            'tailwind_output'    => 'nullable|boolean',
        ]);

        StylesManager::save($request->only(array_keys(StylesManager::defaults())));

        return response()->json(['success' => true]);
    }
}
