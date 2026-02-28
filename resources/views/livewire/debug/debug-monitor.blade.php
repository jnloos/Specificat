<flux:tab.group class="flex flex-col h-screen">

    {{-- Header --}}
    <div class="flex items-center gap-4 px-4 py-2.5 border-b border-zinc-200/80 dark:border-white/10 bg-white dark:bg-zinc-900 shrink-0">
        <div class="flex items-center gap-2.5">
            <flux:heading size="sm" class="tracking-tight font-semibold">Debug Monitor</flux:heading>
        </div>

        <flux:tabs wire:model="activeTab" variant="segmented" size="sm" class="mx-auto">
            <flux:tab name="jobs">
                Jobs
                @if ($jobCount > 0)
                    <flux:badge color="zinc" size="sm" class="ml-1.5">{{ $jobCount }}</flux:badge>
                @endif
            </flux:tab>
            <flux:tab name="prompts">
                Prompts
                @if ($promptCount > 0)
                    <flux:badge color="zinc" size="sm" class="ml-1.5">{{ $promptCount }}</flux:badge>
                @endif
            </flux:tab>
        </flux:tabs>

        <flux:button
            wire:click="clearActive"
            variant="ghost"
            size="sm"
            icon="trash"
            class="text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
        />
    </div>

    <flux:tab.panel name="jobs" class="flex flex-col flex-1 min-h-0">
        <livewire:debug.queue-report />
    </flux:tab.panel>

    <flux:tab.panel name="prompts" class="flex flex-col flex-1 min-h-0">
        <livewire:debug.prompt-report />
    </flux:tab.panel>

</flux:tab.group>
