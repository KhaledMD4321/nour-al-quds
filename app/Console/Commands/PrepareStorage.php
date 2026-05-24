<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mpdf\Mpdf;

/**
 * تحضير مجلدات التخزين اللازمة للنظام بعد كل نشر جديد
 *
 * يُشغَّل ضمن سكريبت النشر:
 *   php artisan app:prepare-storage
 */
class PrepareStorage extends Command
{
    protected $signature = 'app:prepare-storage';

    protected $description = 'Create required storage directories (mpdf-temp, exports, fonts)';

    public function handle(): int
    {
        $dirs = [
            storage_path('app/mpdf-temp'),
            storage_path('app/exports'),
            storage_path('fonts'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->info("Created: $dir");
            } else {
                $this->line("Exists:  $dir");
            }
        }

        // التحقق من مكتبة mPDF
        if (class_exists(Mpdf::class)) {
            $this->info('✓ mPDF library available (Arabic RTL supported via xbriyaz built-in font)');
        } else {
            $this->error('✗ mPDF not found — run: composer install --no-dev');

            return 1;
        }

        // فحص اختياري لفونت Amiri (أفضل جودة للـ PDF، لكن xbriyaz كافٍ)
        $amiri = storage_path('fonts/Amiri-Regular.ttf');
        if (file_exists($amiri)) {
            $this->info('✓ Amiri font found — will be used in PDFs');
        } else {
            $this->line('ℹ Amiri font not found (optional). Using built-in xbriyaz.');
            $this->line('  To install: curl -L -o storage/fonts/Amiri-Regular.ttf');
            $this->line('  https://github.com/alif-type/amiri/raw/main/Amiri-Regular.ttf');
        }

        $this->newLine();
        $this->info('✅ Storage ready.');

        return 0;
    }
}
