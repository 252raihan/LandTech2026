@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-700']) }}>
        {{ $status }}
    </div>
@endif
