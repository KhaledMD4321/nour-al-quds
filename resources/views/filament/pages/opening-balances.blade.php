<x-filament-panels::page>

    {{-- ─── تاريخ الأرصدة (مشترك لكل التبويبات) ───────────────────────────── --}}
    <x-filament::section>
        <div class="max-w-xs">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                تاريخ الأرصدة الافتتاحية
            </label>
            <input
                type="date"
                wire:model="balance_date"
                class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
            >
        </div>
    </x-filament::section>

    {{-- ─── Tabs ────────────────────────────────────────────────────────────── --}}
    <x-filament::tabs label="الأرصدة الافتتاحية">
        <x-filament::tabs.item
            :active="$activeTab === 'customers'"
            wire:click="$set('activeTab', 'customers')"
            icon="heroicon-o-users"
        >
            أرصدة العملاء
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'suppliers'"
            wire:click="$set('activeTab', 'suppliers')"
            icon="heroicon-o-truck"
        >
            أرصدة الموردين
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'stock'"
            wire:click="$set('activeTab', 'stock')"
            icon="heroicon-o-cube"
        >
            أرصدة المخزون
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── تبويب العملاء ─────────────────────────────────────────────────── --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'customers')

        <x-filament::section
            heading="إضافة رصيد افتتاحي لعميل"
            description="المبلغ اللي العميل مديونلنا بيه عند بداية تشغيل النظام"
        >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العميل</label>
                    <select
                        wire:model="customer_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                        <option value="">اختر العميل...</option>
                        @foreach(\App\Models\Customer::active()->orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المبلغ (ج.م.)</label>
                    <input
                        type="number"
                        wire:model="customer_amount"
                        step="0.01" min="0"
                        placeholder="0.00"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                    @error('customer_amount') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-filament::button wire:click="saveCustomerBalance" color="success" icon="heroicon-o-check">
                        حفظ الرصيد
                    </x-filament::button>
                </div>

            </div>
        </x-filament::section>

        @if($this->existingBalances['customers']->isNotEmpty())
            <x-filament::section heading="الأرصدة المسجلة للعملاء">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">العميل</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">المبلغ</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->existingBalances['customers'] as $b)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-2">{{ $b['name'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-mono">{{ number_format($b['amount'], 2) }} ج.م.</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $b['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── تبويب الموردين ────────────────────────────────────────────────── --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'suppliers')

        <x-filament::section
            heading="إضافة رصيد افتتاحي لمورد"
            description="المبلغ اللي إحنا مديونين بيه للمورد عند بداية تشغيل النظام"
        >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المورد</label>
                    <select
                        wire:model="supplier_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                        <option value="">اختر المورد...</option>
                        @foreach(\App\Models\Supplier::active()->orderBy('name')->get() as $s)
                            <option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المبلغ (ج.م.)</label>
                    <input
                        type="number"
                        wire:model="supplier_amount"
                        step="0.01" min="0"
                        placeholder="0.00"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                    @error('supplier_amount') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-filament::button wire:click="saveSupplierBalance" color="success" icon="heroicon-o-check">
                        حفظ الرصيد
                    </x-filament::button>
                </div>

            </div>
        </x-filament::section>

        @if($this->existingBalances['suppliers']->isNotEmpty())
            <x-filament::section heading="الأرصدة المسجلة للموردين">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">المورد</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">المبلغ</th>
                                <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->existingBalances['suppliers'] as $b)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-2">{{ $b['name'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-mono">{{ number_format($b['amount'], 2) }} ج.م.</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $b['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── تبويب المخزون ──────────────────────────────────────────────────── --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'stock')

        {{-- إدخال يدوي — صنف واحد --}}
        <x-filament::section heading="إضافة رصيد مخزون — صنف واحد">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المخزن</label>
                    <select
                        wire:model="warehouse_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                        <option value="">اختر المخزن...</option>
                        @foreach(\App\Models\Warehouse::where('is_active', true)->get() as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الصنف</label>
                    <select
                        wire:model="stock_product_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                        <option value="">اختر الصنف...</option>
                        @foreach(\App\Models\Product::where('is_active', true)->orderBy('name')->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('stock_product_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الكمية</label>
                    <input
                        type="number"
                        wire:model="stock_quantity"
                        step="0.001" min="0"
                        placeholder="0.000"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                    @error('stock_quantity') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تكلفة الوحدة (ج.م.)</label>
                    <input
                        type="number"
                        wire:model="stock_unit_cost"
                        step="0.01" min="0"
                        placeholder="0.00"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                    @error('stock_unit_cost') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-filament::button wire:click="saveStockBalance" color="success" icon="heroicon-o-check">
                        حفظ
                    </x-filament::button>
                </div>

            </div>
        </x-filament::section>

        {{-- رفع من Excel --}}
        <x-filament::section
            heading="رفع مخزون افتتاحي من Excel"
            description="تنسيق الملف: عمود A = كود أو اسم الصنف | عمود B = الكمية | عمود C = تكلفة الوحدة (أول صف عناوين يتخطى)"
            collapsible
            collapsed
        >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المخزن</label>
                    <select
                        wire:model="warehouse_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                        <option value="">اختر المخزن...</option>
                        @foreach(\App\Models\Warehouse::where('is_active', true)->get() as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملف Excel / CSV</label>
                    <input
                        type="file"
                        wire:model="stock_excel_file"
                        accept=".xlsx,.xls,.csv"
                        class="block w-full text-sm text-gray-500 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900 dark:file:text-primary-300"
                    >
                    @error('stock_excel_file') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-filament::button wire:click="importStockExcel" color="primary" icon="heroicon-o-arrow-up-tray">
                        رفع وإضافة
                    </x-filament::button>
                </div>

            </div>
        </x-filament::section>

        {{-- ملخص المخزون الافتتاحي --}}
        @if($this->existingBalances['stock_count'] > 0)
            <x-filament::section heading="ملخص المخزون الافتتاحي المسجّل">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-6 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                            {{ number_format($this->existingBalances['stock_count']) }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">صنف مسجّل</div>
                    </div>
                    <div class="text-center p-6 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                            {{ number_format($this->existingBalances['stock_value'], 2) }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">ج.م. قيمة إجمالية</div>
                    </div>
                </div>
            </x-filament::section>
        @endif

    @endif

</x-filament-panels::page>
