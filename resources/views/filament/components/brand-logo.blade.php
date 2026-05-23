@php
    $companyName = \App\Models\SystemSetting::get('company.name', 'نور القدس');
    $logoPath    = \App\Models\SystemSetting::get('company.logo', '');
    $headerColor = \App\Models\SystemSetting::get('print.header_color', '#1e40af');

    $words     = explode(' ', trim($companyName));
    $shortName = count($words) >= 2
        ? implode(' ', array_slice($words, -2))
        : $companyName;

    // Cache-busting: use file mtime so browser reloads after a new upload
    $logoFullPath = $logoPath ? public_path('storage/' . $logoPath) : null;
    $logoExists   = $logoFullPath && file_exists($logoFullPath);
    $logoVer      = $logoExists ? filemtime($logoFullPath) : 0;
@endphp

<div style="display: flex; align-items: center; gap: 10px;
            direction: rtl; font-family: Cairo, sans-serif; padding: 2px 0;">

    {{-- اللوجو أو أيقونة افتراضية --}}
    @if($logoExists)
        <img src="{{ asset('storage/' . $logoPath) }}?v={{ $logoVer }}"
             alt="{{ $companyName }}"
             style="height: 36px; width: auto; object-fit: contain; flex-shrink: 0;" />
    @else
        <div style="width: 36px; height: 36px; border-radius: 10px;
                    background: {{ $headerColor }}; color: #fff;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 18px; flex-shrink: 0; box-shadow: 0 2px 8px {{ $headerColor }}44;">
            🏪
        </div>
    @endif

    {{-- الاسم --}}
    <div>
        <div style="font-size: 14px; font-weight: 800; color: #111827; line-height: 1.2;">
            {{ $shortName }}
        </div>
        <div style="font-size: 10px; color: #9ca3af; line-height: 1;">ERP</div>
    </div>

</div>
