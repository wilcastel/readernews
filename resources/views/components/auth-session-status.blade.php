@props(['status'])

@if ($status)
<<<<<<< HEAD
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
=======
    <div {{ $attributes->merge(['class' => 'px-4 py-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-medium']) }}>
>>>>>>> myNotesInvestigation
        {{ $status }}
    </div>
@endif
