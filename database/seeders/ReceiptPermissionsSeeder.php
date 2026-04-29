<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReceiptPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // نمط التسمية المتسق مع باقي النظام: finance.receipt.*
        $permissions = [
            'finance.receipt.view',    // عرض قائمة + تفاصيل
            'finance.receipt.create',  // إنشاء إيصال جديد
            'finance.receipt.delete',  // أرشفة (soft-delete) — super_admin فقط
            'finance.receipt.print',   // طباعة الإيصال
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Super Admin — يحصل على كل الصلاحيات صريحاً
        // (النظام لا يستخدم Gate::before — الصلاحيات مضافة مباشرة على الدور)
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // مدير الفرع (unit_manager) — كل صلاحيات الإيصالات
        $manager = Role::where('name', 'unit_manager')->first();
        if ($manager) {
            $manager->givePermissionTo($permissions);
        }

        // محاسب — عرض + إنشاء + طباعة (بدون حذف)
        $accountant = Role::where('name', 'accountant')->first();
        if ($accountant) {
            $accountant->givePermissionTo([
                'finance.receipt.view',
                'finance.receipt.create',
                'finance.receipt.print',
            ]);
        }

        // كاشير — عرض + إنشاء + طباعة (بدون حذف)
        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            $cashier->givePermissionTo([
                'finance.receipt.view',
                'finance.receipt.create',
                'finance.receipt.print',
            ]);
        }
    }
}
