<?php

namespace App\FormsPlus\Fieldtypes;

use App\FormsPlus\SettingsManager;
use App\FormsPlus\StylesManager;
use Statamic\Facades\Form;
use Statamic\Fields\Fieldtype;

class FormsPlusFieldtype extends Fieldtype
{
    protected static $handle = 'forms_plus';

    public function icon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="2" width="18" height="20" rx="2"/><line x1="7" y1="7" x2="17" y2="7"/><line x1="7" y1="11" x2="17" y2="11"/><line x1="7" y1="15" x2="13" y2="15"/><circle cx="17" cy="17" r="3" fill="currentColor" stroke="none"/><path d="M15.5 17l1 1 2-2" stroke="white" stroke-width="1.2" fill="none"/></svg>';
    }

    public function augment($value)
    {
        if (! $value) {
            return '';
        }

        $form = Form::find($value);

        if (! $form) {
            return '';
        }

        $settings  = SettingsManager::get($value);
        $submitted = request()->query('fpsubmitted') === $value;
        $redirect  = $this->resolveRedirect($value, $settings);

        $contents = $form->blueprint()->contents();
        $fields   = collect();

        foreach ($contents['tabs'] ?? [] as $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $fieldData) {
                    if (! isset($fieldData['handle'])) {
                        continue;
                    }

                    $config  = $fieldData['field'] ?? [];
                    $options = [];
                    foreach ($config['options'] ?? [] as $v => $l) {
                        $options[$v] = $l;
                    }

                    $fields->push([
                        'handle'             => $fieldData['handle'],
                        'display'            => $config['display'] ?? ucwords(str_replace('_', ' ', $fieldData['handle'])),
                        'type'               => $config['type'] ?? 'text',
                        'input_type'         => $config['input_type'] ?? 'text',
                        'placeholder'        => $config['placeholder'] ?? '',
                        'instructions'       => $config['instructions'] ?? '',
                        'required'           => in_array('required', $config['validate'] ?? []),
                        'width'              => $config['width'] ?? 100,
                        'options'            => $options,
                        'multiple'           => $config['multiple'] ?? false,
                        'rows'               => $config['rows'] ?? 3,
                        'allowed_extensions' => $config['allowed_extensions'] ?? [],
                    ]);
                }
            }
        }

        try {
            $actionUrl = route('statamic.forms.submit', $value);
        } catch (\Exception $e) {
            $actionUrl = url('!/forms/'.$value);
        }

        return view('forms-plus::partials.form', [
            'form'          => $form,
            'fields'        => $fields,
            'actionUrl'     => $actionUrl,
            'redirect'      => $redirect,
            'errorRedirect' => '',
            'submitLabel'   => $settings['submit_label'] ?? 'Submit',
            'settings'  => $settings,
            'submitted' => $submitted,
        ])->render();
    }

    private function resolveRedirect(string $handle, array $settings): string
    {
        if ($settings['on_submit'] === 'redirect' && ! empty($settings['redirect_url'])) {
            return SettingsManager::resolveRedirectUrl($settings['redirect_url']);
        }

        $pageUrl = request()->url();
        $sep     = str_contains($pageUrl, '?') ? '&' : '?';

        return $pageUrl . $sep . 'fpsubmitted=' . $handle;
    }
}
