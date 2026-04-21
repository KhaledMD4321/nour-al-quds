<x-filament-panels::page>

    {{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex items-center gap-3" dir="rtl">

        {{-- Expand all --}}
        <button
            wire:click="expandAll"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/10"
        >
            {{-- Down-expand icon --}}
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
            </svg>
            توسيع الكل
        </button>

        {{-- Collapse all --}}
        <button
            wire:click="collapseAll"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/10"
        >
            {{-- Up-collapse icon --}}
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M14.78 11.78a.75.75 0 0 1-1.06 0L10 8.06l-3.72 3.72a.75.75 0 0 1-1.06-1.06l4.25-4.25a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd"/>
            </svg>
            طي الكل
        </button>

        {{-- Account count badge --}}
        <span class="me-auto rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400">
            {{ \App\Models\ChartOfAccount::count() }} حساب
        </span>
    </div>

    {{-- ── Tree table ────────────────────────────────────────────────────────── --}}
    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
        dir="rtl"
    >
        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">

            {{-- ── Header ──────────────────────────────────────────────────── --}}
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="w-36 py-3 px-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        الكود
                    </th>
                    <th class="py-3 px-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        اسم الحساب
                    </th>
                    <th class="w-32 py-3 px-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        النوع
                    </th>
                    <th class="w-44 py-3 px-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        الوحدة
                    </th>
                    <th class="w-16 py-3 px-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        نشط
                    </th>
                    <th class="w-20 py-3 px-4">
                        {{-- actions --}}
                    </th>
                </tr>
            </thead>

            {{-- ── Rows ─────────────────────────────────────────────────────── --}}
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($this->getVisibleAccounts() as $account)
                    @php
                        // ── Row style by level ───────────────────────────────
                        $rowClass = match ($account['level']) {
                            1       => 'bg-gray-100 dark:bg-white/10',
                            2       => 'bg-white dark:bg-gray-900',
                            default => 'bg-white dark:bg-gray-900',
                        };

                        $nameWeight = match ($account['level']) {
                            1       => 'font-bold   text-gray-900 dark:text-gray-50',
                            2       => 'font-semibold text-gray-800 dark:text-gray-100',
                            default => 'font-normal  text-gray-700 dark:text-gray-200',
                        };

                        // ── RTL indent (padding-right) for name cell ─────────
                        $indentPx = ($account['level'] - 1) * 20; // 0 / 20 / 40 / 60 px

                        // ── Type badge ───────────────────────────────────────
                        $typeMeta = match ($account['type']) {
                            'asset'     => ['أصول',       'bg-blue-100   text-blue-700   dark:bg-blue-500/20   dark:text-blue-300'],
                            'liability' => ['خصوم',       'bg-red-100    text-red-700    dark:bg-red-500/20    dark:text-red-300'],
                            'equity'    => ['حقوق ملكية', 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300'],
                            'revenue'   => ['إيرادات',    'bg-green-100  text-green-700  dark:bg-green-500/20  dark:text-green-300'],
                            'expense'   => ['مصروفات',    'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300'],
                            default     => [$account['type'], 'bg-gray-100 text-gray-600'],
                        };
                    @endphp

                    <tr
                        @if ($account['has_children'])
                            wire:click="toggleExpand({{ $account['id'] }})"
                            class="{{ $rowClass }} cursor-pointer transition-colors duration-100 hover:bg-primary-50 dark:hover:bg-primary-900/20"
                        @else
                            class="{{ $rowClass }} transition-colors duration-100 hover:bg-gray-50 dark:hover:bg-white/5"
                        @endif
                    >

                        {{-- ── Code cell: arrow ◄/▼ + code ───────────────── --}}
                        <td class="w-36 px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                {{-- Arrow character — only for parent accounts --}}
                                @if ($account['has_children'])
                                    <span
                                        class="w-4 shrink-0 text-center text-xs leading-none transition-colors duration-150 {{ $account['is_expanded'] ? 'text-primary-500 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }}"
                                        aria-hidden="true"
                                    >{{ $account['is_expanded'] ? '▼' : '◄' }}</span>
                                @else
                                    {{-- Spacer so code aligns with parent codes --}}
                                    <span class="w-4 shrink-0"></span>
                                @endif

                                <span class="font-mono text-xs text-gray-600 dark:text-gray-300">
                                    {{ $account['code'] }}
                                </span>
                            </div>
                        </td>

                        {{-- ── Account name — indented from the right (RTL) ── --}}
                        <td
                            class="py-2.5 px-4 {{ $nameWeight }}"
                            style="padding-right: {{ 16 + $indentPx }}px"
                        >
                            {{ $account['name'] }}
                        </td>

                        {{-- ── Type badge ─────────────────────────────────── --}}
                        <td class="w-32 px-4 py-2.5">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeMeta[1] }}">
                                {{ $typeMeta[0] }}
                            </span>
                        </td>

                        {{-- ── Business unit ───────────────────────────────── --}}
                        <td class="w-44 px-4 py-2.5 text-xs">
                            @if ($account['unit_name'])
                                <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary-400"></span>
                                    {{ $account['unit_name'] }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">عام</span>
                            @endif
                        </td>

                        {{-- ── Active dot ──────────────────────────────────── --}}
                        <td class="w-16 px-4 py-2.5 text-center">
                            @if ($account['is_active'])
                                <span
                                    class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"
                                    title="نشط"
                                ></span>
                            @else
                                <span
                                    class="inline-block h-2.5 w-2.5 rounded-full bg-red-400"
                                    title="موقوف"
                                ></span>
                            @endif
                        </td>

                        {{-- ── Edit link — @click.stop prevents row toggle ── --}}
                        <td class="w-20 px-4 py-2.5 text-center" @click.stop>
                            <a
                                href="{{ \App\Filament\Resources\ChartOfAccountResource::getUrl('edit', ['record' => $account['id']]) }}"
                                class="rounded px-1.5 py-0.5 text-xs font-medium text-primary-600 transition hover:bg-primary-50 hover:text-primary-800 dark:text-primary-400 dark:hover:bg-primary-900/30 dark:hover:text-primary-300"
                            >
                                تعديل
                            </a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="6"
                            class="px-6 py-12 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            لا توجد حسابات مضافة
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-filament-panels::page>
