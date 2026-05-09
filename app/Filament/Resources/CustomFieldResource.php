<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomFieldResource\Pages\CreateCustomField;
use App\Filament\Resources\CustomFieldResource\Pages\EditCustomField;
use App\Filament\Resources\CustomFieldResource\Pages\ListCustomFields;
use App\Models\CustomField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-squares-plus';
    protected static string|\UnitEnum|null   $navigationGroup = 'الإعدادات';
    protected static ?int                    $navigationSort  = 102;
    protected static ?string                 $navigationLabel = 'الحقول المخصصة';
    protected static ?string                 $modelLabel      = 'حقل مخصص';
    protected static ?string                 $pluralModelLabel = 'الحقول المخصصة';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ── Form ──────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('تعريف الحقل')
                ->columns(2)
                ->schema([
                    Select::make('entity_type')
                        ->label('الكيان')
                        ->options(CustomField::getEntityTypes())
                        ->required()
                        ->live(),

                    TextInput::make('field_key')
                        ->label('المعرّف (بالإنجليزي)')
                        ->required()
                        ->maxLength(80)
                        ->regex('/^[a-z][a-z0-9_]*$/')
                        ->helperText('حروف إنجليزي صغيرة وأرقام وـ فقط — مثال: color, national_id')
                        ->placeholder('مثال: color'),

                    TextInput::make('field_label')
                        ->label('الاسم بالعربي')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('مثال: اللون'),

                    Select::make('field_type')
                        ->label('نوع الحقل')
                        ->options([
                            'text'     => 'نص',
                            'number'   => 'رقم',
                            'date'     => 'تاريخ',
                            'select'   => 'قائمة اختيار',
                            'toggle'   => 'تفعيل/تعطيل',
                            'textarea' => 'نص طويل',
                        ])
                        ->required()
                        ->live(),
                ]),

            Section::make('خيارات القائمة')
                ->visible(fn (Get $get) => $get('field_type') === 'select')
                ->schema([
                    TagsInput::make('options')
                        ->label('الخيارات')
                        ->helperText('اكتب كل خيار واضغط Enter — مثال: أبيض، بيج، كروم')
                        ->placeholder('أضف خيار...')
                        ->separator(','),
                ]),

            Section::make('إعدادات الحقل')
                ->columns(3)
                ->schema([
                    TextInput::make('default_value')
                        ->label('القيمة الافتراضية')
                        ->nullable(),

                    TextInput::make('placeholder')
                        ->label('نص مساعد')
                        ->nullable()
                        ->placeholder('يظهر داخل الحقل الفاضي'),

                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_required')
                        ->label('إلزامي')
                        ->default(false),

                    Toggle::make('is_searchable')
                        ->label('قابل للبحث')
                        ->helperText('يظهر كعمود في الجدول')
                        ->default(false),

                    Toggle::make('is_printable')
                        ->label('يظهر في الطباعة')
                        ->default(false),

                    Toggle::make('is_active')
                        ->label('مفعّل')
                        ->default(true),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity_type')
                    ->label('الكيان')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CustomField::getEntityTypes()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'customer' => 'info',
                        'supplier' => 'warning',
                        'product'  => 'success',
                        'company'  => 'gray',
                        'invoice'  => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('field_label')
                    ->label('اسم الحقل')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('field_key')
                    ->label('المعرّف')
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('field_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($record) => $record->type_label),

                IconColumn::make('is_required')
                    ->label('إلزامي')
                    ->boolean(),

                IconColumn::make('is_searchable')
                    ->label('قابل للبحث')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('entity_type')
                    ->label('الكيان')
                    ->options(CustomField::getEntityTypes()),
            ])
            ->actions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('entity_type')
            ->paginated([25, 50])
            ->emptyStateHeading('لا توجد حقول مخصصة')
            ->emptyStateDescription('اضغط "إضافة حقل" لإنشاء حقل مخصص جديد')
            ->emptyStateIcon('heroicon-o-squares-plus');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCustomFields::route('/'),
            'create' => CreateCustomField::route('/create'),
            'edit'   => EditCustomField::route('/{record}/edit'),
        ];
    }
}
