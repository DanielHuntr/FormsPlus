<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\MailSettingsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Statamic\Http\Controllers\CP\CpController;

class MailSettingsController extends CpController
{
    public function show()
    {
        $settings = MailSettingsManager::get();

        // Never send the stored password back to the browser
        $passwordSet = ! empty($settings['password']);
        $settings['password'] = '';
        $settings['password_set'] = $passwordSet;

        return response()->json($settings);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'enabled'      => 'boolean',
            'host'         => 'nullable|string|max:255',
            'port'         => 'nullable|integer|min:1|max:65535',
            'encryption'   => 'nullable|in:tls,ssl,',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:255',
            'from_address' => 'nullable|email|max:255',
            'from_name'    => 'nullable|string|max:255',
        ]);

        // If the user left the password blank, keep the existing stored password
        if (empty($validated['password'])) {
            $existing = MailSettingsManager::get();
            $validated['password'] = $existing['password'] ?? '';
        }

        MailSettingsManager::save($validated);

        return response()->json(['success' => true, 'message' => 'Mail settings saved.']);
    }

    public function test(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
        ]);

        $config = MailSettingsManager::get();

        if (empty($config['host'])) {
            return response()->json(['success' => false, 'message' => 'No SMTP host configured.'], 422);
        }

        try {
            $this->configureMailer($config);

            Mail::mailer('forms_plus')
                ->html('<p>This is a test email from <strong>Forms Plus</strong>. Your mail settings are working correctly.</p>', function ($message) use ($request, $config) {
                    $message
                        ->to($request->to)
                        ->subject('Forms Plus — Test Email')
                        ->from($config['from_address'] ?: $config['username'], $config['from_name'] ?: 'Forms Plus');
                });

            return response()->json(['success' => true, 'message' => 'Test email sent to ' . $request->to]);
        } catch (\Throwable $e) {
            Log::error('[Forms Plus] Test email failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public static function configureMailer(array $config): void
    {
        config([
            'mail.mailers.forms_plus' => [
                'transport'  => 'smtp',
                'host'       => $config['host'],
                'port'       => (int) ($config['port'] ?? 587),
                'encryption' => $config['encryption'] ?: null,
                'username'   => $config['username'],
                'password'   => $config['password'],
                'timeout'    => 15,
            ],
        ]);
    }
}
