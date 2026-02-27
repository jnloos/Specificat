<div class="px-8 py-4">
    <div class="mt-2 mb-5">
        <flux:heading class="mb-2" size="lg">{{ __('settings.headings.open_ai') }}</flux:heading>
        <flux:text>{{ __('settings.labels.edit_open_ai_key') }}</flux:text>
    </div>
    <div class="mb-4">
        <flux:input type="password" size="md" wire:model="apiKey"
                    placeholder="{{ __('settings.placeholders.open_ai_key') }}"/>
        <flux:error name="apiKey"/>
        @if($apiKey !== '' && ! $errors->has('apiKey'))
            <flux:text class="mt-2 text-lime-600 inline-flex">
                <flux:icon class="me-1 size-5" name="check"/> {{ __('settings.notifications.open_ai_key_valid') }}
            </flux:text>
        @endif
    </div>
    <flux:button class="mb-4 cursor-pointer" wire:click="save">{{ __('Save') }}</flux:button>
</div>
