@extends('layouts.print-preview')

@php
    $docLabel = 'إيصال بيع سريع — ' . $sale->reference_number;
@endphp

@section('title', $docLabel)
@section('toolbar-title', $docLabel . ($sale->customer_name ? ' | ' . $sale->customer_name : ''))

@section('styles')
<style>
    body { font-size: 12px; color: #1a1a1a; }

    .header {
        text-align: center;
        border-bottom: 2px solid #1a1a1a;
        padding-bottom: 12px; margin-bottom: 15px;
    }
    .company-name { font-size: 18px; font-weight: 800; }
    .company-sub  { font-size: 10px; color: #555; margin-top: 3px; }
    .receipt-title { font-size: 14px; color: #333; margin-top: 6px; font-weight: 700; }

    .info-grid { display: table; width: 100%; margin-bottom: 12px; }
    .info-row  { display: table-row; }
    .info-cell { display: table-cell; font-size: 11px; color: #444; padding: 2px 0; width: 50%; }

    table {
        width: 100%; border-collapse: collapse; margin-bottom: 12px;
    }
    th {
        background: #f0f0f0; padding: 6px 8px; text-align: right;
        font-size: 11px; border: 1px solid #ccc; font-weight: 700;
    }
    td { padding: 5px 8px; border: 1px solid #ddd; font-size: 11px; }
    .total-row td { font-weight: 700; font-size: 13px; background: #f5f5f5; }

    .notes-box {
        font-size: 10px; color: #555; margin-bottom: 10px;
        padding: 6px; background: #fafafa; border: 1px solid #eee;
    }

    .doc-footer {
        text-align: center; font-size: 10px; color: #888;
        margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px;
    }
</style>
@endsection

@section('content')

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $company?->name ?? 'نور القدس' }}</div>
        @if($company?->address)
            <div class="company-sub">{{ $company->address }}</div>
        @endif
        <div class="receipt-title">إيصال بيع سريع</div>
    </div>

    {{-- بيانات الإيصال --}}
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">رقم الإيصال: <strong>{{ $sale->reference_number }}</strong></div>
            <div class="info-cell">التاريخ: {{ $sale->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell">الوحدة: {{ $sale->businessUnit?->name ?? '—' }}</div>
            <div class="info-cell">المخزن: {{ $sale->warehouse?->name ?? '—' }}</div>
        </div>
        @if($sale->customer_name)
            <div class="info-row">
                <div class="info-cell">العميل: <strong>{{ $sale->customer_name }}</strong></div>
                <div class="info-cell">الكاشير: {{ $sale->createdBy?->name ?? '—' }}</div>
            </div>
        @else
            <div class="info-row">
                <div class="info-cell">الكاشير: {{ $sale->createdBy?->name ?? '—' }}</div>
                <div class="info-cell"></div>
            </div>
        @endif
    </div>

    {{-- البنود --}}
    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th>الصنف</th>
                <th style="width:12%; text-align:center">الكمية</th>
                <th style="width:15%; text-align:center">السعر</th>
                <th style="width:15%; text-align:center">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $i => $item)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $item->product?->name ?? 'صنف محذوف' }}</td>
                    <td style="text-align:center">{{ number_format($item->quantity, 1) }}</td>
                    <td style="text-align:center">{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align:center">{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align:center;">الإجمالي الكلي</td>
                <td style="text-align:center;">{{ number_format($sale->total_amount, 2) }} ج.م</td>
            </tr>
        </tbody>
    </table>

    {{-- ملاحظات --}}
    @if($sale->notes)
        <div class="notes-box">ملاحظات: {{ $sale->notes }}</div>
    @endif

    <div class="doc-footer">
        شكراً لتعاملكم معنا
        @if($company?->phone) ● {{ $company->phone }} @endif
    </div>

@endsection
