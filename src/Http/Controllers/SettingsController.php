<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\SettingsManager;
use Illuminate\Http\Request;
use Statamic\Facades\Form;
use Statamic\Fields\Field;
use Statamic\Http\Controllers\CP\CpController;

class SettingsController extends CpController
{
    public function show(string $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $settings = SettingsManager::get($handle);

        // Attach the link fieldtype meta so the Vue component can hydrate
        // the picker (e.g. show the entry title when an entry:: value is stored)
        $settings['redirect_url_meta'] = $this->linkMeta($settings['redirect_url'] ?? null);

        return response()->json($settings);
    }

    private function linkMeta(?string $value): array
    {
        $field = new Field('redirect_url', [
            'type'        => 'link',
            'collections' => [],
        ]);

        $field->setValue($value ?: null);

        return $field->fieldtype()->preload();
    }

    public function save(Request $request, string $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $validated = $request->validate([
            'enabled'              => 'boolean',
            'submit_label'         => 'nullable|string|max:100',
            'notification_email'   => 'nullable|email|max:255',
            'notification_subject' => 'nullable|string|max:255',
            'reply_to_field'       => 'nullable|string|max:255',
            'on_submit'            => 'required|in:message,redirect',
            'success_title'        => 'nullable|string|max:255',
            'success_message'      => 'nullable|string|max:2000',
            'redirect_url'         => 'nullable|string|max:500',
        ]);

        SettingsManager::save($handle, $validated);

        return response()->json(['success' => true, 'message' => 'Settings saved.']);
    }
}
