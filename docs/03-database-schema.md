# 03 — Database Schema — 32 جدول

> **الترتيب هنا هو ترتيب إنشاء الـ Migrations — لازم يُحترم عشان الـ Foreign Keys.**

## GROUP 1: CORE (أولاً)

### company_settings
- `id` — دايماً صف واحد (row=1)
- اسم الشركة، اللوجو، العنوان، التليفون، ترويسة الفاتورة، شروط البيع

### business_units
- `id`
- `name` — "معرض نور القدس" / "مخزن التوزيع الرئيسي"
- `type` — enum: `showroom` / `distribution`
- لا FK — جدول مستقل

### users
- `id`
- `business_unit_id` → business_units (nullable — Super Admin مش مرتبط بوحدة)
- `name`, `email`, `password`, `is_active`
- Spatie roles array
- softDeletes

## GROUP 2: CATALOG (ثانياً)

### companies (المصنّعين)
- `id`
- `name` — إيديال ستاندرد، جلاسير، أمريكان ستاندرد...
- `country`, `phone`, `representative`, `notes`
- softDeletes

### products
- `id`
- `code` — UNIQUE
- `name` — **index مهم جداً للبحث بالاسم الجزئي**
- `name_en` — nullable
- `company_id` → companies
- `category_id` → categories (لو عملنا جدول تصنيفات)
- `unit_of_measure` — enum: piece, meter, box, set, carton
- `min_stock_level` — الحد الأدنى
- `image` — nullable
- `notes`
- softDeletes

### price_list_versions
- `id`
- `company_id` → companies
- `version_number` — UNIQUE مع company_id
- `effective_date`
- `status` — enum: `active` / `archived`
- timestamps
- **UNIQUE(company_id, version_number)**

### price_list_items
- `id`
- `version_id` → price_list_versions (cascadeOnDelete)
- `product_id` → products
- `price` — decimal(15,4) — سعر القائمة
- timestamps
- **UNIQUE(version_id, product_id)** — كل صنف مرة واحدة في الإصدار

## GROUP 3: INVENTORY (ثالثاً)

### warehouses
- `id`
- `name`
- `business_unit_id` → business_units
- `location`, `notes`

### stock
- `id`
- `warehouse_id` → warehouses
- `product_id` → products
- `quantity` — decimal(15,3)
- `avg_cost` — decimal(15,4)
- **لا timestamps** — بس `last_updated`
- **UNIQUE(warehouse_id, product_id)**

### stock_movements
- `id`
- `warehouse_id` → warehouses
- `product_id` → products
- `type` — enum: `in`, `out`, `adjustment`
- `quantity` — decimal(15,3)
- `balance_after` — decimal(15,3) **★ إلزامي**
- `reference_type` — polymorphic (invoice, purchase, transfer, adjustment)
- `reference_id`
- `notes`
- `created_by` → users
- timestamps
- **لا softDeletes — سجل أبدي لا يُحذف أبداً**

### stock_transfers
- `id`
- `from_warehouse_id` → warehouses
- `to_warehouse_id` → warehouses
- `transfer_date`
- `status` — enum: `draft`, `confirmed`
- `notes`
- `created_by` → users
- softDeletes

### stock_transfer_items
- `id`
- `transfer_id` → stock_transfers
- `product_id` → products
- `quantity` — decimal(15,3)
- `unit_cost` — decimal(15,4)

### stock_adjustments
- `id`
- `warehouse_id` → warehouses
- `adjustment_date`
- `status` — enum: `draft`, `confirmed`
- `reason` — text
- `created_by` → users

### stock_adjustment_items
- `id`
- `adjustment_id` → stock_adjustments
- `product_id` → products
- `expected_quantity`, `actual_quantity`, `difference`
- `reason` — enum

## GROUP 4: CONTACTS (رابعاً)

### customers
- `id`
- `code` — UNIQUE
- `name`, `phone`, `address`
- `type` — enum: `individual`, `company`, `trader`
- `tax_registration_number` — nullable
- `credit_limit` — decimal(15,2)
- `default_discount_1`, `default_discount_2`, `default_discount_3` — decimal(5,2)
- `business_unit_id` — nullable (لو العميل تابع لوحدة معينة)
- `opening_balance` — decimal(15,2)
- `notes`
- softDeletes

### suppliers
- `id`
- `code` — UNIQUE
- `name`, `phone`, `address`
- `company_id` → companies (nullable — المصنّع اللي بيوزّعه)
- `tax_registration_number`
- `opening_balance` — decimal(15,2)
- `notes`
- softDeletes

## GROUP 5: SALES (خامساً)

### quick_sales
- `id`
- `business_unit_id` → business_units
- `warehouse_id` → warehouses
- `treasury_id` → treasuries
- `total_amount` — decimal(15,2)
- `payment_type` — enum: `cash`
- `created_by` → users
- timestamps

### quick_sale_items
- `id`
- `quick_sale_id` → quick_sales
- `product_id` → products
- `quantity`, `unit_price`, `total`

### invoices
- `id`
- `reference_number` — UNIQUE, auto-generated
- `type` — enum: `sale`, `sale_return`
- `business_unit_id` → business_units
- `warehouse_id` → warehouses
- `customer_id` → customers
- `invoice_date`, `due_date`
- `status` — enum: `draft`, `confirmed`, `delivered`, `partial_paid`, `paid`, `cancelled`
- `payment_type` — enum: `cash`, `credit`, `cheque`
- `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `paid_amount` — decimal(15,2)
- `notes`
- `original_invoice_id` — nullable (للمرتجعات — الفاتورة الأصلية)
- `created_by` → users
- softDeletes
- **INDEX(status, invoice_date)**

### invoice_items
- `id`
- `invoice_id` → invoices
- `product_id` → products
- `price_list_version_id` → price_list_versions (nullable)
- `quantity` — decimal(15,3)
- `list_price` — decimal(15,4) — سعر القائمة الأصلي
- `discount_1`, `discount_2`, `discount_3` — decimal(5,2)
- `unit_price` — decimal(15,4) — السعر بعد الخصومات
- `total` — decimal(15,2)

## GROUP 6: PURCHASES (سادساً)

### purchase_orders
- `id`
- `supplier_id` → suppliers
- `warehouse_id` → warehouses
- `status` — enum: `draft`, `sent`, `received`
- `order_date`
- `notes`
- `created_by` → users

### purchase_invoices
- `id`
- `supplier_id` → suppliers
- `warehouse_id` → warehouses
- `business_unit_id` → business_units
- `invoice_number` — رقم فاتورة المورد
- `invoice_date`, `due_date`
- `status` — enum: `draft`, `confirmed`, `paid`
- `subtotal`, `tax_amount`, `total_amount`, `paid_amount` — decimal(15,2)
- `total_landed_cost` — decimal(15,2)
- `notes`
- `created_by` → users
- softDeletes

### purchase_invoice_items
- `id`
- `purchase_invoice_id` → purchase_invoices
- `product_id` → products
- `quantity`, `unit_cost`, `total`
- `landed_cost_share` — decimal(15,4) — نصيبه من الـ landed cost

### landed_costs
- `id`
- `purchase_invoice_id` → purchase_invoices
- `type` — enum: `transport`, `loading`, `customs`, `other`
- `description`
- `amount` — decimal(15,2)

## GROUP 7: FINANCE (سابعاً)

### treasuries
- `id`
- `name` — "خزينة المعرض" / "خزينة التوزيع"
- `type` — enum: `cash`, `bank`
- `business_unit_id` → business_units (nullable)
- `current_balance` — decimal(15,2) — **يتحدث مع كل حركة**
- `account_id` → chart_of_accounts (الحساب المرتبط)

### treasury_transactions
- `id`
- `treasury_id` → treasuries
- `type` — enum: `in`, `out`
- `amount` — decimal(15,2)
- `balance_after` — decimal(15,2) **★ إلزامي**
- `reference_type` — polymorphic
- `reference_id`
- `description`
- `created_by` → users
- timestamps
- **لا softDeletes — سجل أبدي**

### receipts (سندات قبض)
- `id`
- `receipt_number` — UNIQUE
- `treasury_id` → treasuries
- `customer_id` → customers (nullable)
- `invoice_id` → invoices (nullable)
- `business_unit_id` → business_units
- `amount` — decimal(15,2)
- `payment_method` — enum: `cash`, `cheque`, `bank_transfer`
- `receipt_date`
- `notes`
- `created_by` → users

### payments (سندات صرف)
- `id`
- `payment_number` — UNIQUE
- `treasury_id` → treasuries
- `supplier_id` → suppliers (nullable)
- `business_unit_id` → business_units
- `amount` — decimal(15,2)
- `category` — enum: `supplier_payment`, `rent`, `salary`, `transport`, `electricity`, `other`
- `payment_method` — enum: `cash`, `cheque`, `bank_transfer`
- `payment_date`
- `notes`
- `created_by` → users

### cheques (الشيكات المؤجلة)
- `id`
- `cheque_number`
- `bank_name`
- `amount` — decimal(15,2)
- `issue_date`, `due_date`
- `direction` — enum: `incoming`, `outgoing`
- `status` — enum: `pending`, `deposited`, `collected`, `bounced`, `replaced`
- `treasury_id` → treasuries
- `customer_id` → customers (nullable — للواردة)
- `supplier_id` → suppliers (nullable — للصادرة)
- `invoice_id` → invoices (nullable)
- `notes`
- timestamps

### treasury_transfers
- `id`
- `from_treasury_id` → treasuries
- `to_treasury_id` → treasuries
- `amount` — decimal(15,2)
- `transfer_date`
- `approved_by` → users (nullable)
- `notes`

## GROUP 8: ACCOUNTING (ثامناً)

### chart_of_accounts (شجرة الحسابات)
- `id`
- `code` — UNIQUE
- `name`
- `type` — enum: `asset`, `liability`, `equity`, `revenue`, `expense`
- `parent_id` → chart_of_accounts (nullable — self-referential)
- `business_unit_id` → business_units (nullable — لو الحساب خاص بوحدة)
- `is_active` — boolean
- `level` — integer
- `notes`

### journal_entries (القيود اليومية)
- `id`
- `entry_number` — UNIQUE, auto-generated
- `entry_date`
- `description`
- `source_type` — polymorphic (invoice, receipt, payment, cheque...)
- `source_id`
- `is_manual` — boolean (قيد يدوي أم أوتوماتيكي)
- `total_debit`, `total_credit` — decimal(15,2)
- `created_by` → users
- timestamps

### journal_entry_lines
- `id`
- `journal_entry_id` → journal_entries
- `account_id` → chart_of_accounts
- `business_unit_id` → business_units (nullable — مركز التكلفة)
- `debit` — decimal(15,2) default 0
- `credit` — decimal(15,2) default 0
- `description`

### fiscal_periods
- `id`
- `year` — integer
- `month` — integer
- `start_date`, `end_date`
- `is_locked` — boolean
- `locked_by` → users (nullable)
- `locked_at` — timestamp nullable

## GROUP 9: SYSTEM (تاسعاً)

### activity_log
- Spatie Activity Log — تلقائي
- `causer_type + causer_id` — polymorphic
- `subject_type + subject_id` — polymorphic

### notifications
- `uuid`
- `notifiable_type + notifiable_id` — polymorphic
- `data` — json
- `read_at` — nullable

### opening_balances
- `id`
- `type` — enum: `customer`, `supplier`, `treasury`, `stock`
- `reference_id` — ID of the entity
- `debit`, `credit` — decimal(15,2)
- `balance_date`
- `notes`
- `created_by` → users
