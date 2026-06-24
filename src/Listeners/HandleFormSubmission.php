<?php

namespace App\FormsPlus\Listeners;

use App\FormsPlus\SettingsManager;
use App\FormsPlus\TemplateManager;
use App\FormsPlus\TemplateRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Statamic\Events\FormSubmitted;

class HandleFormSubmission
{
    public function handle(FormSubmitted $event): void
    {
        $submission = $event->submission;
        $handle     = $submission->form()->handle();
        $settings   = SettingsManager::get($handle);
        $data       = $submission->data()->all();
        $formTitle  = $submission->form()->title();

        $vars = [
            'form_title'    => $formTitle,
            'site_name'     => config('app.name'),
            'submitted_at'  => $submission->date()->format('d M Y, H:i'),
            'submission_id' => $submission->id(),
        ];

        if (! empty($settings['notification_email'])) {
            $this->sendNotification($settings, $handle, $data, $vars);
        }

        if (! empty($settings['confirmation_email_enabled']) && ! empty($settings['confirmation_email_field'])) {
            $submitterEmail = $data[$settings['confirmation_email_field']] ?? null;
            if ($submitterEmail && filter_var($submitterEmail, FILTER_VALIDATE_EMAIL)) {
                $this->sendConfirmation($handle, $data, $vars, $submitterEmail);
            }
        }
    }

    private function sendNotification(array $settings, string $handle, array $data, array $vars): void
    {
        $template = TemplateManager::get('notification', $handle);
        $renderer = new TemplateRenderer();
        $html     = $renderer->render($template, $data, $vars);
        $subject  = $renderer->interpolateSubject($template['subject'] ?? 'New submission');

        $replyTo = null;
        if (! empty($settings['reply_to_field'])) {
            $candidate = $data[$settings['reply_to_field']] ?? null;
            if ($candidate && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $replyTo = $candidate;
            }
        }

        try {
            Mail::html($html, function ($message) use ($settings, $subject, $replyTo) {
                $message->to($settings['notification_email'])->subject($subject);
                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[Forms Plus] Failed to send notification email: ' . $e->getMessage());
        }
    }

    private function sendConfirmation(string $handle, array $data, array $vars, string $submitterEmail): void
    {
        $template = TemplateManager::get('confirmation', $handle);
        $renderer = new TemplateRenderer();
        $html     = $renderer->render($template, [], $vars);
        $subject  = $renderer->interpolateSubject($template['subject'] ?? 'Thank you for your message');

        try {
            Mail::html($html, function ($message) use ($submitterEmail, $subject) {
                $message->to($submitterEmail)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('[Forms Plus] Failed to send confirmation email: ' . $e->getMessage());
        }
    }
}
