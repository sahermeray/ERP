@extends('layouts.documents')

@section('title', __('administration.users'))

@section('content')
    <div class="page-heading list-heading">
        <div><p class="section-label">{{ __('administration.administration') }}</p><h1>{{ __('administration.users') }}</h1><p class="page-intro">{{ __('administration.users_for', ['organization' => $organization->name]) }}</p></div>
        <a class="primary-action" href="{{ route('admin.users.create') }}"><span aria-hidden="true">+</span> {{ __('administration.add_user') }}</a>
    </div>

    <section class="register-panel" aria-label="{{ __('app.organization_users') }}">
        <div class="table-meta"><span>{{ $memberships->total() }} {{ $memberships->total() === 1 ? __('administration.user_count') : __('administration.users_plural') }}</span></div>
        <div class="table-scroll"><table class="document-table">
            <thead><tr><th>{{ __('administration.name') }}</th><th>{{ __('administration.email') }}</th><th>{{ __('app.department') }}</th><th>{{ __('administration.organization_access') }}</th><th>{{ __('administration.account') }}</th><th>{{ __('administration.role') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($memberships as $membership)
                    <tr>
                        <td>{{ $membership->user->name }}</td>
                        <td>{{ $membership->user->email }}</td>
                        <td>{{ $membership->department?->name ?? __('administration.department_not_assigned') }}</td>
                        <td>{{ __('administration.'.strtolower($membership->status)) }}</td>
                        <td><span class="status-badge status-{{ $membership->user->is_active ? 'active' : 'inactive' }}">{{ $membership->user->is_active ? __('administration.active') : __('administration.inactive') }}</span></td>
                        <td>{{ $membership->roles->contains('slug', 'admin') || $membership->user->email === config('app.administrator_email') ? __('administration.administrator') : __('administration.user') }}</td>
                        <td class="row-actions"><a href="{{ route('admin.users.edit', $membership->user_id) }}">{{ __('app.edit') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell"><strong>{{ __('administration.no_organization_users') }}</strong><span>{{ __('administration.add_user_hint') }}</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $memberships->links() }}</div>
    </section>
@endsection