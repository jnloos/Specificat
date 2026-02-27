@props([
    'experts' => [],
    'label' => null,
])

<div class="flex items-center space-x-3">
    <flux:avatar.group class="**:ring-zinc-100 dark:**:ring-zinc-800">
        @php
            $visible = collect($experts)->take(3);
            $remaining = count($experts) - $visible->count();
        @endphp

        @foreach ($visible as $expert)
            <x-contributors.contributors-avatar :name="$expert->name" :avatar-url="$expert->avatar_url" class="h-12 w-12"/>
        @endforeach

        <div>
            @if ($remaining > 0)
                <flux:avatar circle class="cut-avatar w-12 h-12 rounded-full">
                    {{ $remaining }}+
                </flux:avatar>
            @else
                {{-- Platzhalter-Dummy, damit der Block immer existiert --}}
                <div class="w-0 h-0 overflow-hidden"></div>
            @endif
        </div>
    </flux:avatar.group>

    <flux:button variant="primary" {{ $attributes->merge(['type' => 'button']) }} class="ms-4 relative group transition flex items-center space-x-2 cursor-pointer">
        @if (!is_null($label))
            <span>{{ $label }}</span>
        @endif
    </flux:button>
</div>