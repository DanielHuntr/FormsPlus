<?php

namespace App\FormsPlus\Listeners;

use Illuminate\Http\Exceptions\HttpResponseException;
use Statamic\Events\FormSubmitting;

class RejectHoneypotSubmission
{
    public function handle(FormSubmitting $event): void
    {
        if (filled(request('fp_website'))) {
            throw new HttpResponseException(redirect()->back());
        }
    }
}
