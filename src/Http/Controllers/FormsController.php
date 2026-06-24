<?php

namespace App\FormsPlus\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Form;
use Statamic\Http\Controllers\CP\CpController;

class FormsController extends CpController
{
    public function index()
    {
        $forms = Form::all()->map(fn ($form) => [
            'handle' => $form->handle(),
            'title' => $form->title(),
            'submissions' => $form->querySubmissions()->count(),
            'edit_url' => cp_route('forms-plus.edit', $form->handle()),
            'delete_url' => cp_route('forms-plus.destroy', $form->handle()),
        ])->values();

        return view('forms-plus::index', compact('forms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'handle' => 'required|string|regex:/^[a-z0-9_]+$/|max:255',
        ]);

        if (Form::find($request->handle)) {
            return response()->json(['error' => 'A form with that handle already exists.'], 422);
        }

        $form = Form::make($request->handle)->title($request->title);
        $form->save();

        return response()->json([
            'redirect' => cp_route('forms-plus.edit', $form->handle()),
        ]);
    }

    public function edit($handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return redirect(cp_route('forms-plus.index'));
        }

        return view('forms-plus::edit', [
            'form' => [
                'handle' => $form->handle(),
                'title' => $form->title(),
            ],
            'fieldsUrl'            => cp_route('forms-plus.fields', $handle),
            'saveUrl'              => cp_route('forms-plus.fields.save', $handle),
            'indexUrl'             => cp_route('forms-plus.index'),
            'submissionsUrl'       => cp_route('forms-plus.submissions', $handle),
            'exportUrl'            => cp_route('forms-plus.submissions.export', $handle),
            'settingsUrl'          => cp_route('forms-plus.settings', $handle),
            'settingsSaveUrl'      => cp_route('forms-plus.settings.save', $handle),
            'emailNotificationUrl' => cp_route('forms-plus.email.get', [$handle, 'notification']),
            'emailConfirmationUrl' => cp_route('forms-plus.email.get', [$handle, 'confirmation']),
        ]);
    }

    public function fields($handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        return response()->json($this->extractFields($form));
    }

    public function saveFields(Request $request, $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $blueprintFields = collect($request->input('fields', []))
            ->map(fn ($field) => [
                'handle' => $field['handle'],
                'field' => $this->fieldToBlueprint($field),
            ])
            ->values()
            ->all();

        $form->blueprint()->setContents([
            'tabs' => [
                'main' => [
                    'sections' => [['fields' => $blueprintFields]],
                ],
            ],
        ])->save();

        return response()->json(['success' => true, 'message' => 'Form saved.']);
    }

    public function listJson()
    {
        return response()->json(
            Form::all()->map(fn ($form) => [
                'handle' => $form->handle(),
                'label'  => $form->title(),
            ])->values()
        );
    }

    public function destroy($handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response()->json(['error' => 'Form not found.'], 404);
        }

        $form->delete();

        return response()->json(['success' => true]);
    }

    private function extractFields($form): array
    {
        $contents = $form->blueprint()->contents();
        $fields = [];

        foreach ($contents['tabs'] ?? [] as $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $fieldData) {
                    if (! isset($fieldData['handle'])) {
                        continue;
                    }

                    $config = $fieldData['field'] ?? [];
                    $options = $config['options'] ?? [];

                    // Normalise options: Statamic stores them as {value: label} keyed array
                    $normalizedOptions = [];
                    foreach ($options as $value => $label) {
                        $normalizedOptions[] = is_array($label)
                            ? $label
                            : ['value' => (string) $value, 'label' => (string) $label];
                    }

                    $fields[] = [
                        'handle' => $fieldData['handle'],
                        'display' => $config['display'] ?? ucwords(str_replace('_', ' ', $fieldData['handle'])),
                        'type' => $config['type'] ?? 'text',
                        'input_type' => $config['input_type'] ?? 'text',
                        'placeholder' => $config['placeholder'] ?? '',
                        'instructions' => $config['instructions'] ?? '',
                        'required' => in_array('required', $config['validate'] ?? []),
                        'width' => $config['width'] ?? 100,
                        'options' => $normalizedOptions,
                        'multiple' => $config['multiple'] ?? false,
                        'rows' => $config['rows'] ?? 3,
                        'character_limit' => $config['character_limit'] ?? null,
                        'allowed_extensions' => $config['allowed_extensions'] ?? [],
                    ];
                }
            }
        }

        return $fields;
    }

    private function fieldToBlueprint(array $field): array
    {
        $config = [
            'type' => $field['type'],
            'display' => $field['display'] ?? '',
            'width' => (int) ($field['width'] ?? 100),
        ];

        if (! empty($field['placeholder'])) {
            $config['placeholder'] = $field['placeholder'];
        }

        if (! empty($field['instructions'])) {
            $config['instructions'] = $field['instructions'];
        }

        if ($field['type'] === 'text' && ! empty($field['input_type']) && $field['input_type'] !== 'text') {
            $config['input_type'] = $field['input_type'];
        }

        if (in_array($field['type'], ['select', 'checkboxes', 'radio'])) {
            $options = [];
            foreach ($field['options'] ?? [] as $opt) {
                $options[$opt['value']] = $opt['label'];
            }
            $config['options'] = $options;
        }

        if ($field['type'] === 'select') {
            $config['multiple'] = $field['multiple'] ?? false;
            $config['clearable'] = false;
        }

        if ($field['type'] === 'textarea') {
            $config['rows'] = (int) ($field['rows'] ?? 3);
        }

        if (! empty($field['character_limit'])) {
            $config['character_limit'] = (int) $field['character_limit'];
        }

        if ($field['type'] === 'files' && ! empty($field['allowed_extensions'])) {
            $config['allowed_extensions'] = $field['allowed_extensions'];
        }

        if ($field['required'] ?? false) {
            $config['validate'] = ['required'];
        }

        return $config;
    }
}
