<div class="px-8 py-4">
    <div class="mt-2 mb-5">
        <flux:heading class="mb-2" size="lg">{{ __('settings.dict.appearance') }}</flux:heading>
        <flux:text>{{ __('settings.labels.edit_color_mode') }}</flux:text>
    </div>
    <div class="mb-2">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio class="cursor-pointer" value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio class="cursor-pointer" value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio class="cursor-pointer" value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </div>
</div>
