<x-filament-panels::page.simple>

    {{-- ══ لوجو + اسم الشركة ══ --}}
    @php
        $companyName  = \App\Models\SystemSetting::get('company.name', 'نور القدس');
        $logoPath     = \App\Models\SystemSetting::get('company.logo', '');
        $headerColor  = \App\Models\SystemSetting::get('print.header_color', '#1e40af');
        $logoFullPath = $logoPath ? public_path('storage/' . $logoPath) : null;
        $logoExists   = $logoFullPath && file_exists($logoFullPath);
        $logoVer      = $logoExists ? filemtime($logoFullPath) : 0;
    @endphp

    <div style="text-align: center; margin-bottom: 24px; direction: rtl; font-family: Cairo, sans-serif;">

        {{-- اللوجو أو أيقونة افتراضية --}}
        @if($logoExists)
            <img src="{{ asset('storage/' . $logoPath) }}?v={{ $logoVer }}"
                 alt="{{ $companyName }}"
                 style="height: 72px; width: auto; margin: 0 auto 12px; display: block; object-fit: contain;" />
        @else
            <div style="width: 72px; height: 72px; border-radius: 18px;
                        background: {{ $headerColor }}; color: #fff;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 32px; margin: 0 auto 12px; box-shadow: 0 4px 14px {{ $headerColor }}55;">
                🏪
            </div>
        @endif

        {{-- اسم الشركة --}}
        <h1 style="font-size: 20px; font-weight: 800; color: #111827;
                   margin: 0 0 4px; letter-spacing: -0.3px;">
            {{ $companyName }}
        </h1>
        <p style="font-size: 13px; color: #6b7280; margin: 0;">
            نظام إدارة الموارد المؤسسية
        </p>
    </div>

    {{-- ══ نموذج تسجيل الدخول ══ --}}
    {{ $this->content }}

</x-filament-panels::page.simple>
