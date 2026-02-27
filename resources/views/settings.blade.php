<x-layouts.app :title="__('Dashboard')">
    <div class="grid auto-rows-min gap-4 md:grid-cols-2">
        <div class="relative overflow-hidden rounded-xl border bg-neutral-100 dark:bg-zinc-900/20 border-neutral-200 dark:border-neutral-700">
            <livewire:settings.key-for-open-ai />
            {{-- <livewire:settings.key-for-mistral /> --}}
        </div>
        <div class="relative overflow-hidden rounded-xl border bg-neutral-100 dark:bg-zinc-900/20 border-neutral-200 dark:border-neutral-700">
            <livewire:settings.theme-switch />
        </div>
    </div>
</x-layouts.app>
