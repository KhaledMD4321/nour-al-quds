<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- شريط الأدوات --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">

            {{-- فلتر السنة --}}
            <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <span style="font-size: 13px; font-weight: 600; color: #374151;">السنة:</span>
                @foreach($this->getAvailableYears() as $year)
                    <button wire:click="$set('selectedYear', {{ $year }})"
                        style="padding: 6px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;
                               border: 1px solid {{ $selectedYear == $year ? '#1e40af' : '#e5e7eb' }};
                               background: {{ $selectedYear == $year ? '#1e40af' : 'white' }};
                               color: {{ $selectedYear == $year ? 'white' : '#374151' }};">
                        {{ $year }}
                    </button>
                @endforeach
            </div>

            {{-- أزرار الإدارة (super_admin فقط) --}}
            @if(auth()->user()->isSuperAdmin())
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button wire:click="lockAllBefore"
                        onclick="if(!confirm('هل أنت متأكد من قفل كل الفترات السابقة للشهر الحالي؟')) return false;"
                        style="background: #dc2626; color: white; padding: 8px 14px; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600;">
                        🔒 قفل كل الفترات السابقة
                    </button>
                    <div style="display: flex; gap: 4px; align-items: center;">
                        <input type="number" wire:model.live="newYear" min="2020" max="2035"
                            style="width: 80px; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; text-align: center;" />
                        <button wire:click="generateYear"
                            style="background: #059669; color: white; padding: 8px 14px; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                            + إنشاء سنة
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- ملخص السنة --}}
        @php
            $periods       = $this->getPeriods();
            $lockedCount   = $periods->where('is_locked', true)->count();
            $totalJournals = $periods->sum('journal_count');
            $totalSales    = $periods->sum('total_sales');
        @endphp
        <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px 18px; text-align: center;">
                <div style="font-size: 20px; font-weight: 800; color: #0369a1;">{{ $periods->count() }}</div>
                <div style="font-size: 11px; color: #0369a1;">فترة</div>
            </div>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 18px; text-align: center;">
                <div style="font-size: 20px; font-weight: 800; color: #dc2626;">{{ $lockedCount }}</div>
                <div style="font-size: 11px; color: #dc2626;">مقفولة</div>
            </div>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 18px; text-align: center;">
                <div style="font-size: 20px; font-weight: 800; color: #059669;">{{ $periods->count() - $lockedCount }}</div>
                <div style="font-size: 11px; color: #059669;">مفتوحة</div>
            </div>
            <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px 18px; text-align: center;">
                <div style="font-size: 20px; font-weight: 800; color: #7c3aed;">{{ number_format($totalJournals) }}</div>
                <div style="font-size: 11px; color: #7c3aed;">قيد محاسبي</div>
            </div>
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 18px; text-align: center;">
                <div style="font-size: 18px; font-weight: 800; color: #d97706;">{{ number_format($totalSales) }}</div>
                <div style="font-size: 11px; color: #d97706;">إجمالي المبيعات</div>
            </div>
        </div>

        {{-- بطاقات الفترات --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 12px;">
            @forelse($periods as $p)
                <div style="background: white;
                            border: 2px solid {{ $p->is_current ? '#3b82f6' : ($p->is_locked ? '#fca5a5' : '#e5e7eb') }};
                            border-radius: 12px; padding: 16px;
                            {{ $p->is_current ? 'box-shadow: 0 0 0 3px #3b82f644;' : '' }}">

                    {{-- رأس البطاقة --}}
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 19px; font-weight: 800; color: #111827;">{{ $p->month_name }}</div>
                            <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;">
                                {{ \Illuminate\Support\Carbon::parse($p->start_date)->format('d/m') }}
                                ←
                                {{ \Illuminate\Support\Carbon::parse($p->end_date)->format('d/m/Y') }}
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                            @if($p->is_current)
                                <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600;">
                                    الشهر الحالي
                                </span>
                            @endif
                            @if($p->is_locked)
                                <span style="background: #fef2f2; color: #dc2626; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600;">
                                    🔒 مقفول
                                </span>
                            @else
                                <span style="background: #dcfce7; color: #059669; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600;">
                                    🔓 مفتوح
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- إحصائيات الفترة --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 12px;">
                        <div style="text-align: center; background: #f9fafb; padding: 8px 4px; border-radius: 6px;">
                            <div style="font-size: 17px; font-weight: 700; color: #1e40af;">{{ number_format($p->journal_count) }}</div>
                            <div style="font-size: 9px; color: #9ca3af; margin-top: 2px;">قيد</div>
                        </div>
                        <div style="text-align: center; background: #f9fafb; padding: 8px 4px; border-radius: 6px;">
                            <div style="font-size: 17px; font-weight: 700; color: #059669;">{{ number_format($p->invoice_count) }}</div>
                            <div style="font-size: 9px; color: #9ca3af; margin-top: 2px;">فاتورة</div>
                        </div>
                        <div style="text-align: center; background: #f9fafb; padding: 8px 4px; border-radius: 6px;">
                            <div style="font-size: 13px; font-weight: 700; color: #374151;">{{ number_format($p->total_sales, 0) }}</div>
                            <div style="font-size: 9px; color: #9ca3af; margin-top: 2px;">مبيعات</div>
                        </div>
                    </div>

                    {{-- معلومات القفل --}}
                    @if($p->is_locked && $p->locked_at)
                        <div style="font-size: 10px; color: #9ca3af; margin-bottom: 10px; text-align: center;">
                            قُفل {{ \Illuminate\Support\Carbon::parse($p->locked_at)->diffForHumans() }}
                        </div>
                    @endif

                    {{-- الإجراءات --}}
                    <div style="text-align: center;">
                        @if($p->is_locked)
                            @if(auth()->user()->isSuperAdmin())
                                <button wire:click="unlockPeriod({{ $p->id }})"
                                    onclick="if(!confirm('هل أنت متأكد من فتح هذه الفترة المقفولة؟')) return false;"
                                    style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;
                                           padding: 7px 18px; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 600; width: 100%;">
                                    🔓 فتح الفترة
                                </button>
                            @else
                                <span style="font-size: 11px; color: #9ca3af;">مقفولة — تواصل مع مدير النظام</span>
                            @endif
                        @else
                            <button wire:click="lockPeriod({{ $p->id }})"
                                onclick="if(!confirm('هل أنت متأكد من قفل فترة {{ $p->month_name }} {{ $p->year }}؟ لن يمكن تسجيل قيود فيها بعد القفل.')) return false;"
                                style="background: #dc2626; color: white; border: none;
                                       padding: 7px 18px; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 600; width: 100%;">
                                🔒 قفل الفترة
                            </button>
                        @endif
                    </div>

                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #9ca3af;">
                    <div style="font-size: 48px; margin-bottom: 12px;">📅</div>
                    <div style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        لا توجد فترات لسنة {{ $selectedYear }}
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                        <div style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">
                            اضبط السنة في الأعلى واضغط "إنشاء سنة" لإنشاء 12 فترة شهرية
                        </div>
                        <button wire:click="$set('newYear', {{ $selectedYear }}); generateYear()"
                            style="background: #059669; color: white; padding: 10px 24px;
                                   border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600;">
                            + إنشاء فترات {{ $selectedYear }}
                        </button>
                    @endif
                </div>
            @endforelse
        </div>

    </div>
</x-filament-panels::page>
