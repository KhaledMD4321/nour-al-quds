<?php

namespace App\Filament\Resources\JournalEntries;

use App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Filament\Resources\JournalEntries\RelationManagers\LinesRelationManager;
use App\Models\JournalEntry;
use App\Modules\Accounting\AccountingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Concerns\HasModuleGuard;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryResource extends Resource
{
    use HasModuleGuard;
    protected static string $module = 'accounting';

    protected static ?string $model = JournalEntry::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-book-open';
    protected static string|\UnitEnum|null   $navigationGroup = 'المحاسبة';
    protected static ?int                    $navigationSort   = 9;
    protected static ?string                 $navigationLabel  = 'القيود اليومية';
    protected static ?string                 $modelLabel       = 'قيد';
    protected static ?string                 $pluralModelLabel = 'القيود اليومية';
    protected static ?string                 $recordTitleAttribute = 'entry_number';

    // ── RBAC ──────────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('accounting.journal.view');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isSuperAdmin()) return true;
        return $user->can('accounting.journal.create');
    }

    public static function canEdit($record): bool
    {
        return false; // القيود لا تُعدَّل — أي تصحيح بقيد عكسي
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات القيد')
                ->columns(2)
                ->schema([

                    Placeholder::make('entry_number_preview')
                        ->label('رقم القيد')
                        ->content('سيتم توليده تلقائياً عند الحفظ'),

                    DatePicker::make('entry_date')
                        ->label('تاريخ القيد')
                        ->required()
                        ->default(today())
                        ->displayFormat('Y-m-d'),

                    Textarea::make('description')
                        ->label('البيان / الوصف')
                        ->required()
                        ->rows(2)
                        ->placeholder('مثال: تسوية فروق صرف — إيجار شهر أبريل')
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('ملاحظات إضافية')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),

                ]),

        ]);
    }

    // ── Eager Load ────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['createdBy', 'lines']);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([

                TextColumn::make('entry_number')
                    ->label('رقم القيد')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('entry_date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('البيان')
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('total_debit')
                    ->label('إجمالي مدين')
                    ->money('EGP')
                    ->color('danger')
                    ->alignEnd(),

                TextColumn::make('total_credit')
                    ->label('إجمالي دائن')
                    ->money('EGP')
                    ->color('success')
                    ->alignEnd(),

                IconColumn::make('is_manual')
                    ->label('يدوي')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil')
                    ->falseIcon('heroicon-o-cog-6-tooth')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('source_type')
                    ->label('المصدر')
                    ->formatStateUsing(fn ($state) => match (class_basename($state ?? '')) {
                        'Receipt'         => 'سند قبض',
                        'Payment'         => 'سند صرف',
                        'QuickSale'       => 'بيع سريع',
                        'Invoice'         => 'فاتورة',
                        'Cheque'          => 'شيك',
                        'PurchaseInvoice' => 'فاتورة شراء',
                        ''                => 'يدوي',
                        default           => class_basename($state ?? 'يدوي'),
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('createdBy.name')
                    ->label('المستخدم')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('is_manual')
                    ->label('النوع')
                    ->options([
                        '1' => 'يدوي',
                        '0' => 'أوتوماتيكي',
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $data['value'] !== null && $data['value'] !== ''
                            ? $query->where('is_manual', (bool) $data['value'])
                            : $query
                    ),

                Filter::make('entry_date')
                    ->label('الفترة')
                    ->form([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('to')->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
                            ->when($data['to']   ?? null, fn ($q, $d) => $q->whereDate('entry_date', '<=', $d));
                    }),

            ])
            ->actions([

                ViewAction::make()->label('عرض'),

                Action::make('reverse')
                    ->label('عكس القيد')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn () =>
                        auth()->user()?->isSuperAdmin()
                        || auth()->user()?->can('accounting.journal.reverse')
                    )
                    ->requiresConfirmation()
                    ->modalHeading('عكس القيد')
                    ->modalDescription('سيتم إنشاء قيد عكسي بنفس المبالغ مع عكس المدين والدائن. هل أنت متأكد؟')
                    ->action(function (JournalEntry $record) {
                        try {
                            $rev = app(AccountingService::class)->reverseEntry($record->id, auth()->id());
                            Notification::make()
                                ->success()
                                ->title('تم إنشاء القيد العكسي: ' . $rev->entry_number)
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ])
            ->emptyStateHeading('لا توجد قيود يومية')
            ->emptyStateDescription('ابدأ بإضافة قيد يومي جديد.')
            ->emptyStateIcon('heroicon-o-book-open')
            ->paginated([25, 50, 100]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'view'   => ViewJournalEntry::route('/{record}'),
        ];
    }
}
