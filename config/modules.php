<?php

/**
 * إعدادات وحدات النظام
 *
 * يُستخدَم هذا الملف كـ fallback فقط.
 * المصدر الحقيقي هو جدول `modules` في قاعدة البيانات
 * ويُدار عبر صفحة إعدادات الوحدات في لوحة التحكم.
 *
 * للتحقق من حالة وحدة: \App\Models\Module::isActive('sales')
 */

return [

    /*
    |--------------------------------------------------------------------------
    | الوحدات الافتراضية (fallback عند عدم وجود DB)
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'sales' => true,
        'purchases' => true,
        'inventory' => true,
        'customers' => true,
        'finance' => true,
        'accounting' => true,
        'reports' => true,
        'catalog' => true,
        'data_mgmt' => true,
        'internal_ops' => true,
    ],

];
