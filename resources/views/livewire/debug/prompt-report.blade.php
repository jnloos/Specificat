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
                <flux:table.column class="text-left">Prompt ID</flux:table.column>
                <flux:table.column class="w-24 text-right">Status</flux:table.column>
                <flux:table.column class="w-20 text-right">Duration</flux:table.column>
                <flux:table.column class="w-7 text-right"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($prompts as $prompt)
                    <flux:table.row :key="$prompt['id']">
                        <flux:table.cell class="text-left"></flux:table.cell>
                        <flux:table.cell class="text-left">
                            <span class="text-xs tabular-nums font-mono text-zinc-400 dark:text-zinc-500">
                                {{ $prompt['requested_at']
                                    ? \Carbon\Carbon::parse($prompt['requested_at'])->format('H:i:s.v')
                                    : '—' }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-left">
                            <span class="text-xs font-mono text-zinc-500 dark:text-zinc-400 truncate block">
                                {{ $prompt['id'] }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            @switch($prompt['status'])
                                @case('pending')
                                    <flux:badge color="sky"   size="sm" inset="left">Pending</flux:badge>
                                    @break
                                @case('success')
                                    <flux:badge color="green" size="sm" inset="left">Success</flux:badge>
                                    @break
                                @case('failed')
                                    <flux:badge color="red"   size="sm" inset="left">Failed</flux:badge>
                                    @break
                            @endswitch
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <span class="text-xs tabular-nums font-mono text-zinc-400 dark:text-zinc-500">
                                @if ($prompt['duration_ms'] !== null)
                                    {{ $prompt['duration_ms'] >= 1000
                                        ? number_format($prompt['duration_ms'] / 1000, 2) . 's'
                                        : $prompt['duration_ms'] . 'ms' }}
                                @else
                                    —
                                @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-right sticky right-0 bg-white dark:bg-zinc-900">
                            <flux:button
                                wire:click="togglePromptExpand('{{ $prompt['id'] }}')"
                                variant="ghost"
                                size="sm"
                                icon="chevron-down"
                                class="text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 {{ $prompt['expanded'] ? 'rotate-180' : '' }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                    @if ($prompt['expanded'])
                        <flux:table.row :key="$prompt['id'] . '-detail'">
                            <flux:table.cell colspan="6" class="py-0 max-w-full min-w-0 overflow-hidden">
                                <div class="ml-8 mr-4 mb-2.5 rounded-lg ring-1 overflow-hidden max-w-full
                                    {{ $prompt['status'] === 'failed'
                                        ? 'bg-red-50 dark:bg-red-950/50 ring-red-200 dark:ring-red-900/60'
                                        : 'bg-white dark:bg-zinc-900 ring-zinc-200 dark:ring-white/10' }}"
                                >
                                    @if ($prompt['model'] || ($prompt['status'] === 'success' && $prompt['inputTokens'] !== null))
                                        <div class="flex items-center gap-3 px-3 py-1.5 border-b border-zinc-200 dark:border-white/10">
                                            @if ($prompt['model'])
                                                <flux:badge color="zinc" size="sm" icon="cpu-chip">{{ $prompt['model'] }}</flux:badge>
                                            @endif
                                            @if ($prompt['status'] === 'success' && $prompt['inputTokens'] !== null)
                                                <span class="ml-auto flex items-center gap-2">
                                                    <flux:badge color="zinc" size="sm" icon="arrow-up-tray">{{ number_format($prompt['inputTokens']) }} in</flux:badge>
                                                    <flux:badge color="zinc" size="sm" icon="arrow-down-tray">{{ number_format($prompt['outputTokens']) }} out</flux:badge>
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($prompt['input'])
                                        <div
                                            class="border-b border-zinc-200 dark:border-white/10"
                                            x-data="{
                                                copied: false,
                                                copy() {
                                                    navigator.clipboard.writeText(@js($prompt['input']));
                                                    this.copied = true;
                                                    setTimeout(() => this.copied = false, 2000);
                                                }
                                            }"
                                        >
                                            <div class="flex items-center justify-between px-3 py-1.5 border-b border-zinc-100 dark:border-white/5">
                                                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Input</span>
                                                <flux:button variant="ghost" size="xs" @click="copy()"
                                                    x-bind:icon="copied ? 'check' : 'clipboard'"
                                                    x-bind:class="copied ? 'text-emerald-500' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                                                ><span x-text="copied ? 'Copied!' : 'Copy'"></span></flux:button>
                                            </div>
                                            <div class="px-3 py-2 max-w-full min-w-0">
                                                <div class="max-w-full min-w-0 overflow-x-auto">
                                                    <pre class="block min-w-max text-zinc-700 dark:text-zinc-300 text-xs leading-relaxed whitespace-pre max-h-36 overflow-y-auto">{{ $prompt['input'] }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if (count($prompt['attachments']) > 0)
                                        <div class="border-b border-zinc-200 dark:border-white/10">
                                            <div class="px-3 py-1.5 border-b border-zinc-100 dark:border-white/5">
                                                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                                                    Attachments ({{ count($prompt['attachments']) }})
                                                </span>
                                            </div>
                                            <div class="divide-y divide-zinc-100 dark:divide-white/5">
                                                @foreach ($prompt['attachments'] as $attachment)
                                                    <div class="px-3 py-1.5">
                                                        <span class="text-xs font-mono text-zinc-500 dark:text-zinc-400">{{ $attachment }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($prompt['status'] === 'success' && $prompt['response'] !== null)
                                        <div
                                            x-data="{
                                                copied: false,
                                                copy() {
                                                    navigator.clipboard.writeText(@js($prompt['response']));
                                                    this.copied = true;
                                                    setTimeout(() => this.copied = false, 2000);
                                                }
                                            }"
                                        >
                                            <div class="flex items-center justify-between px-3 py-1.5 border-b border-zinc-100 dark:border-white/5">
                                                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Response</span>
                                                <flux:button variant="ghost" size="xs" @click="copy()"
                                                    x-bind:icon="copied ? 'check' : 'clipboard'"
                                                    x-bind:class="copied ? 'text-emerald-500' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                                                ><span x-text="copied ? 'Copied!' : 'Copy'"></span></flux:button>
                                            </div>
                                            <div class="px-3 py-2 max-w-full min-w-0">
                                                <div class="max-w-full min-w-0 overflow-x-auto">
                                                    <pre class="block min-w-max text-zinc-700 dark:text-zinc-200 text-xs leading-relaxed whitespace-pre max-h-48 overflow-y-auto">{{ $prompt['response'] }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($prompt['status'] === 'failed' && $prompt['error'])
                                        <div
                                            x-data="{
                                                copied: false,
                                                fullText: @js(($prompt['error'] ?? '') . ($prompt['trace'] ? "\\n\\nStack trace:\\n" . $prompt['trace'] : '')),
                                                copy() {
                                                    navigator.clipboard.writeText(this.fullText);
                                                    this.copied = true;
                                                    setTimeout(() => this.copied = false, 2000);
                                                }
                                            }"
                                        >
                                            <div class="flex items-center justify-between px-3 py-1.5 border-b border-red-200 dark:border-red-900/60">
                                                <span class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">Exception</span>
                                                <flux:button variant="ghost" size="xs" @click="copy()"
                                                    x-bind:icon="copied ? 'check' : 'clipboard'"
                                                    x-bind:class="copied ? 'text-emerald-500' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                                                ><span x-text="copied ? 'Copied!' : 'Copy'"></span></flux:button>
                                            </div>
                                            <div class="px-3 py-2 max-w-full min-w-0">
                                                <div class="max-w-full min-w-0 overflow-x-auto">
                                                    <pre class="block min-w-max text-red-700 dark:text-red-300 text-xs leading-relaxed font-mono max-h-64 overflow-y-auto whitespace-pre">{{ $prompt['error'] }}</pre>
                                                </div>
                                            </div>
                                            @if ($prompt['trace'])
                                                <div class="border-t border-red-200 dark:border-red-900/60">
                                                    <div class="px-3 py-1.5 border-b border-red-200 dark:border-red-900/60">
                                                        <span class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">Stack Trace</span>
                                                    </div>
                                                    <div class="px-3 py-2 max-w-full min-w-0">
                                                        <div class="max-w-full min-w-0 overflow-x-auto">
                                                            <pre class="block min-w-max text-zinc-500 dark:text-zinc-400 text-xs leading-relaxed whitespace-pre max-h-64 overflow-y-auto">{{ $prompt['trace'] }}</pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endif
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3 select-none">
                                <flux:icon.chat-bubble-left-right class="w-10 h-10 text-zinc-300 dark:text-zinc-700" />
                                <flux:text size="sm" variant="subtle">No prompt requests yet</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Footer --}}
    <div class="shrink-0 flex items-center gap-2 px-4 py-2 border-t border-zinc-200/80 dark:border-white/10">
        @php $pcounts = collect($prompts)->countBy('status'); @endphp
        <flux:badge color="sky"   size="sm">{{ $pcounts->get('pending', 0) }} pending</flux:badge>
        <flux:badge color="green" size="sm">{{ $pcounts->get('success', 0) }} succeeded</flux:badge>
        <flux:badge color="red"   size="sm">{{ $pcounts->get('failed', 0) }} failed</flux:badge>
        <span class="ml-auto text-xs text-zinc-400 tabular-nums">{{ count($prompts) }} total</span>
    </div>

</div>
