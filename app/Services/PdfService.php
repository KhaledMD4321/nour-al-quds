<?php

namespace App\Services;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class PdfService
{
    /**
     * عرض PDF في المتصفح (inline)
     */
    public static function stream(string $view, array $data, string $filename, string $orientation = 'P')
    {
        $html = view($view, $data)->render();
        $mpdf = self::createInstance($orientation);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * تحميل PDF مباشرة
     */
    public static function download(string $view, array $data, string $filename, string $orientation = 'P')
    {
        $html = view($view, $data)->render();
        $mpdf = self::createInstance($orientation);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected static function createInstance(string $orientation = 'P'): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // إضافة مجلد storage/fonts للفونتات المخصصة (Amiri)
        $customFontDir = storage_path('fonts');
        if (is_dir($customFontDir)) {
            $fontDirs[] = $customFontDir;
        }

        // تجهيز بيانات Amiri لو الملفات موجودة
        $amiriConfig = [];
        if (file_exists("$customFontDir/Amiri-Regular.ttf")) {
            $amiriConfig['amiri'] = [
                'R' => 'Amiri-Regular.ttf',
                'B' => file_exists("$customFontDir/Amiri-Bold.ttf") ? 'Amiri-Bold.ttf' : 'Amiri-Regular.ttf',
            ];
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $orientation,
            'default_font' => 'xbriyaz', // فونت عربي مدمج في mPDF
            'default_font_size' => 11,
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 12,
            'margin_bottom' => 14,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData + $amiriConfig,
            'tempDir' => $tempDir,
            'autoArabic' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
            'allow_charset_conversion' => true,
        ]);

        $mpdf->SetDirectionality('rtl');

        return $mpdf;
    }

    /**
     * نسخة مخصصة لـ A5 (إيصال البيع السريع)
     */
    public static function streamA5(string $view, array $data, string $filename)
    {
        $html = view($view, $data)->render();
        $mpdf = self::createInstanceA5();
        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    protected static function createInstanceA5(): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $customFontDir = storage_path('fonts');
        if (is_dir($customFontDir)) {
            $fontDirs[] = $customFontDir;
        }

        $amiriConfig = [];
        if (file_exists("$customFontDir/Amiri-Regular.ttf")) {
            $amiriConfig['amiri'] = [
                'R' => 'Amiri-Regular.ttf',
                'B' => file_exists("$customFontDir/Amiri-Bold.ttf") ? 'Amiri-Bold.ttf' : 'Amiri-Regular.ttf',
            ];
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5',
            'orientation' => 'P',
            'default_font' => 'xbriyaz',
            'default_font_size' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData + $amiriConfig,
            'tempDir' => $tempDir,
            'autoArabic' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetDirectionality('rtl');

        return $mpdf;
    }
}
