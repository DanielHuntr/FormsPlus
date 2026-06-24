<?php

namespace App\FormsPlus;

use App\FormsPlus\Fieldtypes\FormsPlusFieldtype;
use App\FormsPlus\Listeners\HandleFormSubmission;
use App\FormsPlus\Tags\FormsPlus;
use Illuminate\Support\Facades\Event;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\CP\Nav;
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

    public function bootAddon(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forms-plus');

        Event::listen(FormSubmitted::class, HandleFormSubmission::class);

        Nav::extend(function ($nav) {
            $nav->tools('Forms Plus')
                ->route('forms-plus.index')
                ->icon('form')
                ->children([
                    $nav->item('All Forms')->route('forms-plus.index'),
                    $nav->item('Email Templates')->route('forms-plus.email-templates'),
                    $nav->item('Theme')->route('forms-plus.theme'),
                ]);
        });
    }
}
