<?php

namespace App\FormsPlus\Http\Controllers;

use App\FormsPlus\SettingsManager;
use Illuminate\Routing\Controller;

class HandleRedirectController extends Controller
{
    public function __invoke(string $handle)
    {
        $settings    = SettingsManager::get($handle);
        $redirectUrl = SettingsManager::resolveRedirectUrl($settings['redirect_url'] ?? '');
        $params      = $settings['redirect_query_params'] ?? [];

        if ($redirectUrl && ! empty($params)) {
            $submission = session('submission');
            if (is_array($submission)) {
                $data = $submission;
            } elseif (is_object($submission) && method_exists($submission, 'data')) {
                $value = $submission->data();
                $data  = is_array($value) ? $value : $value->all();
            } else {
                $data = [];
            }

            $parts = [];
            foreach ($params as $param) {
                $key   = trim($param['key'] ?? '');
                $value = $param['value'] ?? '';
                if (! $key) {
                    continue;
                }
                $value = preg_replace_callback('/\{\{([\w-]+)\}\}/', function ($m) use ($data) {
                    return $data[$m[1]] ?? '';
                }, $value);
                $parts[] = urlencode($key).'='.urlencode($value);
            }

            if ($parts) {
                $sep = str_contains($redirectUrl, '?') ? '&' : '?';
                $redirectUrl .= $sep.implode('&', $parts);
            }
        }

        return $redirectUrl
            ? redirect()->away($redirectUrl)
            : redirect('/');
    }
}
