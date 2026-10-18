@props([
    'icon' => 'dashboard',
    'title' => '',
    'subtitle' => null,
    'badge' => null,
])

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="flex min-w-0 items-start gap-4">
        <span class="page-icon hidden sm:flex">
            <x-icon :name="$icon" class="h-6 w-6" />
        </span>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="sim-badge">{{ $badge }}</span>
                @endif
            </div>
            @if ($subtitle)
                <p class="page-sub">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            {{ $actions }}
        </div>
    @endisset
</div>
