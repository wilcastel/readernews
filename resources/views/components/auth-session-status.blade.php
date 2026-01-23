@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'px-4 py-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-medium']) }}>
        {{ $status }}
    </div>
@endif
