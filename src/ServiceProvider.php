<?php

namespace App\FormsPlus;

use App\FormsPlus\Fieldtypes\FormsPlusFieldtype;
use App\FormsPlus\Listeners\HandleFormSubmission;
use App\FormsPlus\Listeners\RejectHoneypotSubmission;
use App\FormsPlus\Tags\FormsPlus;
use Illuminate\Support\Facades\Event;
use Statamic\Events\FormSubmitted;
use Statamic\Events\FormSubmitting;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Form;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        FormsPlus::class,
    ];

    protected $fieldtypes = [
        FormsPlusFieldtype::class,
    ];

    protected $scripts = [
        __DIR__.'/../dist/cp.js',
    ];

    protected $stylesheets = [
        __DIR__.'/../dist/cp.css',
    ];

    protected $routes = [
        'cp'  => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    public function bootAddon(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forms-plus');

        Event::listen(FormSubmitting::class, RejectHoneypotSubmission::class);
        Event::listen(FormSubmitted::class, HandleFormSubmission::class);

        Nav::extend(function ($nav) {
            $nav->tools('Forms Plus')
                ->route('forms-plus.index')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>')
                ->children([
                    $nav->item('All Forms')->route('forms-plus.index'),
                    $nav->item('Email Templates')->route('forms-plus.email-templates'),
                    $nav->item('Theme')->route('forms-plus.theme'),
                    $nav->item('Mail Settings')->route('forms-plus.mail-settings'),
                ]);
        });

        $this->createDemoFormIfNeeded();
    }

    private function createDemoFormIfNeeded(): void
    {
        if (app()->runningInConsole() || Form::find('contact')) {
            return;
        }

        $form = Form::make('contact')->title('Contact Us');
        $form->save();

        $form->blueprint()->setContents([
            'tabs' => [
                'main' => [
                    'sections' => [[
                        'fields' => [
                            [
                                'handle' => 'name',
                                'field' => [
                                    'type'       => 'text',
                                    'display'    => 'Full Name',
                                    'input_type' => 'text',
                                    'placeholder' => 'Jane Smith',
                                    'validate'   => ['required'],
                                    'width'      => 100,
                                ],
                            ],
                            [
                                'handle' => 'email',
                                'field' => [
                                    'type'       => 'text',
                                    'display'    => 'Email Address',
                                    'input_type' => 'email',
                                    'placeholder' => 'jane@example.com',
                                    'validate'   => ['required'],
                                    'width'      => 50,
                                ],
                            ],
                            [
                                'handle' => 'phone',
                                'field' => [
                                    'type'       => 'text',
                                    'display'    => 'Phone Number',
                                    'input_type' => 'tel',
                                    'placeholder' => '+1 555 000 0000',
                                    'width'      => 50,
                                ],
                            ],
                            [
                                'handle' => 'message',
                                'field' => [
                                    'type'     => 'textarea',
                                    'display'  => 'Message',
                                    'validate' => ['required'],
                                    'rows'     => 5,
                                    'width'    => 100,
                                ],
                            ],
                        ],
                    ]],
                ],
            ],
        ])->save();
    }
}
