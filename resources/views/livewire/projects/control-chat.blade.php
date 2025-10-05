@php use App\Models\Project; @endphp
@props([
    'awaitsMessage' => false,
    'disableSummary',
    'disableMessage',
    'disableGenerate',
    'iconGenerate'
])

<div class="fixed bottom-0 left-[15vw] w-[85vw] justify-center z-50 bg-none">
    <div class="max-w-[1120px] justify-center mx-auto">
        <div class="text-center">
            <!-- Textarea -->
            <div class="bg-white dark:bg-zinc-800 rounded-md">
                <flux:textarea wire:model="msgContent" wire:loading.attr="disabled" resize="none" class="w-full rounded-md" :placeholder="__('Contribute to the specification...')" rows="5"/>
            </div>

            <div class="flex gap-2 my-3">
                <flux:button class="basis-1/5 cursor-pointer" wire:loading.attr="disabled" wire:click.debounce="generateSummary" icon="numbered-list" :disabled="$disableSummary"/>
                <flux:button wire:click.debounce="sendMessage" wire:loading.attr="disabled" variant="primary" class="basis-4/5 cursor-pointer" :disabled="$disableMessage">
                    <span>
                        {{ __('Send Message') }}
                    </span>
                </flux:button>
                <flux:button class="basis-1/5 cursor-pointer" wire:loading.attr="disabled" wire:click.debounce="generateMessages" :icon="$iconGenerate" :disabled="$disableGenerate"/>
            </div>
        </div>
    </div>

    @if($awaitsMessage)
        <div wire:poll.2s="tick"></div>
    @endif
</div>



