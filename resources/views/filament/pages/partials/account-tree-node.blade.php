@php
    $types = [
        'asset'     => ['label' => 'أصول',        'color' => '#2563eb', 'bg' => '#dbeafe'],
        'liability' => ['label' => 'خصوم',        'color' => '#d97706', 'bg' => '#fef3c7'],
        'equity'    => ['label' => 'حقوق ملكية',  'color' => '#7c3aed', 'bg' => '#ede9fe'],
        'revenue'   => ['label' => 'إيرادات',     'color' => '#059669', 'bg' => '#dcfce7'],
        'expense'   => ['label' => 'مصروفات',     'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];
    $t           = $types[$node['type']] ?? ['label' => $node['type'], 'color' => '#6b7280', 'bg' => '#f3f4f6'];
    $indent      = $depth * 28;
    $hasChildren = ! empty($node['children']);
    $isEditing   = ($this->editingId === $node['id']);
    $rowBg       = $depth === 0 ? '#f8fafc' : ($depth === 1 ? '#ffffff' : '#fafafa');
    $fontWeight  = $depth === 0 ? '700' : ($depth === 1 ? '600' : '400');
@endphp

<div style="display: grid; grid-template-columns: 1fr 100px 100px 100px 130px;
            padding: 8px 16px; border-bottom: 1px solid #f3f4f6;
            align-items: center; background: {{ $rowBg }};"
     id="account-{{ $node['id'] }}">

    {{-- اسم الحساب مع indent --}}
    <div style="padding-right: {{ $indent }}px; display: flex; align-items: center; gap: 6px;">
        @if($hasChildren)
            <span style="color: #9ca3af; font-size: 10px; width: 12px;">{{ $showAll ? '▼' : '▶' }}</span>
        @else
            <span style="width: 12px; display: inline-block;"></span>
        @endif

        <span style="font-family: monospace; font-size: 12px; color: {{ $t['color'] }}; font-weight: 600; min-width: 52px;">
            {{ $node['code'] }}
        </span>

        @if($isEditing)
            <input type="text" wire:model.live="editData.name"
                style="flex: 1; padding: 4px 8px; border: 1px solid #3b82f6; border-radius: 4px; font-size: 13px;" />
        @else
            <span style="font-size: 13px; font-weight: {{ $fontWeight }}; color: #111827;">
                {{ $node['name'] }}
            </span>
        @endif
    </div>

    {{-- النوع --}}
    <div style="text-align: center;">
        <span style="display: inline-block; padding: 2px 8px; font-size: 10px; font-weight: 600;
                     border-radius: 999px; background: {{ $t['bg'] }}; color: {{ $t['color'] }};">
            {{ $t['label'] }}
        </span>
    </div>

    {{-- الوحدة --}}
    <div style="text-align: center; font-size: 11px; color: #6b7280;">
        {{ $node['business_unit'] }}
    </div>

    {{-- الحركات --}}
    <div style="text-align: center;">
        @if($node['has_movements'])
            <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px;
                         border-radius: 999px; font-size: 10px; font-weight: 600;">
                {{ number_format($node['movement_count']) }}
            </span>
        @else
            <span style="color: #d1d5db; font-size: 12px;">—</span>
        @endif
    </div>

    {{-- الإجراءات --}}
    <div style="text-align: center; display: flex; justify-content: center; gap: 4px;">
        @if($isEditing)
            <button wire:click="saveEdit"
                style="background: #059669; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                حفظ
            </button>
            <button wire:click="cancelEdit"
                style="background: white; border: 1px solid #d1d5db; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                إلغاء
            </button>
        @else
            <button wire:click="startEdit({{ $node['id'] }})" title="تعديل"
                style="background: none; border: none; cursor: pointer; font-size: 14px; padding: 2px 3px;">✏️</button>
            <button wire:click="toggleAddForm({{ $node['id'] }})" title="إضافة حساب فرعي"
                style="background: none; border: none; cursor: pointer; font-size: 14px; padding: 2px 3px;">➕</button>
            @if(! $node['has_movements'] && empty($node['children']))
                <button wire:click="archiveAccount({{ $node['id'] }})"
                    title="أرشفة"
                    onclick="if(!confirm('هل أنت متأكد من أرشفة هذا الحساب؟')) return false;"
                    style="background: none; border: none; cursor: pointer; font-size: 14px; padding: 2px 3px;">🗑️</button>
            @endif
        @endif
    </div>
</div>

{{-- الأبناء — recursive --}}
@if($hasChildren && $showAll)
    @foreach($node['children'] as $child)
        @include('filament.pages.partials.account-tree-node', [
            'node'    => $child,
            'depth'   => $depth + 1,
            'showAll' => $showAll,
        ])
    @endforeach
@endif
