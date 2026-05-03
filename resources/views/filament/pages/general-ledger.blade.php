<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر البحث ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @if($this->account_id)
            {{-- ── اسم الحساب ── --}}
            <div style="background: #1e40af; color: white; border-radius: 10px; padding: 14px 20px; margin-bottom: 16px; font-size: 16px; font-weight: 700;">
                دفتر أستاذ: {{ $this->getAccountName() }}
            </div>

            {{-- ── جدول الحركات ── --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f9fafb;">
                        <tr>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">التاريخ</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">رقم القيد</th>
                            <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">البيان</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">مدين</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">دائن</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $running = 0; @endphp
                        @forelse($this->getLines() as $line)
                            @php
                                $running += (float)$line->debit - (float)$line->credit;
                                $isDebit   = (float)$line->debit  > 0;
                                $isCredit  = (float)$line->credit > 0;
                            @endphp
                            <tr style="border-bottom: 1px solid #f3f4f6; transition: background .1s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                                <td style="padding: 10px 14px; font-size: 13px; white-space: nowrap;">
                                    {{ $line->journalEntry->entry_date->format('d/m/Y') }}
                                </td>
                                <td style="padding: 10px 14px; font-family: monospace; font-size: 12px; color: #4b5563;">
                                    {{ $line->journalEntry->entry_number }}
                                </td>
                                <td style="padding: 10px 14px; font-size: 13px;">
                                    {{ $line->description ?: $line->journalEntry->description }}
                                </td>
                                <td style="padding: 10px 14px; text-align: left; color: {{ $isDebit ? '#dc2626' : '#d1d5db' }}; font-weight: {{ $isDebit ? '600' : '400' }};">
                                    {{ $isDebit ? number_format((float)$line->debit, 2) : '—' }}
                                </td>
                                <td style="padding: 10px 14px; text-align: left; color: {{ $isCredit ? '#059669' : '#d1d5db' }}; font-weight: {{ $isCredit ? '600' : '400' }};">
                                    {{ $isCredit ? number_format((float)$line->credit, 2) : '—' }}
                                </td>
                                <td style="padding: 10px 14px; text-align: left; font-weight: 700; white-space: nowrap; color: {{ $running >= 0 ? '#1e40af' : '#dc2626' }};">
                                    {{ number_format(abs($running), 2) }}
                                    <span style="font-size: 10px; color: #9ca3af; font-weight: 400; margin-right: 4px;">
                                        {{ $running >= 0 ? 'مدين' : 'دائن' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 40px; text-align: center; color: #9ca3af; font-size: 14px;">
                                    لا توجد حركات في الفترة المحددة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if($this->account_id && $this->getLines()->isNotEmpty())
                        @php $totals = $this->getTotals(); @endphp
                        <tfoot style="background: #f1f5f9; border-top: 2px solid #e2e8f0;">
                            <tr>
                                <td colspan="3" style="padding: 12px 14px; font-weight: 700; font-size: 14px;">الإجمالي</td>
                                <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #dc2626; font-size: 14px;">
                                    {{ number_format($totals['debit'], 2) }}
                                </td>
                                <td style="padding: 12px 14px; text-align: left; font-weight: 700; color: #059669; font-size: 14px;">
                                    {{ number_format($totals['credit'], 2) }}
                                </td>
                                <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 14px; color: {{ $totals['balance'] >= 0 ? '#1e40af' : '#dc2626' }};">
                                    {{ number_format(abs($totals['balance']), 2) }}
                                    <span style="font-size: 10px; font-weight: 400; color: #9ca3af; margin-right: 4px;">
                                        {{ $totals['balance'] >= 0 ? 'مدين' : 'دائن' }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

        @else
            <div style="text-align: center; padding: 60px 20px; color: #9ca3af; font-size: 15px;">
                <div style="font-size: 40px; margin-bottom: 12px;">📖</div>
                اختر حساب من القائمة لعرض دفتر الأستاذ
            </div>
        @endif

    </div>
</x-filament-panels::page>
