@props([
    'label' => 'Document',
    'status',
    'daysRemaining' => null,
    'expiryDate' => null,
    'issueDate' => null,
    'compact' => false,
])

@php
    $tones = [
        \App\Enums\DocumentExpiryStatus::Expired->value => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50',
        \App\Enums\DocumentExpiryStatus::Critical->value => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50',
        \App\Enums\DocumentExpiryStatus::Expiring->value => 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-900/50',
        \App\Enums\DocumentExpiryStatus::Valid->value => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-900/50',
        \App\Enums\DocumentExpiryStatus::Missing->value => 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
    ];

    $tone = $tones[$status->value] ?? $tones[\App\Enums\DocumentExpiryStatus::Missing->value];

    $days = match (true) {
        $daysRemaining === null => 'Not set',
        $daysRemaining < 0 => 'Expired '.abs($daysRemaining).' day(s) ago',
        $daysRemaining === 0 => 'Expires today',
        $daysRemaining === 1 => '1 day left',
        default => $daysRemaining.' days left',
    };
@endphp

@if ($compact)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold whitespace-nowrap '.$tone]) }}>
        <span class="opacity-70">{{ $label }}</span>
        <span>{{ $days }}</span>
    </span>
@else
    <div {{ $attributes->merge(['class' => 'rounded-lg border px-4 py-3 '.$tone]) }}>
        <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-semibold">{{ $label }}</span>
            <span class="text-xs font-medium uppercase tracking-wide opacity-80">{{ $status->value }}</span>
        </div>

        <p class="mt-1 text-lg font-bold leading-tight">{{ $days }}</p>

        @if ($expiryDate)
            <p class="mt-1 text-xs opacity-80">
                Expires {{ $expiryDate->format('d M, Y') }}
                @if ($issueDate)
                    &middot; Issued {{ $issueDate->format('d M, Y') }}
                @endif
            </p>
        @else
            <p class="mt-1 text-xs opacity-80">No expiry date recorded for this document.</p>
        @endif
    </div>
@endif
