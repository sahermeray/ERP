<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php($currentOrganization = $organization ?? auth()->user()?->currentOrganization())
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f5f3">
    <title>@yield('title', 'Document Management') | {{ config('app.name', 'Records Management System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="records-page">
    <header class="records-header">
        <a class="records-brand" href="{{ route('dashboard') }}">
            <span class="agency-mark" aria-hidden="true">{{ strtoupper(substr(config('app.name', 'R'), 0, 1)) }}</span>
            <span class="brand-copy">
                <strong>{{ $currentOrganization?->name ?? config('app.name', 'Records Management System') }}</strong>
                <small>Document Management</small>
            </span>
        </a>
        <div class="records-user">
            @if ($currentOrganization)
                <span class="organization-name">{{ $currentOrganization->name }}</span>
            @endif
            <span class="user-name">{{ auth()->user()->name }}</span>
        </div>
    </header>

    <nav class="records-nav" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
        <a href="{{ route('documents.incoming.index') }}" @class(['active' => request()->routeIs('documents.incoming.*')])>Incoming Documents</a>
        <a href="{{ route('documents.outgoing.index') }}" @class(['active' => request()->routeIs('documents.outgoing.*')])>Outgoing Documents</a>
        <a href="{{ route('documents.archive') }}" @class(['active' => request()->routeIs('documents.archive')])>Archive</a>
        @can('manage-administration')
            <span class="nav-section-label">Administration</span>
            <a href="{{ route('admin.organization.edit') }}" @class(['active' => request()->routeIs('admin.organization.*')])>Organization</a>
            <a href="{{ route('admin.departments.index') }}" @class(['active' => request()->routeIs('admin.departments.*')])>Departments</a>
            <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>Users</a>
        @endcan
        <form class="nav-logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" aria-label="Sign out"><span aria-hidden="true">&#8599;</span><span class="logout-label">Logout</span><span class="logout-accessible-copy">Sign out</span></button>
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