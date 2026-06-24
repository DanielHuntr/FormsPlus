<?php

namespace App\FormsPlus\Tags;

use App\FormsPlus\SettingsManager;
use App\FormsPlus\StylesManager;
use Statamic\Facades\Form;
use Statamic\Tags\Tags;

class FormsPlus extends Tags
{
    protected static $handle = 'forms_plus';

    public function index(): string
    {
        $handle = $this->params->get('handle');
        $form   = Form::find($handle);

        if (! $form) {
            return '';
        }

        $settings  = SettingsManager::get($handle);
        $submitted = request()->query('fpsubmitted') === $handle;
        $redirect  = $this->resolveRedirect($handle, $settings, $this->params->get('redirect', ''));

        return view('forms-plus::partials.form', [
            'form'          => $form,
            'fields'        => $this->extractFields($form),
            'actionUrl'     => $this->formActionUrl($handle),
            'redirect'      => $redirect,
            'errorRedirect' => $this->params->get('error_redirect', ''),
            'submitLabel'   => $this->params->get('submit_label') ?: $settings['submit_label'] ?? 'Submit',
            'settings'      => $settings,
            'styles'        => StylesManager::get(),
            'submitted'     => $submitted,
        ])->render();
    }

    private function resolveRedirect(string $handle, array $settings, string $paramOverride): string
    {
        if ($paramOverride) {
            return $paramOverride;
        }

        if ($settings['on_submit'] === 'redirect' && ! empty($settings['redirect_url'])) {
            return SettingsManager::resolveRedirectUrl($settings['redirect_url']);
        }

        // "message" mode — redirect back to this page with a submitted flag
        $pageUrl = request()->url();
        $sep     = str_contains($pageUrl, '?') ? '&' : '?';

        return $pageUrl . $sep . 'fpsubmitted=' . $handle;
    }

    private function extractFields($form): \Illuminate\Support\Collection
    {
        $contents = $form->blueprint()->contents();
        $fields   = collect();

        foreach ($contents['tabs'] ?? [] as $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $fieldData) {
                    if (! isset($fieldData['handle'])) {
                        continue;
                    }

                    $config = $fieldData['field'] ?? [];

                    $fields->push([
                        'handle'             => $fieldData['handle'],
                        'display'            => $config['display'] ?? ucwords(str_replace('_', ' ', $fieldData['handle'])),
                        'type'               => $config['type'] ?? 'text',
                        'input_type'         => $config['input_type'] ?? 'text',
                        'placeholder'        => $config['placeholder'] ?? '',
                        'instructions'       => $config['instructions'] ?? '',
                        'required'           => in_array('required', $config['validate'] ?? []),
                        'width'              => $config['width'] ?? 100,
                        'options'            => $config['options'] ?? [],
                        'multiple'           => $config['multiple'] ?? false,
                        'rows'               => $config['rows'] ?? 3,
                        'allowed_extensions' => $config['allowed_extensions'] ?? [],
                    ]);
                }
            }
        }

        return $fields;
    }

    private function formActionUrl(string $handle): string
    {
        try {
            return route('statamic.forms.submit', $handle);
        } catch (\Exception $e) {
            return url('!/forms/'.$handle);
        }
    }
}
