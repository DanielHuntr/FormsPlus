<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\SettingsManager;
use Illuminate\Routing\Controller;
use Statamic\Facades\Form;

class PublicFormController extends Controller
{
    public function render(string $handle)
    {
        $form = Form::find($handle);

        if (! $form) {
            return response('', 404);
        }

        $settings  = SettingsManager::get($handle);
        $submitted = request()->query('fpsubmitted') === $handle;

        // Determine redirect URL for "message" mode
        // The Vue component passes its page URL so we can redirect back to it correctly
        $redirect = $this->resolveRedirect($handle, $settings);

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
            $actionUrl = route('statamic.forms.submit', $handle);
        } catch (\Exception $e) {
            $actionUrl = url('!/forms/'.$handle);
        }

        return view('forms-plus::partials.form', [
            'form'          => $form,
            'fields'        => $fields,
            'actionUrl'     => $actionUrl,
            'redirect'      => $redirect,
            'errorRedirect' => request('error_redirect', ''),
            'submitLabel'   => request('submit_label') ?: $settings['submit_label'] ?? 'Submit',
            'settings'  => $settings,
            'submitted' => $submitted,
        ]);
    }

    private function resolveRedirect(string $handle, array $settings): string
    {
        if ($settings['on_submit'] === 'redirect' && ! empty($settings['redirect_url'])) {
            return SettingsManager::resolveRedirectUrl($settings['redirect_url']);
        }

        // "message" mode — the Vue component passes its page URL as ?page_url=...
        $pageUrl = request()->query('page_url');

        if ($pageUrl) {
            $sep = str_contains($pageUrl, '?') ? '&' : '?';
            return $pageUrl . $sep . 'fpsubmitted=' . $handle;
        }

        // Fallback: use the HTTP referer
        $referer = request()->header('Referer', '');
        if ($referer) {
            $sep = str_contains($referer, '?') ? '&' : '?';
            return $referer . $sep . 'fpsubmitted=' . $handle;
        }

        return '?fpsubmitted=' . $handle;
    }
}
