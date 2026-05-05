<x-filament-panels::page>
    <div dir="rtl" style="font-family: Cairo, sans-serif;">

        {{-- ── جدول الفترات ── --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
            <div style="background: #1e40af; color: white; padding: 12px 16px; font-weight: 700; font-size: 14px;">
                الفترات المالية
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">السنة</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الشهر</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">من</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">إلى</th>
                        <th style="padding: 10px 14px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">الحالة</th>
                        <th style="padding: 10px 14px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">مقفول بواسطة</th>
                        <th style="padding: 10px 14px; text-align: center; font-size: 12px; font-weight: 600; color: #6b7280; border-bottom: 1px solid #e5e7eb;">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->getPeriods() as $period)
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding: 10px 14px; font-weight: 600; font-size: 13px;">{{ $period->year }}</td>
                            <td style="padding: 10px 14px; font-size: 13px;">
                                {{ ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'][$period->month - 1] ?? $period->month }}
                            </td>
                            <td style="padding: 10px 14px; font-size: 12px; color: #6b7280;">{{ $period->start_date->format('Y-m-d') }}</td>
                            <td style="padding: 10px 14px; font-size: 12px; color: #6b7280;">{{ $period->end_date->format('Y-m-d') }}</td>
                            <td style="padding: 10px 14px; text-align: center;">
                                @if($period->is_locked)
                                    <span style="display:inline-block; padding:2px 12px; font-size:11px; border-radius:999px; background:#fef2f2; color:#991b1b; font-weight:600;">🔒 مقفول</span>
                                @else
                                    <span style="display:inline-block; padding:2px 12px; font-size:11px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:600;">🔓 مفتوح</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; font-size: 12px; color: #9ca3af;">
                                {{ $period->lockedByUser?->name ?? '—' }}
                                @if($period->locked_at)
                                    <br><span style="font-size: 11px;">{{ $period->locked_at->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                @if($period->is_locked)
                                    <button wire:click="unlockPeriod({{ $period->id }})"
                                        wire:confirm="هل تريد فتح هذه الفترة؟"
                                        style="background:#fef2f2; color:#dc2626; border:1px solid #fca5a5; border-radius:6px; padding:4px 12px; font-size:12px; cursor:pointer; font-family: Cairo, sans-serif;">
                                        فتح الفترة
                                    </button>
                                @else
                                    <button wire:click="lockPeriod({{ $period->id }})"
                                        wire:confirm="هل تريد قفل هذه الفترة؟ لن يمكن إضافة معاملات جديدة فيها."
                                        style="background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; border-radius:6px; padding:4px 12px; font-size:12px; cursor:pointer; font-family: Cairo, sans-serif;">
                                        قفل الفترة
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #9ca3af;">
                                لا توجد فترات مالية. يمكنك إنشاؤها من الإعدادات.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Rollback ── --}}
        @if(auth()->user()?->isSuperAdmin())
        <div style="background: #fff5f5; border: 2px solid #fca5a5; border-radius: 12px; padding: 20px;">
            <div style="font-weight: 700; color: #991b1b; font-size: 15px; margin-bottom: 12px;">
                ⚠ منطقة الخطر — Rollback
            </div>
            <div style="font-size: 13px; color: #6b7280; margin-bottom: 16px; line-height: 1.8;">
                يمكنك هنا إلغاء جميع المعاملات (فواتير، مقبوضات، مدفوعات، قيود) في فترة زمنية محددة.
                <strong style="color: #dc2626;">هذا الإجراء نهائي ولا يمكن التراجع عنه.</strong>
            </div>
            <div style="background: white; border-radius: 8px; padding: 16px;">
                {{ $this->form }}
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>
