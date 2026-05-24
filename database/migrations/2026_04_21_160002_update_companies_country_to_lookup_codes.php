<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Map existing Arabic free-text country values → lookup codes. */
    private array $mapping = [
        'مصر' => 'egypt',
        'أمريكا' => 'usa',
        'تركيا' => 'turkey',
        'الصين' => 'china',
        'إيطاليا' => 'italy',
        'ألمانيا' => 'germany',
        'إسبانيا' => 'spain',
        'الهند' => 'india',
        'الإمارات' => 'uae',
        'السعودية' => 'saudi',
    ];

    public function up(): void
    {
        foreach ($this->mapping as $arabic => $code) {
            DB::table('companies')
                ->where('country', $arabic)
                ->update(['country' => $code]);
        }
    }

    public function down(): void
    {
        $reversed = array_flip($this->mapping);

        foreach ($reversed as $code => $arabic) {
            DB::table('companies')
                ->where('country', $code)
                ->update(['country' => $arabic]);
        }
    }
};
