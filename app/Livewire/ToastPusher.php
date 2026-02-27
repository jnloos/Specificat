<?php

namespace App\Livewire;

use App\Events\Toast\ToastDanger;
use App\Events\Toast\ToastNeutral;
use App\Events\Toast\ToastSuccess;
use App\Events\Toast\ToastWarning;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class ToastPusher extends Component
{
    private static function pushToToast(string $text, string $heading, int $ttl, ?string $variant = null): void
    {
        if (trim($text) == '') {
            $text = null;
        }

        if (trim($heading) == '') {
            $heading = null;
        }

        Flux::toast(text: $text, heading: $heading, duration: $ttl, variant: $variant);
    }

    #[On('native:'.ToastSuccess::class)]
    public function onToastSuccess(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::toastSuccess($heading, $text, ttl: $ttl);
    }

    public static function toastSuccess(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::pushToToast($text, $heading, ttl: $ttl, variant: 'success');
    }

    #[On('native:'.ToastWarning::class)]
    public function onToastWarning(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::toastWarning($text, $heading, ttl: $ttl);
    }

    public static function toastWarning(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::pushToToast($text, $heading, ttl: $ttl, variant: 'warning');
    }

    #[On('native:'.ToastDanger::class)]
    public function onToastDanger(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::toastDanger($text, $heading, ttl: $ttl);
    }

    public static function toastDanger(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::pushToToast($text, $heading, ttl: $ttl, variant: 'danger');
    }

    #[On('native:'.ToastNeutral::class)]
    public function onToastNeutral(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::toastNeutral($text, $heading, ttl: $ttl);
    }

    public static function toastNeutral(string $heading = '', string $text = '', int $ttl = 10000): void
    {
        self::pushToToast($text, $heading, ttl: $ttl);
    }

    public function render(): mixed
    {
        return view('livewire.toast-pusher');
    }
}
