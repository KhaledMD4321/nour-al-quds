<?php

namespace Tests\Feature;

use App\Mail\DailyDigestMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SendDailyDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_emails_admins_and_creates_in_app_notifications(): void
    {
        Mail::fake();

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.local',
            'password' => 'secret-pass',
            'is_active' => true,
        ]);
        $owner->assignRole('super_admin');

        $this->artisan('app:daily-digest')->assertSuccessful();

        Mail::assertSent(DailyDigestMail::class);

        // إشعار "ملخص اليوم" داخل النظام على الأقل
        $this->assertGreaterThanOrEqual(1, $owner->fresh()->notifications()->count());
    }

    public function test_it_is_safe_when_there_are_no_recipients(): void
    {
        // لا يوجد super_admin → يخرج بنجاح دون إرسال
        Mail::fake();

        $this->artisan('app:daily-digest')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
