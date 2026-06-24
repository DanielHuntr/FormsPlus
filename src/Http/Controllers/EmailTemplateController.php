<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\SettingsManager;
use App\FormsPlus\TemplateManager;
use App\FormsPlus\TemplateRenderer;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class EmailTemplateController extends CpController
{
    public function showDefaults()
    {
        return view('forms-plus::email-templates', [
            'emailNotificationUrl' => cp_route('forms-plus.email-templates.get', 'notification'),
            'emailConfirmationUrl' => cp_route('forms-plus.email-templates.get', 'confirmation'),
        ]);
    }

    public function getTemplate(string $type)
    {
        $this->validateType($type);

        return response()->json([
            'template' => TemplateManager::get($type),
        ]);
    }

    public function saveTemplate(Request $request, string $type)
    {
        $this->validateType($type);
        $request->validate([
            'template'         => 'required|array',
            'template.subject' => 'required|string|max:500',
            'template.blocks'  => 'required|array',
        ]);

        TemplateManager::save($type, $request->input('template'));

        return response()->json(['success' => true]);
    }

    public function getFormTemplate(string $handle, string $type)
    {
        $this->validateType($type);

        $settings   = SettingsManager::get($handle);
        $useDefault = (bool) ($settings["use_default_{$type}_template"] ?? true);

        return response()->json([
            'template'    => TemplateManager::getRaw($type, $handle),
            'use_default' => $useDefault,
        ]);
    }

    public function saveFormTemplate(Request $request, string $handle, string $type)
    {
        $this->validateType($type);
        $request->validate([
            'template'         => 'required|array',
            'template.subject' => 'required|string|max:500',
            'template.blocks'  => 'required|array',
            'use_default'      => 'boolean',
        ]);

        $useDefault = (bool) $request->input('use_default', true);

        TemplateManager::save($type, $request->input('template'), $handle);

        $settings = SettingsManager::get($handle);
        $settings["use_default_{$type}_template"] = $useDefault;
        SettingsManager::save($handle, $settings);

        return response()->json(['success' => true]);
    }

    public function preview(Request $request)
    {
        $request->validate(['template' => 'required|array']);

        $renderer = new TemplateRenderer();
        $html     = $renderer->render(
            $request->input('template'),
            [
                'name'    => 'Jane Smith',
                'email'   => 'jane@example.com',
                'message' => 'This is a preview of how your email will look with real submission data.',
            ],
            [
                'form_title'    => 'Contact Us',
                'site_name'     => config('app.name'),
                'submitted_at'  => now()->format('d M Y, H:i'),
                'submission_id' => 'preview-001',
            ]
        );

        return response()->json(['html' => $html]);
    }

    private function validateType(string $type): void
    {
        if (! in_array($type, ['notification', 'confirmation'])) {
            abort(404);
        }
    }
}
