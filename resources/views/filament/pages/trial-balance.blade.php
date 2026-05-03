<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── فلتر ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            {{ $this->form }}
        </div>

        @php
            $balances = $this->getBalances();
            $totals = [
                'debit'          => $balances->sum('total_debit'),
                'credit'         => $balances->sum('total_credit'),
                'balance_debit'  => $balances->sum('balance_debit'),
                'balance_credit' => $balances->sum('balance_credit'),
            ];
            $diff       = abs($totals['balance_debit'] - $totals['balance_credit']);
            $isBalanced = $diff < 0.01;
        @endphp

        {{-- ── مؤشر التوازن ── --}}
        <div style="padding: 12px 20px; margin-bottom: 16px; border-radius: 10px; font-weight: 700; font-size: 14px;
            {{ $isBalanced
                ? 'background: #dcfce7; color: #166534; border: 1px solid #86efac;'
                : 'background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5;' }}">
            @if($isBalanced)
                ✓ الميزان متوازن — المدين يساوي الدائن
            @else
                ✗ الميزان غير متوازن — الفرق: {{ number_format($diff, 2) }} ج.م
            @endif
        </div>

        {{-- ── الجدول ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #1e40af; color: white;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">الكود</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">اسم الحساب</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px;">النوع</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">إجمالي مدين</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px;">إجمالي دائن</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px; color: #fca5a5;">رصيد مدين</th>
                        <th style="padding: 10px 14px; text-align: left; font-size: 12px; color: #86efac;">رصيد دائن</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $b)
                        @php
                            $typeStyle = match($b->type) {
                                'asset'     => 'background:#dbeafe;color:#1e40af;',
                                'liability' => 'background:#fef3c7;color:#92400e;',
                                'equity'    => 'background:#f3e8ff;color:#6b21a8;',
                                'revenue'   => 'background:#dcfce7;color:#166534;',
                                'expense'   => 'background:#fef2f2;color:#991b1b;',
                                default     => 'background:#f3f4f6;color:#374151;',
                            };
                            $typeLabel = match($b->type) {
                                'asset'     => 'أصول',
                                'liability' => 'التزامات',
                                'equity'    => 'حقوق ملكية',
                                'revenue'   => 'إيرادات',
                                'expense'   => 'مصروفات',
                                default     => $b->type,
                            };
                        @endphp
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 8px 14px; font-family: monospace; font-size: 12px; color: #374151;">
                                {{ $b->code }}
                            </td>
                            <td style="padding: 8px 14px; font-weight: 600; font-size: 13px;">
                                {{ $b->name }}
                            </td>
                            <td style="padding: 8px 14px;">
                                <span style="display: inline-block; padding: 2px 10px; font-size: 10px; border-radius: 999px; {{ $typeStyle }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px;">
                                {{ number_format($b->total_debit, 2) }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-size: 13px;">
                                {{ number_format($b->total_credit, 2) }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-weight: 600; color: #dc2626;">
                                {{ $b->balance_debit > 0 ? number_format($b->balance_debit, 2) : '' }}
                            </td>
                            <td style="padding: 8px 14px; text-align: left; font-weight: 600; color: #059669;">
                                {{ $b->balance_credit > 0 ? number_format($b->balance_credit, 2) : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #9ca3af;">
                                لا توجد بيانات في الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot style="background: #111827; color: white;">
                    <tr>
                        <td colspan="3" style="padding: 12px 14px; font-weight: 700; font-size: 14px;">الإجمالي</td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 14px;">
                            {{ number_format($totals['debit'], 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 14px;">
                            {{ number_format($totals['credit'], 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 14px; color: #fca5a5;">
                            {{ number_format($totals['balance_debit'], 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: left; font-weight: 700; font-size: 14px; color: #86efac;">
                            {{ number_format($totals['balance_credit'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</x-filament-panels::page>
