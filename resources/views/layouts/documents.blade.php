<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
@php($currentOrganization = $organization ?? auth()->user()?->currentOrganization())
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f5f3">
    <title>@yield('title', __('app.page_title')) | {{ config('app.name', 'Records Management System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="records-page">
    <header class="records-header">
        <a class="records-brand" href="{{ route('dashboard') }}">
            <span class="agency-mark" aria-hidden="true">{{ strtoupper(substr(config('app.name', 'R'), 0, 1)) }}</span>
            <span class="brand-copy">
                <strong>{{ $currentOrganization?->name ?? config('app.name', 'Records Management System') }}</strong>
                <small>{{ __('app.page_title') }}</small>
            </span>
        </a>
        <div class="records-user">
            <form class="locale-form" method="POST" action="{{ route('locale.update') }}">
                @csrf
                <label class="visually-hidden" for="locale">{{ __('app.language') }}</label>
                <select id="locale" name="locale" aria-label="{{ __('app.language') }}">
                    <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                    <option value="ar" @selected(app()->getLocale() === 'ar')>العربية</option>
                </select>
                <button type="submit">{{ __('app.change_language') }}</button>
            </form>
            @if ($currentOrganization)
                <span class="organization-name">{{ $currentOrganization->name }}</span>
            @endif
            <span class="user-name">{{ auth()->user()->name }}</span>
        </div>
    </header>

    <nav class="records-nav" aria-label="{{ __('app.main_navigation') }}">
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>{{ __('app.navigation.dashboard') }}</a>
        <a href="{{ route('documents.incoming.index') }}" @class(['active' => request()->routeIs('documents.incoming.*')])>{{ __('documents.incoming_documents') }}</a>
        <a href="{{ route('documents.outgoing.index') }}" @class(['active' => request()->routeIs('documents.outgoing.*')])>{{ __('documents.outgoing_documents') }}</a>
        <a href="{{ route('documents.archive') }}" @class(['active' => request()->routeIs('documents.archive')])>{{ __('documents.archive') }}</a>
        @can('manage-administration')
            <span class="nav-section-label">{{ __('administration.administration') }}</span>
            <a href="{{ route('admin.organization.edit') }}" @class(['active' => request()->routeIs('admin.organization.*')])>{{ __('administration.organization') }}</a>
            <a href="{{ route('admin.departments.index') }}" @class(['active' => request()->routeIs('admin.departments.*')])>{{ __('administration.departments') }}</a>
            <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>{{ __('administration.users') }}</a>
        @endcan
        <form class="nav-logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" aria-label="{{ __('app.sign_out') }}"><span aria-hidden="true">&#8599;</span><span class="logout-label">{{ __('app.logout') }}</span><span class="logout-accessible-copy">{{ __('app.sign_out') }}</span></button>
        </form>
    </nav>

    <main class="records-main">
        @if (session('status'))
            <div class="success-banner" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>