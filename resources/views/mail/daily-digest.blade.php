<x-mail::message>
# ☀️ ملخص نور القدس — {{ $digest['date'] }}

| البند | القيمة |
|:---|---:|
| مبيعات الأمس | {{ number_format($digest['yesterday_sales'], 2) }} ج.م ({{ $digest['yesterday_count'] }} فاتورة) |
| النقدية الحالية | {{ number_format($digest['cash'], 2) }} ج.م |
| نمو الشهر (مقابل الشهر الماضي) | {{ $digest['month_growth']['up'] ? '▲' : '▼' }} {{ number_format(abs($digest['month_growth']['delta']), 1) }}% |
| فواتير محتاجة تحصيل | {{ $digest['open_count'] }} — {{ number_format($digest['open_total'], 2) }} ج.م |
| متأخرات العملاء | {{ number_format($digest['overdue_ar'], 2) }} ج.م |
| شيكات تستحق هذا الأسبوع | {{ $digest['cheques_count'] }} (وارد {{ number_format($digest['cheques_in'], 2) }} / صادر {{ number_format($digest['cheques_out'], 2) }}) |
@if($digest['top_product'])
| أعلى صنف مبيعاً أمس | {{ $digest['top_product'] }} — {{ number_format($digest['top_product_value'], 2) }} ج.م |
@endif

@if(count($alerts) > 0)
## ⚠️ تنبيهات تحتاج انتباهك

@foreach($alerts as $alert)
- **{{ $alert['title'] }}** — {{ $alert['body'] }}
@endforeach
@else
✅ لا توجد تنبيهات حرجة اليوم.
@endif

<x-mail::button :url="rtrim(config('app.url'), '/').'/admin'">
فتح لوحة التحكم
</x-mail::button>

— نور القدس ERP
</x-mail::message>
