<div class="flex flex-col flex-1 min-h-0">

    <div
        class="flex-1 min-h-0 overflow-y-auto px-4 py-3"
        x-data
        x-init="
            const el = $el;
            const observer = new MutationObserver(() => el.scrollTop = el.scrollHeight);
            observer.observe(el, { childList: true, subtree: true });
        "
    >
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-6"></flux:table.column>
                <flux:table.column class="w-32 text-left">Time</flux:table.column>
                <flux:table.column class="text-left">Job</flux:table.column>
                <flux:table.column class="w-28 text-left">Queue</flux:table.column>
                <flux:table.column class="w-28 text-right">Status</flux:table.column>
                <flux:table.column class="w-24 text-right">Duration</flux:table.column>
                <flux:table.column class="w-7 text-right"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($jobs as $job)
                    <flux:table.row :key="$job['id']">
                        <flux:table.cell class="text-left"></flux:table.cell>
                        <flux:table.cell class="text-left">
                            <span class="text-xs tabular-nums font-mono text-zinc-400 dark:text-zinc-500">
                                {{ $job['dispatched_at']
                                    ? \Carbon\Carbon::parse($job['dispatched_at'])->format('H:i:s.v')
                                    : ($job['started_at']
                                        ? \Carbon\Carbon::parse($job['started_at'])->format('H:i:s.v')
                                        : '—') }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-left">
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate block" title="{{ $job['fullClass'] }}">
                                {{ $job['class'] }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-left">
                            <flux:badge color="zinc" size="sm" class="truncate max-w-full font-mono">
                                {{ $job['queue'] ?? 'default' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            @switch($job['status'])
                                @case('dispatched')
                                    <flux:badge color="sky"    size="sm" inset="left">Dispatched</flux:badge>
                                    @break
                                @case('running')
                                    <flux:badge color="yellow" size="sm" inset="left">Running</flux:badge>
                                    @break
                                @case('success')
                                    <flux:badge color="green"  size="sm" inset="left">Success</flux:badge>
                                    @break
                                @case('failed')
                                    <flux:badge color="red"    size="sm" inset="left">Failed</flux:badge>
                                    @break
                            @endswitch
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <span class="text-xs tabular-nums font-mono text-zinc-400 dark:text-zinc-500">
                                @if ($job['duration_ms'] !== null)
                                    {{ $job['duration_ms'] >= 1000
                                        ? number_format($job['duration_ms'] / 1000, 2) . 's'
                                        : $job['duration_ms'] . 'ms' }}
                                @else
                                    —
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            @if ($job['status'] === 'failed' && $job['error'])
                                <flux:button
                                    wire:click="toggleExpand('{{ $job['id'] }}')"
                                    variant="ghost"
                                    size="sm"
                                    icon="chevron-down"
                                    class="text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 {{ $job['expanded'] ? 'rotate-180' : '' }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                    @if ($job['expanded'] && $job['status'] === 'failed')
                        <flux:table.row :key="$job['id'] . '-detail'">
                            <flux:table.cell colspan="7" class="py-0 max-w-full min-w-0 overflow-hidden">
                                <div
                                    class="ml-8 mr-4 mb-2.5 rounded-lg bg-red-50 dark:bg-red-950/50 ring-1 ring-red-200 dark:ring-red-900/60 overflow-hidden max-w-full"
                                    x-data="{
                                        copied: false,
                                        fullText: @js(($job['error'] ?? '') . ($job['trace'] ? "\\n\\nStack trace:\\n" . $job['trace'] : '')),
                                        copy() {
                                            navigator.clipboard.writeText(this.fullText);
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 2000);
                                        }
                                    }"
                                >
                                    <div class="flex items-center justify-between px-3 py-1.5 border-b border-red-200 dark:border-red-900/60">
                                        <span class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide">Exception</span>
                                        <flux:button
                                            variant="ghost" size="xs"
                                            @click="copy()"
                                            x-bind:icon="copied ? 'check' : 'clipboard'"
                                            x-bind:class="copied ? 'text-emerald-500' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                                        ><span x-text="copied ? 'Copied!' : 'Copy'"></span></flux:button>
                                    </div>
                                    <div class="px-3 py-2 max-w-full min-w-0">
                                        <div class="max-w-full min-w-0 overflow-x-auto">
                                            <pre class="block min-w-max text-red-700 dark:text-red-300 text-xs leading-relaxed font-mono max-h-64 overflow-y-auto whitespace-pre">{{ $job['error'] }}</pre>
                                        </div>
                                    </div>
                                    @if ($job['trace'])
                                        <div class="border-t border-red-200 dark:border-red-900/60">
                                            <div class="px-3 py-1.5 border-b border-red-200 dark:border-red-900/60">
                                                <span class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide">Stack Trace</span>
                                            </div>
                                            <div class="px-3 py-2 max-w-full min-w-0">
                                                <div class="max-w-full min-w-0 overflow-x-auto">
                                                    <pre class="block min-w-max text-zinc-500 dark:text-zinc-400 text-xs leading-relaxed whitespace-pre max-h-64 overflow-y-auto">{{ $job['trace'] }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endif
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3 select-none">
                                <flux:icon.queue-list class="w-10 h-10 text-zinc-300 dark:text-zinc-700" />
                                <flux:text size="sm" variant="subtle">No queue jobs yet</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Footer --}}
    <div class="shrink-0 flex items-center gap-2 px-4 py-2 border-t border-zinc-200/80 dark:border-white/10">
        @php $counts = collect($jobs)->countBy('status'); @endphp
        <flux:badge color="sky"    size="sm">{{ $counts->get('dispatched', 0) }} dispatched</flux:badge>
        <flux:badge color="yellow" size="sm">{{ $counts->get('running', 0) }} running</flux:badge>
        <flux:badge color="green"  size="sm">{{ $counts->get('success', 0) }} succeeded</flux:badge>
        <flux:badge color="red"    size="sm">{{ $counts->get('failed', 0) }} failed</flux:badge>
        <span class="ml-auto text-xs text-zinc-400 tabular-nums">{{ count($jobs) }} total</span>
    </div>

</div>
