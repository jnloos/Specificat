<div class="flex flex-col h-screen bg-zinc-900 text-zinc-100 font-mono text-sm">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-700 bg-zinc-800 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
            <span class="font-semibold text-zinc-100 text-base tracking-tight">Queue Debugger</span>
            <span class="text-zinc-500 text-xs">{{ count($jobs) }} job{{ count($jobs) !== 1 ? 's' : '' }}</span>
        </div>
        <flux:button
            wire:click="clearJobs"
            variant="ghost"
            size="sm"
            icon="trash"
            class="text-zinc-400 hover:text-zinc-100"
        >
            Clear
        </flux:button>
    </div>

    {{-- Column headings --}}
    @if (count($jobs) > 0)
        <div class="grid grid-cols-[140px_1fr_110px_90px_32px] gap-2 px-4 py-2 border-b border-zinc-700/50 text-zinc-500 text-xs uppercase tracking-wider shrink-0">
            <span>Time</span>
            <span>Job</span>
            <span>Status</span>
            <span class="text-right">Duration</span>
            <span></span>
        </div>
    @endif

    {{-- Job list (newest first) --}}
    <div
        class="flex-1 overflow-y-auto"
        x-data
        x-init="
            const el = $el;
            const observer = new MutationObserver(() => el.scrollTop = el.scrollHeight);
            observer.observe(el, { childList: true, subtree: true });
        "
    >
        @forelse (array_reverse($jobs, true) as $job)
            <div
                class="border-b border-zinc-800 hover:bg-zinc-800/40 transition-colors"
                wire:key="job-{{ $job['id'] }}"
            >
                {{-- Main row --}}
                <div class="grid grid-cols-[140px_1fr_110px_90px_32px] gap-2 items-center px-4 py-2.5">

                    {{-- Timestamp --}}
                    <span class="text-zinc-500 text-xs tabular-nums truncate">
                        {{ $job['dispatched_at']
                            ? \Carbon\Carbon::parse($job['dispatched_at'])->format('H:i:s.v')
                            : ($job['started_at']
                                ? \Carbon\Carbon::parse($job['started_at'])->format('H:i:s.v')
                                : '—') }}
                    </span>

                    {{-- Job class --}}
                    <div class="min-w-0">
                        <span
                            class="text-zinc-200 truncate block"
                            title="{{ $job['fullClass'] }}"
                        >{{ $job['class'] }}</span>
                        <span class="text-zinc-600 text-xs truncate block">{{ $job['id'] }}</span>
                    </div>

                    {{-- Status badge --}}
                    <div>
                        @switch($job['status'])
                            @case('dispatched')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-500/15 text-blue-400 ring-1 ring-blue-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                    Dispatched
                                </span>
                                @break
                            @case('running')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/15 text-yellow-400 ring-1 ring-yellow-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                                    Running
                                </span>
                                @break
                            @case('success')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    Success
                                </span>
                                @break
                            @case('failed')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/15 text-red-400 ring-1 ring-red-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                    Failed
                                </span>
                                @break
                        @endswitch
                    </div>

                    {{-- Duration --}}
                    <span class="text-zinc-400 text-xs text-right tabular-nums">
                        @if ($job['duration_ms'] !== null)
                            {{ $job['duration_ms'] >= 1000
                                ? number_format($job['duration_ms'] / 1000, 2) . 's'
                                : $job['duration_ms'] . 'ms' }}
                        @else
                            <span class="text-zinc-700">—</span>
                        @endif
                    </span>

                    {{-- Expand toggle (only for failed jobs) --}}
                    <div class="flex justify-center">
                        @if ($job['status'] === 'failed' && $job['error'])
                            <button
                                wire:click="toggleExpand('{{ $job['id'] }}')"
                                class="text-zinc-500 hover:text-red-400 transition-colors"
                                title="Toggle error details"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 transition-transform {{ $job['expanded'] ? 'rotate-180' : '' }}">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Expanded error panel --}}
                @if ($job['expanded'] && $job['status'] === 'failed')
                    <div
                        class="mx-4 mb-3 rounded-md bg-red-950/40 ring-1 ring-red-900/50 overflow-hidden"
                        x-data="{
                            copied: false,
                            fullText: @js(($job['error'] ?? '') . ($job['trace'] ? "\n\nStack trace:\n" . $job['trace'] : '')),
                            copy() {
                                navigator.clipboard.writeText(this.fullText);
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2000);
                            }
                        }"
                    >
                        {{-- Exception header + copy button --}}
                        <div class="flex items-center justify-between px-3 py-2 border-b border-red-900/40">
                            <span class="text-red-300 text-xs font-semibold">Exception</span>
                            <button
                                @click="copy()"
                                class="flex items-center gap-1 text-xs text-zinc-500 hover:text-zinc-200 transition-colors"
                            >
                                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5">
                                    <path d="M5.5 3.5A1.5 1.5 0 0 1 7 2h2.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 1 .439 1.061V9.5A1.5 1.5 0 0 1 12 11V9.329A2.5 2.5 0 0 0 11.329 7L9.5 5.172A2.5 2.5 0 0 0 7.829 4.5H7A1.5 1.5 0 0 1 5.5 3.5Z" />
                                    <path d="M4 5a1.5 1.5 0 0 0-1.5 1.5v6A1.5 1.5 0 0 0 4 14h5a1.5 1.5 0 0 0 1.5-1.5V9.329a1 1 0 0 0-.293-.707L8.086 6.5a1 1 0 0 0-.707-.293H5.5A1.5 1.5 0 0 0 4 5Z" />
                                </svg>
                                <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3.5 h-3.5 text-emerald-400">
                                    <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy'" :class="copied ? 'text-emerald-400' : ''"></span>
                            </button>
                        </div>

                        {{-- Error message --}}
                        <p class="px-3 py-2 text-red-200 text-xs leading-relaxed">{{ $job['error'] }}</p>

                        {{-- Full stack trace (scrollable) --}}
                        @if ($job['trace'])
                            <div class="border-t border-red-900/40">
                                <div class="px-3 py-2 border-b border-red-900/40">
                                    <span class="text-red-300 text-xs font-semibold">Stack Trace</span>
                                </div>
                                <pre class="px-3 py-2 text-zinc-400 text-xs leading-relaxed overflow-auto max-h-72 whitespace-pre">{{ $job['trace'] }}</pre>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center h-full gap-3 text-zinc-600 select-none py-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12 text-zinc-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
                <span class="text-sm">Waiting for queue jobs…</span>
            </div>
        @endforelse
    </div>

    {{-- Footer status bar --}}
    <div class="shrink-0 flex items-center gap-4 px-4 py-1.5 border-t border-zinc-700/50 bg-zinc-800/50 text-xs text-zinc-500">
        @php
            $counts = collect($jobs)->countBy('status');
        @endphp
        <span class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            {{ $counts->get('dispatched', 0) }} dispatched
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
            {{ $counts->get('running', 0) }} running
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            {{ $counts->get('success', 0) }} succeeded
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
            {{ $counts->get('failed', 0) }} failed
        </span>
    </div>

</div>