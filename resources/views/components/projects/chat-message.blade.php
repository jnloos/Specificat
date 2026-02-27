@use(App\Facades\Markdown)

@props([
    'id' => Str::random(6),
    'msg'
])

@php($expert = $msg->expert)

<div class="block">
    @if(is_null($expert))
        <div class="flex justify-end">
            <div id="{{ $id }}" wire:key="{{ $id }}" class="rounded-lg min-w-sm z-0 px-5 pt-4 pb-8 break-words ms-30 bg-zinc-300 dark:bg-zinc-600">
                <flux:heading size="lg" class="mb-2 font-bold">{{ __('You') }}</flux:heading>
                <span class="markdown-html">
                    {!! Markdown::parse($msg->content) !!}
                </span>
            </div>
        </div>
    @else
        <div class="flex justify-start">
            <div id="{{ $id }}" wire:key="{{ $id }}" class="rounded-lg min-w-sm z-0 px-5 pt-4 pb-8 break-words me-30 bg-zinc-100 dark:bg-zinc-700">
                <flux:heading size="lg" class="mb-2 font-bold">{{ $expert->name }}</flux:heading>
                <span class="markdown-html">
                    {!! Markdown::parse($msg->content) !!}
                </span>
            </div>
        </div>
    @endif

    <div class="-translate-y-5">
        @if(is_null($expert))
            <x-contributors.contributors-avatar :name="__('You')" :avatar-url="null" class="ms-auto me-5 w-12 h-12"/>
        @else
            <x-contributors.contributors-avatar :name="$expert->name" :avatar-url="$expert->avatar_url" class="ms-5 w-12 h-12"/>
        @endif
    </div>
</div>