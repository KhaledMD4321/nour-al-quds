<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @if($this->supplier_id)
            @php
                $supplier    = $this->getSupplierInfo();
                $lines       = $this->getStatementLines();
                $opening     = $this->getOpeningBalance();
                $totalDebit  = $lines->sum('debit');
                $totalCredit = $lines->sum('credit');
                // رصيد المورد: موجب = علينا له (دائن)
                $closing = $opening + $totalCredit - $totalDebit;
            @endphp

            {{-- ── بيانات المورد ── --}}
            <div style="background: #92400e; color: white; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 18px; font-weight: 700;">{{ $supplier->name }}</div>
                    <div style="font-size: 12px; opacity: .8;">{{ $supplier->code }} | {{ $supplier->phone ?? '—' }}</div>
                </div>
                <div style="text-align: left;">
                    <div style="font-size: 12px; opacity: .8;">الرصيد المستحق</div>
                    <div style="font-size: 24px; font-weight: 800;">
                        {{ number_format(abs($closing), 2) }} ج.م
                        <span style="font-size: 12px; font-weight: 400;">{{ $closing > 0 ? '(مستحق له)' : ($closing < 0 ? '(زيادة دفع)' : '') }}</span>
                    </div>
                </div>
            </div>

            {{-- ── بطاقات الملخص ── --}}
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">رصيد أول المدة</div>
                    <div style="font-size: 17px; font-weight: 700; color: #92400e;">{{ number_format(abs($opening), 2) }}</div>
                    <div style="font-size: 10px; color: #9ca3af;">{{ $opening >= 0 ? 'مستحق له' : 'رصيد سالب' }}</div>
                </div>
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">إجمالي فواتير</div>
                    <div style="font-size: 17px; font-weight: 700; color: #dc2626;">{{ number_format($totalCredit, 2) }}</div>
                </div>
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">إجمالي مدفوعات</div>
                    <div style="font-size: 17px; font-weight: 700; color: #059669;">{{ number_format($totalDebit, 2) }}</div>
                </div>
                <div style="background: {{ $closing > 0 ? '#fffbeb' : '#f0fdf4' }}; border: 1px solid {{ $closing > 0 ? '#fde68a' : '#86efac' }}; border-radius: 10px; padding: 14px; text-align: center;">
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 4px;">صافي المستحق</div>
                    <div style="font-size: 17px; font-weight: 700; color: {{ $closing > 0 ? '#92400e' : '#059669' }};">{{ number_format(abs($closing), 2) }}</div>
                    <div style="font-size: 10px; color: #9ca3af;">{{ $closing > 0 ? 'مستحق له' : 'رصيد زيادة' }}</div>
                </div>
            </div>

            {{-- ── زر الطباعة ── --}}
            <div style="margin-bottom: 12px; text-align: left;">
                <a href="{{ route('supplier-statement.print', ['supplier' => $this->supplier_id, 'from' => $this->from_date, 'to' => $this->to_date]) }}"
                   target="_blank"
                   style="display:inline-block; background:#92400e; color:white; padding:8px 20px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:600;">
                    🖨️ طباعة PDF
                </a>
            </div>

            {{-- ── جدول الحركات ── --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f9fafb;">
                        <tr>
                            <th style="padding:10px 14px; text-align:right; font-size:12px; font-weight:600; color:#6b7280; border-bottom:1px solid #e5e7eb;">التاريخ</th>
                            <th style="padding:10px 14px; text-align:right; font-size:12px; font-weight:600; color:#6b7280; border-bottom:1px solid #e5e7eb;">المرجع</th>
                            <th style="padding:10px 14px; text-align:right; font-size:12px; font-weight:600; color:#6b7280; border-bottom:1px solid #e5e7eb;">البيان</th>
                            <th style="padding:10px 14px; text-align:left; font-size:12px; font-weight:600; color:#6b7280; border-bottom:1px solid #e5e7eb;">مدين (مدفوعات)</th>
                            <th style="padding:10px 14px; text-align:left; font-size:12px; font-weight:600; color:#6b7280; border-bottom:1px solid #e5e7eb;">دائن (فواتير)</th>
                            <th style="padding:10px 14px; text-align:left; font-size:12px; font-weight:600; color:#6b7280; border-bottom:1px solid #e5e7eb;">الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- رصيد أول المدة --}}
                        <tr style="background:#f3f4f6; border-bottom:1px solid #e5e7eb;">
                            <td style="padding:10px 14px; font-size:13px; color:#9ca3af;">—</td>
                            <td style="padding:10px 14px; font-size:13px; color:#9ca3af;">—</td>
                            <td style="padding:10px 14px; font-size:13px; font-weight:600;">رصيد أول المدة</td>
                            <td style="padding:10px 14px; text-align:left; color:#9ca3af;">—</td>
                            <td style="padding:10px 14px; text-align:left; color:#9ca3af;">—</td>
                            <td style="padding:10px 14px; text-align:left; font-weight:700; color:#92400e;">
                                {{ number_format(abs($opening), 2) }}
                                <span style="font-size:10px; color:#9ca3af;">{{ $opening >= 0 ? 'مستحق له' : 'دائن' }}</span>
                            </td>
                        </tr>

                        @php $running = $opening; @endphp
                        @forelse($lines as $line)
                            @php $running += $line->credit - $line->debit; @endphp
                            <tr style="border-bottom:1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                                <td style="padding:10px 14px; font-size:13px; white-space:nowrap;">{{ $line->date->format('d/m/Y') }}</td>
                                <td style="padding:10px 14px; font-family:monospace; font-size:12px; color:#4b5563;">{{ $line->reference }}</td>
                                <td style="padding:10px 14px; font-size:13px;">{{ $line->description }}</td>
                                <td style="padding:10px 14px; text-align:left; color:{{ $line->debit > 0 ? '#059669' : '#d1d5db' }}; font-weight:{{ $line->debit > 0 ? '600' : '400' }};">
                                    {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}
                                </td>
                                <td style="padding:10px 14px; text-align:left; color:{{ $line->credit > 0 ? '#dc2626' : '#d1d5db' }}; font-weight:{{ $line->credit > 0 ? '600' : '400' }};">
                                    {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}
                                </td>
                                <td style="padding:10px 14px; text-align:left; font-weight:700; white-space:nowrap; color:{{ $running >= 0 ? '#92400e' : '#059669' }};">
                                    {{ number_format(abs($running), 2) }}
                                    <span style="font-size:10px; color:#9ca3af; font-weight:400;">{{ $running >= 0 ? 'مستحق له' : 'رصيد دائن' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:30px; text-align:center; color:#9ca3af; font-size:14px;">
                                    لا توجد حركات في الفترة المحددة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($lines->isNotEmpty())
                    <tfoot style="background:#111827; color:white;">
                        <tr>
                            <td colspan="3" style="padding:12px 14px; font-weight:700; font-size:14px;">الإجمالي</td>
                            <td style="padding:12px 14px; text-align:left; font-weight:700; font-size:14px; color:#86efac;">{{ number_format($totalDebit, 2) }}</td>
                            <td style="padding:12px 14px; text-align:left; font-weight:700; font-size:14px; color:#fca5a5;">{{ number_format($totalCredit, 2) }}</td>
                            <td style="padding:12px 14px; text-align:left; font-weight:700; font-size:14px;">
                                {{ number_format(abs($closing), 2) }}
                                <span style="font-size:11px; font-weight:400; color:#9ca3af;">{{ $closing >= 0 ? 'مستحق له' : 'رصيد دائن' }}</span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

        @else
            <div style="text-align:center; padding:60px; color:#9ca3af; font-size:15px; background:white; border:1px solid #e5e7eb; border-radius:12px;">
                <div style="font-size:40px; margin-bottom:12px;">📋</div>
                اختر مورد لعرض كشف حسابه
            </div>
        @endif

    </div>
</x-filament-panels::page>
