# Nour Al-Quds ERP — Project Notes

> **Update this file after every work session with Claude Code.**

## Current Phase
Phase 2 — Foundation Layer ✅ COMPLETE
Next: Phase 3 — Master Data ⏳ Waiting for instructions

## Tech Stack
- Laravel 11 + Filament 5.5.2 + PostgreSQL
- PHP 8.5.5
- Spatie Laravel Permission
- DomPDF + Maatwebsite Excel (installed, not yet used)

## Filament 5.5 Namespace Map (CRITICAL — differs from v3 docs)
| Component | Filament 5.5 Namespace |
|-----------|----------------------|
| EditAction, DeleteAction, CreateAction | `Filament\Actions\*` (NOT `Filament\Tables\Actions\*`) |
| TextColumn, ToggleColumn, IconColumn | `Filament\Tables\Columns\*` |
| SelectFilter, TernaryFilter | `Filament\Tables\Filters\*` |
| Section | `Filament\Schemas\Components\Section` (NOT `Filament\Forms\Components\Section`) |
| form() signature | `Schema $schema` (NOT `Form $form`) |
| Form fields (TextInput, Select, etc.) | `Filament\Forms\Components\*` |

## Completed Phases

### Phase 0 — Environment Setup ✅
- PHP 8.5, Composer, Node, Git, PostgreSQL, Claude Code installed

### Phase 1 — Project Scaffold ✅
- Laravel project created at C:\Projects\nour-al-quds
- PostgreSQL database nour_al_quds connected
- Filament 5.5 installed — Arabic + RTL working
- Spatie Permission, DomPDF, Maatwebsite Excel installed
- Documentation (11 files) in docs/ folder
- Git initialized

### Phase 2 — Foundation Layer ✅ (Completed April 21, 2026)

#### 2.1 Company Settings ✅
- Migration: company_settings table (single row design)
- Model: CompanySetting with getInstance()
- Seeder: CompanySettingsSeeder — "شركة نور القدس للأدوات الصحية والسباكة"
- Filament: Edit-only page under "الإعدادات"

#### 2.2 Business Units ✅
- Migration: business_units table (name, type enum, is_active)
- Model: BusinessUnit with TYPE_SHOWROOM/TYPE_DISTRIBUTION constants, scopeActive, isShowroom/isDistribution helpers
- Seeder: 2 rows — "معرض نور القدس" (showroom) + "مخزن التوزيع الرئيسي" (distribution)
- Migration: added business_unit_id (FK nullable) + is_active to users table
- User model updated: businessUnit(), isSuperAdmin(), scopeActive
- Filament: BusinessUnitResource under "الإعدادات"

#### 2.3 Users + Roles + Permissions ✅
- 15 permissions defined:
  sales.quick.create, sales.invoice.create, sales.invoice.delete,
  inventory.view, inventory.transfer, inventory.adjust,
  finance.treasury.view, finance.receipt.create, finance.payment.create,
  reports.sales, reports.profit_loss,
  accounting.journal, accounting.lock_period,
  settings.users, settings.company
- 6 roles: super_admin, showroom_manager, showroom_cashier, distribution_manager, distribution_accountant, warehouse_keeper
- 3 test users:
  - admin@nour.test / password → super_admin (sees everything)
  - showroom@nour.test / password → showroom_manager (limited)
  - dist@nour.test / password → distribution_manager (limited)
- Middleware: CheckBusinessUnit registered as 'check.unit'
- Filament: UserResource under "الإعدادات" — list with role badges, filters, super_admin protection

#### 2.4 Chart of Accounts ✅
- Migration: chart_of_accounts (code UNIQUE, self-referential parent_id, business_unit_id nullable)
- Model: ChartOfAccount — 5 TYPE constants, parent/children relations, scopeActive/scopeRoots/scopeOfType, isParent(), getFullPath()
- Seeder: 50 accounts — complete Egyptian plumbing company chart:
  - Assets 1xxx (18 accounts), Liabilities 2xxx, Equity 3xxx, Revenue 4xxx, Expenses 5xxx
  - 12 unit-specific leaf accounts (6 showroom + 6 distribution)
- Filament: CUSTOM Livewire collapsible tree view (not standard Filament table)
  - ListChartOfAccounts.php — Livewire page with expandedIds state
  - list-chart-of-accounts.blade.php — custom tree UI
  - Features: expand/collapse per account, "توسيع الكل"/"طي الكل" buttons
  - Known: Laravel BelongsTo warnings in logs (cosmetic only)

#### 2.5 Fiscal Periods ✅
- Migration: fiscal_periods with UNIQUE(year, month)
- Model: FiscalPeriod — MONTHS constant (Arabic names), lock(User)/unlock(), scopeForDate, scopeOpen/scopeLocked, isCurrentPeriod(), getDisplayName(), getActivePeriodForDate(), isDateLocked()
- Seeder: 24 periods (Jan-Dec 2025 + Jan-Dec 2026), all open
- Filament: List-only resource (no create/edit), lock/unlock row actions with confirmation modals
- canAccess: accounting.lock_period permission only

#### 2.6 Units of Measure + Currencies + Tax Rates ✅
- config/nour.php — SINGLE SOURCE OF TRUTH for all business enums:
  units_of_measure (8), currencies (EGP), payment_methods, customer_types, invoice_statuses, cheque_statuses, adjustment_reasons, expense_categories
- app/Helpers/NourConfig.php — static accessors: units(), currencies(), formatMoney(), currencySymbol(), paymentMethods(), customerTypes(), invoiceStatuses()
- Migration: tax_rates table
- Model: TaxRate with getDefault(), getRate() static methods
- Seeder: 3 rates — VAT 14% (default), exempt 0%, table tax 5%
- Filament: TaxRateResource under "الإعدادات"

## Database Seeder Order (IMPORTANT)
CompanySettingsSeeder
→ BusinessUnitSeeder
→ ChartOfAccountsSeeder
→ FiscalPeriodSeeder
→ TaxRateSeeder
→ RolesAndPermissionsSeeder
→ AdminUserSeeder

## Current Database Tables (11 app + 5 Spatie)
1. company_settings
2. business_units
3. users (+ business_unit_id, is_active)
4. chart_of_accounts
5. fiscal_periods
6. tax_rates
7-11. Spatie tables (permissions, roles, model_has_roles, model_has_permissions, role_has_permissions)

## Admin Panel Navigation Structure
لوحة التحكم (Dashboard)
├── المحاسبة
│   ├── شجرة الحسابات (custom tree view)
│   └── الفترات المالية (list + lock/unlock)
└── الإعدادات
    ├── إعدادات الشركة (edit only)
    ├── الوحدات التشغيلية (CRUD)
    ├── المستخدمين (CRUD + role assignment)
    └── الضرائب (CRUD)

## Known Issues
- Chart of Accounts: Laravel BelongsTo warnings in logs (cosmetic, no impact)
- PDF template: deferred to Phase 4 (no invoice data exists yet to test with)
- Showroom/Distribution dashboards empty (expected — operational modules not built)

## Next: Phase 3 — Master Data
Build order (mandatory sequence):
1. Companies/Manufacturers (المصنّعين)
2. Categories (التصنيفات) — tree structure
3. Products (المنتجات) — with index on name for partial search
4. Price Lists (قوائم الأسعار) — versions + items + Excel import
5. Warehouses (المخزنين) — one per business unit
6. Customers (العملاء) — with credit limits + 3-tier discounts
7. Suppliers (الموردين)
8. Opening Balances (أرصدة افتتاحية)
