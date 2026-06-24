<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\SettingsManager;
use Illuminate\Http\Request;
use Statamic\Facades\Form;
use Statamic\Http\Controllers\CP\CpController;

class SettingsController extends CpController
{
    public function show(string $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        return response()->json(SettingsManager::get($handle));
    }

    public function save(Request $request, string $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $validated = $request->validate([
            'enabled'                    => 'boolean',
            'submit_label'               => 'nullable|string|max:100',
            'notification_email'         => 'nullable|email|max:255',
            'reply_to_field'             => 'nullable|string|max:255',
            'confirmation_email_enabled' => 'boolean',
            'confirmation_email_field'   => 'nullable|string|max:255',
            'on_submit'                  => 'required|in:message,redirect',
            'success_title'              => 'nullable|string|max:255',
            'success_message'            => 'nullable|string|max:2000',
            'redirect_url'               => 'nullable|string|max:500',
        ]);

        SettingsManager::save($handle, $validated);

        return response()->json(['success' => true, 'message' => 'Settings saved.']);
    }
}
