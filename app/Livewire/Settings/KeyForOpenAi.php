<?php

namespace App\Livewire\Settings;

use App\Livewire\ToastPusher;
use Livewire\Component;

class KeyForOpenAi extends Component
{
    public $apiKey;

    public function mount(): void
    {
        $this->apiKey = \App\Services\OpenAI\KeyForOpenAI::get() ?? '';
        if ($this->apiKey !== '') {
            $this->validate();
        }
    }

    protected function rules(): array
    {
        return [
            'apiKey' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!\App\Services\OpenAI\KeyForOpenAI::validate($value)) {
                        $fail(__('settings.errors.open_ai_key_invalid'));
                    }
                },
            ],
        ];
    }

    public function save(): void
    {
        $this->resetErrorBag('apiKey');

        $this->validate();
        \App\Services\OpenAI\KeyForOpenAI::set($this->apiKey);
        ToastPusher::toastSuccess(__('settings.headings.open_ai'), __('settings.notifications.open_ai_key_valid'));
    }


    public function render(): mixed
    {
        return view('livewire.settings.key-for-open-ai');
    }
}
