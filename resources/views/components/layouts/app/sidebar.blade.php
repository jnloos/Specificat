@props([
    'title' => 'Laravel'
])

@use(App\Models\Project)

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 w-[15vw] min-w-[15vw]">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group class="grid mt-5">
                    <flux:navlist.item icon="squares-plus" :href="route('project.new')" :current="request()->routeIs('project.new')" wire:navigate>{{ __('New Project') }}</flux:navlist.item>
                    <flux:navlist.item icon="user-circle" :href="route('experts')" :current="request()->routeIs('experts')" wire:navigate>{{ __('Edit Experts') }}</flux:navlist.item>
                </flux:navlist.group>

                @php
                    $projects = Project::orderBy('updated_at', 'desc')->get();
                @endphp
                @if ($projects->isNotEmpty())
                    <flux:navlist.group :heading="__('Projects')" class="grid mt-5">
                        @foreach ($projects as $project)
                            @php($isCurr = request()->routeIs('project.show') && request()->route('project')->id == $project->id)
                            <flux:navlist.item :href="route('project.show', $project)" :current="$isCurr" wire:navigate>{{ $project->title }}</flux:navlist.item>
                        @endforeach
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://gitlab.uni-trier.de/s4jaloos/specificat" target="_blank">
                    {{ __('Repository') }}
                </flux:navlist.item>
                <flux:navlist.item icon="cog-6-tooth" :href="route('settings')" wire:navigate>
                    {{ __('Settings') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        <!-- Mobile Header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        </flux:header>

        {{ $slot }}

        @fluxScripts

        <livewire:toast-pusher />
    </body>
</html>
