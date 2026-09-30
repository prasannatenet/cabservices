@props([
    // The record, or the associate itself, to label.
    'record',
])

@php
    $associate = $record instanceof \App\Models\User ? $record : $record->associate;
    $isAdminCreated = $associate === null;
@endphp

{{-- An admin-created record and an associate-owned one look different, so the
     owner of a row is readable at a glance without opening it. --}}
<span {{ $attributes->merge(['class' => 'px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border whitespace-nowrap '
    .($isAdminCreated
        ? 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-900/50'
        : 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-900/50')]) }}>
    {{ $isAdminCreated ? 'Admin Created' : $associate->name }}
</span>