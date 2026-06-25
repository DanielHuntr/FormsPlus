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
            'enabled'                    => 'sometimes|boolean',
            'notification_email'         => 'sometimes|nullable|email|max:255',
            'reply_to_field'             => 'sometimes|nullable|string|max:255',
            'confirmation_email_enabled' => 'sometimes|boolean',
            'confirmation_email_field'   => 'sometimes|nullable|string|max:255',
            'submit_label'               => 'sometimes|nullable|string|max:100',
            'on_submit'                  => 'sometimes|nullable|in:message,redirect',
            'success_title'              => 'sometimes|nullable|string|max:255',
            'success_message'            => 'sometimes|nullable|string|max:2000',
            'redirect_url'               => 'sometimes|nullable|string|max:2000',
        ]);

        // Merge so a partial save (e.g. only submit-button fields) doesn't wipe other keys.
        $existing = SettingsManager::get($handle);
        SettingsManager::save($handle, array_merge($existing, $validated));

        return response()->json(['success' => true, 'message' => 'Settings saved.']);
    }
}
