<?php

namespace App\Services;

use App\Models\CustomField;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

/**
 * يولّد Filament form components وtable columns ديناميكياً من custom_fields
 */
class CustomFieldRenderer
{
    /**
     * يرجع array من Filament form components للكيان المحدد
     */
    public static function formComponents(string $entityType): array
    {
        $fields = CustomField::forEntity($entityType)->get();

        if ($fields->isEmpty()) {
            return [];
        }

        $components = [];

        foreach ($fields as $field) {
            $component = match ($field->field_type) {
                'number' => TextInput::make("custom_fields.{$field->field_key}")
                    ->label($field->field_label)
                    ->numeric()
                    ->placeholder($field->placeholder ?? '')
                    ->default($field->default_value)
                    ->required($field->is_required),

                'date' => DatePicker::make("custom_fields.{$field->field_key}")
                    ->label($field->field_label)
                    ->default($field->default_value)
                    ->required($field->is_required),

                'select' => Select::make("custom_fields.{$field->field_key}")
                    ->label($field->field_label)
                    ->options(
                        collect($field->options ?? [])
                            ->mapWithKeys(fn ($opt) => [$opt => $opt])
                            ->toArray()
                    )
                    ->default($field->default_value)
                    ->required($field->is_required)
                    ->searchable(),

                'toggle' => Toggle::make("custom_fields.{$field->field_key}")
                    ->label($field->field_label)
                    ->default($field->default_value === '1'),

                'textarea' => Textarea::make("custom_fields.{$field->field_key}")
                    ->label($field->field_label)
                    ->placeholder($field->placeholder ?? '')
                    ->default($field->default_value)
                    ->required($field->is_required)
                    ->rows(3),

                default => TextInput::make("custom_fields.{$field->field_key}")
                    ->label($field->field_label)
                    ->placeholder($field->placeholder ?? '')
                    ->default($field->default_value)
                    ->required($field->is_required),
            };

            $components[] = $component;
        }

        return $components;
    }

    /**
     * يرجع array من Filament table columns للحقول اللي is_searchable = true
     */
    public static function tableColumns(string $entityType): array
    {
        $fields = CustomField::forEntity($entityType)
            ->where('is_searchable', true)
            ->get();

        $columns = [];

        foreach ($fields as $field) {
            $columns[] = TextColumn::make("cf_{$field->field_key}")
                ->label($field->field_label)
                ->getStateUsing(fn ($record) => $record->getCustomFieldValue($field->field_key) ?? '—')
                ->toggleable(isToggledHiddenByDefault: false);
        }

        return $columns;
    }

    /**
     * يحفظ قيم الحقول المخصصة من الفورم للكيان
     */
    public static function saveValues(mixed $record, array $formData): void
    {
        $customData = $formData['custom_fields'] ?? [];
        if (empty($customData)) {
            return;
        }

        $record->saveCustomFields($customData);
    }

    /**
     * يحمّل القيم الحالية للفورم (عند Edit)
     */
    public static function loadValues(mixed $record): array
    {
        return $record->getCustomFieldValues();
    }
}
