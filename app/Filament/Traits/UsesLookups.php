<?php

namespace App\Filament\Traits;

use App\Models\LookupType;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;

/**
 * Convenience factory methods for lookup-backed form fields and table columns.
 *
 * Usage in any Filament Resource:
 *
 *   use App\Filament\Traits\UsesLookups;
 *   class ProductResource extends Resource {
 *       use UsesLookups;
 *       ...
 *       self::lookupSelect('unit_of_measure', 'وحدة القياس', 'unit_of_measure', required: true)
 *       self::lookupColumn('unit_of_measure', 'الوحدة', 'unit_of_measure')
 *   }
 */
trait UsesLookups
{
    /**
     * Build a Select form component backed by a lookup type.
     */
    protected static function lookupSelect(
        string $fieldName,
        string $label,
        string $lookupTypeCode,
        bool $required = false,
        ?string $placeholder = null,
    ): Select {
        $select = Select::make($fieldName)
            ->label($label)
            ->options(fn (): array => LookupType::getOptions($lookupTypeCode))
            ->default(fn (): ?string => LookupType::getDefault($lookupTypeCode))
            ->searchable()
            ->preload();

        if ($required) {
            $select->required();
        }

        if ($placeholder) {
            $select->placeholder($placeholder);
        }

        return $select;
    }

    /**
     * Build a TextColumn that displays the Arabic label instead of the raw code.
     */
    protected static function lookupColumn(
        string $fieldName,
        string $label,
        string $lookupTypeCode,
    ): TextColumn {
        return TextColumn::make($fieldName)
            ->label($label)
            ->formatStateUsing(
                fn (?string $state): string => LookupType::getLabel($lookupTypeCode, $state) ?? ($state ?? '—')
            )
            ->sortable();
    }
}
