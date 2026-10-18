@extends('layouts.app')

@section('title', __('citizen.search.title'))
@section('page-title', __('citizen.search.title'))
@section('page-sub', __('citizen.search.subtitle'))

@section('content')
<div class="space-y-6">

    <div class="min-w-0">
        <h1 class="text-xl font-bold text-navy-900">{{ __('citizen.search.field_mouza') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('citizen.land.field_mouza_help') }}</p>
    </div>

    {{-- The public geo hierarchy: districts → upazilas → mouzas. Read-only
         reference data, safe to expose and useful for building a search. --}}
    <section class="card p-6">
        <h2 class="card-title">{{ __('citizen.search.field_district') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            @foreach ($districts as $district)
                <li class="rounded-lg border-slate-200 p-3">
                    <p class="font-semibold text-slate-700">{{ $district->name_en }}</p>
                    <p class="text-xs text-slate-400">{{ $district->name_bn }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($upazilas->isNotEmpty())
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.search.field_upazila') }}</h2>
            <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                @foreach ($upazilas as $upazila)
                    <li class="rounded-lg border-slate-200 p-3">
                        <p class="font-semibold text-slate-700">{{ $upazila->name_en }}</p>
                        <p class="text-xs text-slate-400">{{ $upazila->name_bn }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($mouzas->isNotEmpty())
        <section class="card p-6">
            <h2 class="card-title">{{ __('citizen.search.field_mouza') }}</h2>
            <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                @foreach ($mouzas as $mouza)
                    <li class="rounded-lg border-slate-200 p-3">
                        <p class="font-semibold text-slate-700">{{ $mouza->name_en }}</p>
                        <p class="text-xs text-slate-400">{{ $mouza->name_bn }} · {{ $mouza->code }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <a href="{{ route('citizen.find') }}" class="btn-ghost border-slate-200">
        {{ __('citizen.common.back') }}
    </a>
</div>
@endsection
