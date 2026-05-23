<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * تسجيل فونت Amiri العربي في DomPDF
 *
 * يُشغَّل مرة واحدة بعد النشر أو بعد تحديث الفونتات:
 *   php artisan fonts:register-arabic
 */
class RegisterArabicFonts extends Command
{
    protected $signature   = 'fonts:register-arabic';
    protected $description = 'Register Amiri Arabic font for DomPDF PDF generation';

    public function handle(): int
    {
        $fontDir = storage_path('fonts');

        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0755, true);
        }

        $regular = "$fontDir/Amiri-Regular.ttf";
        $bold    = "$fontDir/Amiri-Bold.ttf";

        if (! file_exists($regular)) {
            $this->error("❌ Amiri-Regular.ttf not found in $fontDir");
            $this->line('   Run: curl -L -o storage/fonts/Amiri-Regular.ttf "https://github.com/alif-type/amiri/raw/main/Amiri-Regular.ttf"');
            return 1;
        }

        try {
            /** @var \Barryvdh\DomPDF\PDF $pdfWrapper */
            $pdfWrapper = app('dompdf.wrapper');
            $dompdf     = $pdfWrapper->getDomPDF();
            $canvas     = $dompdf->getCanvas();
            $metrics    = $canvas->get_dompdf()->getFontMetrics();

            $metrics->registerFont(
                ['family' => 'amiri', 'style' => 'normal', 'weight' => 'normal'],
                $regular
            );
            $this->info('✓ Registered Amiri Regular');

            if (file_exists($bold)) {
                $metrics->registerFont(
                    ['family' => 'amiri', 'style' => 'normal', 'weight' => 'bold'],
                    $bold
                );
                $this->info('✓ Registered Amiri Bold');
            }

            $this->info('');
            $this->info('✅ Amiri font registered. PDFs will now render Arabic correctly.');
            return 0;

        } catch (\Throwable $e) {
            $this->error('Registration failed: ' . $e->getMessage());
            $this->line('DomPDF will still auto-discover TTF files in font_dir on first use.');
            return 1;
        }
    }
}
