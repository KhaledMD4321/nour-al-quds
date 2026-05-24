<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'نور القدس')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #dde1e7;
            direction: rtl;
            min-height: 100vh;
            color: #1a1a1a;
        }

        /* ── شريط الأدوات ── */
        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 200;
            background: #1e3a5f;
            color: white;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
            min-height: 52px;
        }
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .toolbar-center {
            flex: 1;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: #e2e8f0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            padding: 0 12px;
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .toolbar-brand {
            font-size: 13px;
            font-weight: 800;
            color: #93c5fd;
            white-space: nowrap;
        }
        .btn-toolbar {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: opacity .15s, transform .1s;
            white-space: nowrap;
        }
        .btn-toolbar:hover  { opacity: .85; transform: translateY(-1px); }
        .btn-toolbar:active { transform: translateY(0); }
        .btn-back  { background: rgba(255,255,255,0.15); color: white; }
        .btn-print { background: #2563eb; color: white; }
        .btn-pdf   { background: #dc2626; color: white; }

        /* ── غلاف صفحة A4 ── */
        .page-wrapper {
            max-width: 794px;          /* ~A4 width at 96dpi */
            margin: 24px auto 40px;
            background: white;
            box-shadow: 0 4px 28px rgba(0,0,0,0.18);
            padding: 22mm 20mm 20mm;
            min-height: 1070px;
        }

        /* ── طباعة: أخفِ الشريط وأزِل wrapper padding ── */
        @media print {
            @page { size: A4; margin: 12mm 14mm 14mm 14mm; }

            body { background: white !important; }

            .print-toolbar { display: none !important; }

            .page-wrapper {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                min-height: 0 !important;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    {{-- ── شريط الأدوات (شاشة فقط) ── --}}
    <div class="print-toolbar">

        <div class="toolbar-right">
            <button class="btn-toolbar btn-back" onclick="history.back()">
                &#8594; رجوع
            </button>
            <span class="toolbar-brand">&#127981; نور القدس</span>
        </div>

        <div class="toolbar-center">@yield('toolbar-title', '')</div>

        <div class="toolbar-left">
            <a id="btn-pdf-dl" href="#" class="btn-toolbar btn-pdf">
                &#11015; PDF
            </a>
            <button class="btn-toolbar btn-print" onclick="window.print()">
                &#128438; طباعة
            </button>
        </div>

    </div>

    {{-- ── محتوى المستند ── --}}
    <div class="page-wrapper">
        @yield('content')
    </div>

    <script>
        // بناء رابط PDF عبر إضافة pdf=1 مع الحفاظ على باقي المعاملات
        (function () {
            var url = new URL(window.location.href);
            url.searchParams.set('pdf', '1');
            document.getElementById('btn-pdf-dl').href = url.toString();
        })();

        // اختصار Ctrl+P / Cmd+P
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>

</body>
</html>
