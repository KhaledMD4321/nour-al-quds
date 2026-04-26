# Nour Al-Quds ERP — Project Notes

> **Update this file after every work session with Claude Code.**

## Current Phase
Phase 3 — Master Data ✅ COMPLETE (3.1 → 3.8)
Next: Phase 4 — Sales Invoices ⏳

## Tech Stack
- Laravel 11 + Filament 5.5.2 + PostgreSQL
- PHP 8.5.5
- Spatie Laravel Permission
- DomPDF + Maatwebsite Excel (installed, not yet used)

---

## Filament 5.5 Namespace Map (CRITICAL — differs from v3 docs)

| Component | Filament 5.5 Namespace |
|-----------|----------------------|
| EditAction, DeleteAction, CreateAction, ViewAction, RestoreAction | `Filament\Actions\*` (NOT `Filament\Tables\Actions\*`) |
| BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction | `Filament\Actions\*` |
| TextColumn, IconColumn | `Filament\Tables\Columns\*` |
| SelectFilter, TernaryFilter, TrashedFilter, Filter | `Filament\Tables\Filters\*` |
| Section | `Filament\Schemas\Components\Section` (NOT `Filament\Forms\Components\Section`) |
| form() / table() signatures | `Schema $schema` / `Table $table` |
| Form fields (TextInput, Select, Textarea, Toggle, etc.) | `Filament\Forms\Components\*` |
| Table row actions | `->recordActions([...])` (NOT `->actions([...])`) |
| Table bulk actions | `->toolbarActions([BulkActionGroup::make([...])])` (NOT `->bulkActions([...])`) |
| RelationManager form() | `Schema $schema` (NOT `Forms\Form $form`) |
| FileUpload | Must be inside Schema form — NOT as standalone Livewire property |

## Filament Resource File Structure Pattern

All resources follow a **subdirectory split** pattern:

```
app/Filament/Resources/
└── ModelName/
    ├── ModelNameResource.php        ← main resource (navigation, canAccess, form/table delegates, pages)
    ├── Schemas/
    │   └── ModelNameForm.php        ← static configure(Schema $schema): Schema
    ├── Tables/
    │   └── ModelNamesTable.php      ← static configure(Table $table): Table
    ├── Pages/
    │   ├── ListModels.php
    │   ├── CreateModel.php
    │   ├── EditModel.php
    │   └── ViewModel.php
    └── RelationManagers/            ← only when needed
        └── XxxRelationManager.php
```

## Auto-Code Pattern (PostgreSQL)

```php
public static function generateCode(): string
{
    $last = static::withTrashed()
                  ->where('code', 'LIKE', 'PRE-%')
                  ->orderByRaw("CAST(SUBSTRING(code FROM 5) AS INTEGER) DESC")
                  ->value('code');
    $next = $last ? (int) substr($last, 4) + 1 : 1;
    return 'PRE-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}
```
Prefixes used: `CAT-` categories · `PRD-` products · `CUS-` customers · `SUP-` suppliers

---

## Completed Phases

### Phase 0 — Environment Setup ✅
- PHP 8.5, Composer, Node, Git, PostgreSQL, Claude Code installed

### Phase 1 — Project Scaffold ✅
- Laravel project created at `C:\Projects\nour-al-quds`
- PostgreSQL database `nour_al_quds` connected
- Filament 5.5 installed — Arabic + RTL working
- Spatie Permission, DomPDF, Maatwebsite Excel installed
- Documentation (11 files) in `docs/` folder
- Git initialized

---

### Phase 2 — Foundation Layer ✅ (Completed April 21, 2026)

#### 2.1 Company Settings ✅
- Migration: `company_settings` table (single row design)
- Model: `CompanySetting` with `getInstance()`
- Seeder: `CompanySettingsSeeder` — "شركة نور القدس للأدوات الصحية والسباكة"
- Filament: Edit-only page under "الإعدادات"

#### 2.2 Business Units ✅
- Migration: `business_units` table (name, type enum, is_active)
- Model: `BusinessUnit` — `TYPE_SHOWROOM/TYPE_DISTRIBUTION` constants, `scopeActive`, `isShowroom/isDistribution`
- Seeder: 2 rows — "معرض نور القدس" (showroom) + "مخزن التوزيع الرئيسي" (distribution)
- Migration: added `business_unit_id` (FK nullable) + `is_active` to `users` table
- User model updated: `businessUnit()`, `isSuperAdmin()`, `scopeActive`
- Filament: `BusinessUnitResource` under "الإعدادات"

#### 2.3 Users + Roles + Permissions ✅
- 15 permissions (sales, inventory, finance, reports, accounting, settings)
- 6 roles: `super_admin`, `showroom_manager`, `showroom_cashier`, `distribution_manager`, `distribution_accountant`, `warehouse_keeper`
- 3 test users: admin / showroom / dist (@nour.test — password: `password`)
- Middleware: `CheckBusinessUnit` registered as `'check.unit'`
- Filament: `UserResource` under "الإعدادات"

#### 2.4 Chart of Accounts ✅
- Migration: `chart_of_accounts` (code UNIQUE, self-referential `parent_id`, `business_unit_id` nullable)
- Model: `ChartOfAccount` — 5 TYPE constants, parent/children relations, scopes, `getFullPath()`
- Seeder: 50 accounts — Assets 1xxx · Liabilities 2xxx · Equity 3xxx · Revenue 4xxx · Expenses 5xxx
- Filament: Custom Livewire collapsible tree view (`ListChartOfAccounts.php` + blade template)

#### 2.5 Fiscal Periods ✅
- Migration: `fiscal_periods` with `UNIQUE(year, month)`
- Model: `FiscalPeriod` — `lock(User)/unlock()`, `scopeForDate`, `isDateLocked()`
- Seeder: 24 periods (Jan 2025 → Dec 2026), all open
- Filament: List-only resource with lock/unlock row actions + confirmation modals

#### 2.6 Units of Measure + Tax Rates ✅
- `config/nour.php` — single source of truth for all business enums
- `app/Helpers/NourConfig.php` — static accessors (`units()`, `formatMoney()`, etc.)
- Migration + Model + Seeder: `tax_rates` — 3 rates (VAT 14% default, exempt 0%, table tax 5%)
- Filament: `TaxRateResource` under "الإعدادات"

---

### Phase 3 — Master Data ✅ (April 25, 2026)

#### 3.1 Companies / Manufacturers ✅
- **Migration:** `companies` table — name, country, phone, representative, notes, `softDeletes`
- **Model:** `Company` — relations: `products()`, `priceListVersions()`, `activePriceList()`, `suppliers()`
- **Seeder:** `CompanySeeder` — 6 Egyptian/international manufacturers (إيديال ستاندرد، ديورافيت، جلاسير، أمريكان ستاندرد، ديما، كيرول)
- **Filament:** `Companies/CompanyResource` — navigation group "بيانات المنتجات"، sort 3
- **canAccess:** `super_admin` + `distribution_manager`

#### 3.2 Categories ✅
- **Migration:** `categories` table — self-referential `parent_id`, `sort_order`, `softDeletes`
- **Model:** `Category` — `parent()/children()` relations, `scopeRoots()`, `scopeActive()`, `getFullPathAttribute()`, guard delete if has products
- **Seeder:** `CategorySeeder` — 14 categories: 5 roots + 9 children (أدوات صحية, خلاطات, مواسير, إكسسوار, أخرى)
- **Filament:** `Categories/CategoryResource` — parent selector shows full path (e.g. "أدوات صحية > مراحيض")
- **Auto-code:** `CAT-00001` pattern

#### 3.3 Products ✅
- **Migration:** `products` table — `name`, `code`, `company_id`, `category_id`, `unit_of_measure`, `notes`, `is_active`, `softDeletes`; index on `name` for partial search
- **Model:** `Product` — `company()/category()` BelongsTo, `stockItems()/priceListItems()` HasMany, `getTotalStockAttribute()`, `getStockIn(warehouseId)`, guard delete if has stock or invoices
- **Seeder:** `ProductSeeder` — 10 demo products across all categories with correct company links
- **Filament:** `Products/ProductResource` — navigation group "بيانات المنتجات"، sort 2
- **Auto-code:** `PRD-00001` pattern
- **canAccess:** `super_admin` + `showroom_manager` + `distribution_manager`

#### 3.4 Price Lists ✅
- **Migration:** `price_list_versions` — `company_id`, `version_name`, `effective_date`, `status` (draft/active/archived), `notes`, `softDeletes`
- **Migration:** `price_list_items` — `version_id`, `product_id`, `price` decimal(15,4), `UNIQUE(version_id, product_id)`
- **Models:** `PriceListVersion` (activate/archive with history), `PriceListItem`
- **Seeder:** `PriceListSeeder` — 2 versions (إيديال ستاندرد + ديورافيت), each with items
- **Excel Import Page:** `ImportPriceListPage` — drag-and-drop FileUpload (FilePond), preview table, column mapping (A=name B=price / A=code B=name C=price), import with create-or-update
- **RelationManager:** `PriceListItemsRelationManager` — paginated 25/50/100, server-side product search with `getSearchResultsUsing()`, inline product creation with `createOptionForm/createOptionUsing`, EditAction overridden to show **price only** (fixes INSERT-instead-of-UPDATE bug)
- **Filament:** `PriceListVersionResource` — activate/archive actions, status badges

  **Known Bugs Fixed:**
  - `Class "Filament\Tables\Actions\CreateAction" not found` → use `Filament\Actions\CreateAction`
  - RelationManager `form()` must take `Schema $schema` not `Forms\Form $form`
  - `->relationship('category','name')` inside `createOptionForm` resolves against RelationManager model not `Product` → use `->options(fn() => Category::pluck(...))`
  - EditAction in RelationManager does INSERT instead of UPDATE when full `product_id` Select is present → override `->form([])` with price-only field

#### 3.5 Warehouses + Stock ✅
- **Migration:** `warehouses` — name, `business_unit_id`, type (main/secondary/transit), location, is_active, `softDeletes`
- **Migration:** `stock` — `warehouse_id`, `product_id`, `quantity` decimal(15,3), `avg_cost` decimal(15,4), `UNIQUE(warehouse_id, product_id)`, **NO timestamps**
- **Models:**
  - `Warehouse` — guard delete if stock quantities > 0
  - `Stock` — `$timestamps = false`, `$table = 'stock'`, `getTotalValueAttribute()`
  - `Product` updated — `stockItems()`, `getTotalStockAttribute()`, `getStockIn(warehouseId)`
- **Seeder:** `WarehouseSeeder` — 3 warehouses (معرض رئيسي، مخزن التوزيع، مخزن ترانزيت)
- **Filament:**
  - `WarehouseResource` — navigation group "المخزون"، sort 1، with `StockItemsRelationManager` (read-only: `headerActions([])`, `recordActions([])`, `toolbarActions([])`)
  - `StockResource` — read-only index, `canCreate(): false`, `getHeaderActions(): []` in `ListStocks` (critical — `canCreate` alone does NOT remove the button)
  - `StocksTable` — `total_value` sortable by `orderByRaw("quantity * avg_cost")`

#### 3.6 Customers ✅
- **Migration:** `customers` — code, name, phone, phone_2, address, `business_unit_id`, `tax_registration_number`, `default_discount_1/2/3` decimal(5,2), `credit_limit`, `opening_balance`, notes, is_active, `softDeletes`
- **Model:** `Customer`
  - **Sequential triple discount** (cascading, NOT additive): `price × (1−d1/100) × (1−d2/100) × (1−d3/100)`
  - `getEffectiveDiscountPercentAttribute()` — combined % for display
  - `calculatePrice(float $listPrice)` — applies all 3 discounts
  - `scopeActive`, `scopeSearch`
  - **Auto-code:** `CUS-00001` pattern
- **Seeder:** `CustomerSeeder` — 7 demo customers (CUS-00001 → CUS-00007), all 5 customer types covered
- **Filament:** `Customers/CustomerResource`
  - Navigation group "العملاء والموردين"، sort 1
  - Global search on name / phone / code
  - `CustomerForm` — 3 sections: basic (3-col) · discounts collapsible (3-col) · credit/balances collapsible (2-col)
  - `CustomersTable` — type badge (5 colors: individual/company/trader/contractor/government) · effective discount with `orderByRaw` sorting · credit_limit green when > 0
  - **canAccess:** `super_admin` + `showroom_manager` + `distribution_manager` + `showroom_cashier`

#### 3.7 Suppliers ✅
- **Migration:** `suppliers` — code, name, phone, phone_2, address, `company_id` FK `nullOnDelete`, `tax_registration_number`, `opening_balance` decimal(15,2), notes, is_active, `softDeletes`
- **Model:** `Supplier`
  - Relations: `company()` BelongsTo · `purchaseInvoices()` · `payments()` · `cheques()` HasMany
  - `scopeActive`, `scopeSearch` (ILIKE on name/phone/code)
  - **Auto-code:** `SUP-00001` pattern
- **Company model updated:** added `suppliers()` HasMany
- **Seeder:** `SupplierSeeder` — 5 demo suppliers (SUP-00001 → SUP-00005):
  - 3 linked to manufacturers (إيديال، ديورافيت، جلاسير، ديما)
  - 2 with opening balances (ديورافيت: 15,000 · ديما: 8,500)
  - 1 general supplier (not linked to any manufacturer)
- **Filament:** `Suppliers/SupplierResource`
  - Navigation group "العملاء والموردين"، sort 2
  - Global search on name / phone / code
  - `SupplierForm` — 2 sections: basic (3-col with company Select) · أرصدة collapsible (2-col)
  - `SuppliersTable` — `company.name` column · opening_balance red (danger) when > 0 · `TernaryFilter` for balance and active status
  - **canAccess:** `super_admin` + `showroom_manager` + `distribution_manager`

#### 3.8 Opening Balances ✅
- **Migration:** `opening_balances` — type enum (customer/supplier/stock/treasury), reference_id, product_id (stock only), debit/credit decimal(15,2), quantity decimal(15,3), unit_cost decimal(15,4), balance_date, created_by FK
- **Migration:** `stock_movements` — warehouse_id, product_id, type string, quantity decimal(15,3), unit_cost decimal(15,4), **balance_after decimal(15,3) mandatory**, polymorphic reference (reference_type/reference_id), **NO softDeletes** — eternal audit log
- **Model:** `OpeningBalance`
  - `getReferenceNameAttribute()` — resolves Customer/Supplier/Warehouse name from reference_id
  - `getProductNameAttribute()` — resolves Product name for stock type
- **Model:** `StockMovement`
  - No softDeletes
  - `getTypeLabelAttribute()` — Arabic label for movement types (دخول/خروج/تحويل وارد/.../رصيد افتتاحي)
  - Scopes: `forWarehouse`, `forProduct`, `ofType`
- **OpeningBalanceService** (`app/Modules/DataManagement/OpeningBalanceService.php`)
  - `setCustomerBalance(id, amount, date)` — updates `customers.opening_balance` + inserts OB row (idempotent: deletes old first)
  - `setSupplierBalance(id, amount, date)` — updates `suppliers.opening_balance` + inserts OB row (idempotent)
  - `setStockBalance(warehouseId, productId, qty, cost, date)` — updates `stock` table + creates `stock_movements` opening record + inserts OB row (idempotent — replaces existing)
  - `importStockFromExcel(file, warehouseId, date)` — reads Excel (A=code/name, B=qty, C=cost), calls setStockBalance per row, returns `['added', 'skipped', 'errors']`
- **AppServiceProvider:** `OpeningBalanceService` registered as singleton
- **OpeningBalancesPage** (`app/Filament/Pages/OpeningBalancesPage.php`)
  - Custom Filament page — **canAccess: super_admin only**
  - Navigation: "الإعدادات" group, sort 8, slug `opening-balances`
  - 3 tabs: أرصدة العملاء / أرصدة الموردين / أرصدة المخزون
  - Each tab has: input form + save action + existing balances display table
  - Stock tab has extra: Excel bulk import section (collapsed by default) + summary cards (count + total value)
  - Shared `balance_date` field across all tabs

  **Bugs fixed during implementation:**
  - `$navigationGroup` must be `string|\UnitEnum|null` (not `?string`) — PHP type narrowing error
  - `$view` must be **non-static** (`protected string`) in Filament 5 Pages — `Cannot redeclare non static ... as static` error

---

## Database Seeder Order (IMPORTANT)

```
LookupSeeder                 ← must be first
CompanySettingsSeeder
BusinessUnitSeeder
ChartOfAccountsSeeder
FiscalPeriodSeeder
TaxRateSeeder
RolesAndPermissionsSeeder
AdminUserSeeder
CompanySeeder
CategorySeeder
ProductSeeder
PriceListSeeder
WarehouseSeeder
CustomerSeeder
SupplierSeeder
```

## Current Database Tables (24 app + 5 Spatie)

| # | Table | Phase |
|---|-------|-------|
| 1 | company_settings | 2.1 |
| 2 | business_units | 2.2 |
| 3 | users | 2.2 |
| 4 | chart_of_accounts | 2.4 |
| 5 | fiscal_periods | 2.5 |
| 6 | tax_rates | 2.6 |
| 7 | companies | 3.1 |
| 8 | categories | 3.2 |
| 9 | products | 3.3 |
| 10 | price_list_versions | 3.4 |
| 11 | price_list_items | 3.4 |
| 12 | warehouses | 3.5 |
| 13 | stock | 3.5 |
| 14 | customers | 3.6 |
| 15 | suppliers | 3.7 |
| 16 | opening_balances | 3.8 |
| 17 | stock_movements | 3.8 |
| 18-22 | Spatie RBAC tables | 2.3 |

## Admin Panel Navigation Structure

```
لوحة التحكم (Dashboard)
├── بيانات المنتجات
│   ├── المصنّعين (CompanyResource)
│   ├── المنتجات (ProductResource)
│   └── قوائم الأسعار (PriceListVersionResource + ImportPriceListPage)
├── المخزون
│   ├── المخازن (WarehouseResource — with StockItemsRelationManager)
│   └── رصيد المخزون (StockResource — read-only)
├── العملاء والموردين
│   ├── العملاء (CustomerResource)
│   └── الموردين (SupplierResource)
├── المحاسبة
│   ├── شجرة الحسابات (custom Livewire tree view)
│   └── الفترات المالية (list + lock/unlock)
└── الإعدادات
    ├── إعدادات الشركة (edit only)
    ├── الوحدات التشغيلية (CRUD)
    ├── المستخدمين (CRUD + role assignment)
    ├── الضرائب (CRUD)
    └── الأرصدة الافتتاحية (OpeningBalancesPage — super_admin only)
```

## Seed Data Summary

| Entity | Count | Range |
|--------|-------|-------|
| Companies (manufacturers) | 6 | — |
| Categories | 14 | CAT-00001 → CAT-00014 |
| Products | 10 | PRD-00001 → PRD-00010 |
| Price list versions | 2 | — |
| Warehouses | 3 | — |
| Customers | 7 | CUS-00001 → CUS-00007 |
| Suppliers | 5 | SUP-00001 → SUP-00005 |
| Opening balances | 0 | entered manually by admin |

## Known Issues

- Chart of Accounts: Laravel BelongsTo warnings in logs (cosmetic, framework-level — no impact)
- `make:filament-resource --generate` prompts interactively on this machine — **create all resource files manually** instead
- PDF templates: deferred to Phase 4 (no invoice data yet)
- PurchaseInvoice, Payment, Cheque models: referenced in `Supplier` relations but not yet created — no errors until those modules are built
- Treasury opening balances: deferred to Phase 5 when `treasuries` table is created

## Next: Phase 4 — Sales Invoices

Build order:
1. `invoices` + `invoice_items` tables
2. `InvoiceService` — create, confirm, cancel (with stock deduction)
3. Invoice Filament resource with live item builder
4. PDF print (A4 Arabic RTL)
5. Quick Sale (receipt-only, no formal invoice)
