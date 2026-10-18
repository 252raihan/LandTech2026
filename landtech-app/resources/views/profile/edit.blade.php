@extends('layouts.app')

@section('title', __('Profile'))
@section('page-title', __('Profile'))

@section('content')
    <div class="space-y-6">
        <x-page-header icon="user" :title="__('Profile')" :subtitle="__('pages.layout.footer_note')" />

        <div class="card p-4 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card p-4 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card border-red-200 p-4 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
