<x-filament-panels::page.simple>
    @php
        $companyName  = \App\Models\SystemSetting::get('company.name', 'نور القدس');
        $logoPath     = \App\Models\SystemSetting::get('company.logo', '');
        $headerColor  = \App\Models\SystemSetting::get('print.header_color', '#1e40af');
        $logoFullPath = $logoPath ? public_path('storage/' . $logoPath) : null;
        $logoExists   = $logoFullPath && file_exists($logoFullPath);
        $logoVer      = $logoExists ? filemtime($logoFullPath) : 0;
    @endphp

    {{-- ══ هيدر: لوجو + اسم الشركة ══ --}}
    <div style="text-align: center; margin-bottom: 28px; direction: rtl; font-family: Cairo, sans-serif;">

        @if($logoExists)
            <img src="{{ asset('storage/' . $logoPath) }}?v={{ $logoVer }}"
                 alt="{{ $companyName }}"
                 style="height: 80px; width: auto; margin: 0 auto 16px; display: block;
                        object-fit: contain; border-radius: 12px;" />
        @else
            {{-- أيقونة افتراضية لو مفيش لوجو مرفوع --}}
            <div style="width: 72px; height: 72px; border-radius: 18px;
                        background: {{ $headerColor }}; color: #fff;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 32px; margin: 0 auto 16px;
                        box-shadow: 0 4px 16px {{ $headerColor }}44;">
                🏪
            </div>
        @endif

        <h1 style="font-size: 22px; font-weight: 800; color: {{ $headerColor }};
                   margin: 0 0 6px; letter-spacing: -0.3px;">
            {{ $companyName }}
        </h1>
        <p style="font-size: 13px; color: #9ca3af; margin: 0;">
            نظام إدارة الموارد المؤسسية
        </p>
    </div>

    {{-- ══ نموذج تسجيل الدخول ══ --}}
    {{ $this->content }}

    {{-- ══ فوتر ══ --}}
    <div style="text-align: center; margin-top: 20px;
                font-size: 11px; color: #d1d5db;
                font-family: Cairo, sans-serif;">
        {{ $companyName }} &copy; {{ date('Y') }}
    </div>

</x-filament-panels::page.simple>
