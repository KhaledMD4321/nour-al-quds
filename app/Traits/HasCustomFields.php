<?php

namespace App\Traits;

use App\Models\CustomField;
use App\Models\CustomFieldValue;

trait HasCustomFields
{
    /**
     * الحقول المخصصة المفعّلة لهذا الكيان
     */
    public function getCustomFields()
    {
        return CustomField::forEntity($this->getCustomFieldEntityType())->get();
    }

    /**
     * قراءة قيمة حقل مخصص واحد
     */
    public function getCustomFieldValue(string $fieldKey): ?string
    {
        $field = CustomField::where('entity_type', $this->getCustomFieldEntityType())
            ->where('field_key', $fieldKey)
            ->first();

        if (! $field) {
            return null;
        }

        return CustomFieldValue::where('custom_field_id', $field->id)
            ->where('entity_id', $this->id)
            ->value('value');
    }

    /**
     * كتابة قيمة حقل مخصص واحد
     */
    public function setCustomFieldValue(string $fieldKey, ?string $value): void
    {
        $field = CustomField::where('entity_type', $this->getCustomFieldEntityType())
            ->where('field_key', $fieldKey)
            ->first();

        if (! $field) {
            return;
        }

        CustomFieldValue::updateOrCreate(
            ['custom_field_id' => $field->id, 'entity_id' => $this->id],
            ['value' => $value]
        );
    }

    /**
     * كتابة كل الحقول المخصصة مرة واحدة
     * $data = ['color' => 'أبيض', 'national_id' => '12345']
     */
    public function saveCustomFields(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->setCustomFieldValue($key, is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''));
        }
    }

    /**
     * قراءة كل القيم المخصصة كـ [field_key => value]
     */
    public function getCustomFieldValues(): array
    {
        $fields = $this->getCustomFields();
        $result = [];

        foreach ($fields as $field) {
            $stored = CustomFieldValue::where('custom_field_id', $field->id)
                ->where('entity_id', $this->id)
                ->value('value');

            $result[$field->field_key] = $stored ?? $field->default_value;
        }

        return $result;
    }

    /**
     * اسم الكيان — كل Model يحدده
     */
    abstract protected function getCustomFieldEntityType(): string;
}
