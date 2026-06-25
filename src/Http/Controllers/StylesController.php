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
            'form'         => 'nullable|string|max:2000',
            'wrapper'      => 'nullable|string|max:2000',
            'label'        => 'nullable|string|max:2000',
            'input'        => 'nullable|string|max:2000',
            'textarea'     => 'nullable|string|max:2000',
            'select'       => 'nullable|string|max:2000',
            'checkbox'     => 'nullable|string|max:2000',
            'radio'        => 'nullable|string|max:2000',
            'choice_label' => 'nullable|string|max:2000',
            'button'       => 'nullable|string|max:2000',
            'error'        => 'nullable|string|max:2000',
            'hint'         => 'nullable|string|max:2000',
            'custom_css'         => 'nullable|string|max:20000',
            'preview_stylesheet' => 'nullable|url|max:500',
        ]);

        StylesManager::save($request->only(array_keys(StylesManager::defaults())));

        return response()->json(['success' => true]);
    }
}
