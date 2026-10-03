@extends('layouts.documents')

@section('title', 'Users')

@section('content')
    <div class="page-heading list-heading">
        <div><p class="section-label">Administration</p><h1>Users</h1><p class="page-intro">Organization memberships and access for {{ $organization->name }}.</p></div>
        <a class="primary-action" href="{{ route('admin.users.create') }}"><span aria-hidden="true">+</span> Add user</a>
    </div>

    <section class="register-panel" aria-label="Organization users">
        <div class="table-meta"><span>{{ $memberships->total() }} {{ $memberships->total() === 1 ? 'user' : 'users' }}</span></div>
        <div class="table-scroll"><table class="document-table">
            <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Organization access</th><th>Account</th><th>Role</th><th></th></tr></thead>
            <tbody>
                @forelse ($memberships as $membership)
                    <tr>
                        <td>{{ $membership->user->name }}</td>
                        <td>{{ $membership->user->email }}</td>
                        <td>{{ $membership->department?->name ?? '—' }}</td>
                        <td>{{ ucfirst($membership->status) }}</td>
                        <td><span class="status-badge status-{{ $membership->user->is_active ? 'active' : 'inactive' }}">{{ $membership->user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $membership->roles->contains('slug', 'admin') || $membership->user->email === config('app.administrator_email') ? 'Administrator' : 'User' }}</td>
                        <td class="row-actions"><a href="{{ route('admin.users.edit', $membership->user_id) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-cell"><strong>No organization users</strong><span>Add a user to create an active membership.</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $memberships->links() }}</div>
    </section>
@endsection