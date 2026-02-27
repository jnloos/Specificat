<?php

namespace App\Providers;

use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Settings;
use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Override locale if modified in the settings
        $locale = Settings::get('locale', config('app.locale'));
        app()->setLocale($locale);

        Menu::create();
        Window::open()->width(1000)->height(900);

        if (config('app.debug') || app()->environment('local')) {
            Window::open()
                ->id('queue-debug')
                ->title('Queue Debugger')
                ->url(route('debug.queue'))
                ->width(900)
                ->height(550)
                ->alwaysOnTop();
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
