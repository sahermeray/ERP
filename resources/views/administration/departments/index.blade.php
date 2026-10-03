@extends('layouts.documents')

@section('title', 'Departments')

@section('content')
    <div class="page-heading list-heading">
        <div><p class="section-label">Administration</p><h1>Departments</h1><p class="page-intro">Departments for {{ $organization->name }}.</p></div>
        <a class="primary-action" href="{{ route('admin.departments.create') }}"><span aria-hidden="true">+</span> Add department</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="register-panel" aria-label="Departments">
        <div class="table-meta"><span>{{ $departments->total() }} {{ $departments->total() === 1 ? 'department' : 'departments' }}</span></div>
        <div class="table-scroll"><table class="document-table">
            <thead><tr><th>Department</th><th>Code</th><th>Users</th><th>Documents</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td><strong>{{ $department->name }}</strong>@if ($department->description)<small>{{ $department->description }}</small>@endif</td>
                        <td>{{ $department->code ?: '—' }}</td>
                        <td>{{ $department->memberships_count }}</td>
                        <td>{{ $department->sending_documents_count + $department->receiving_documents_count }}</td>
                        <td><span class="status-badge status-{{ $department->is_active ? 'active' : 'inactive' }}">{{ $department->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="row-actions">
                            <a href="{{ route('admin.departments.edit', $department->id) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.departments.status', $department->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $department->is_active ? 0 : 1 }}">
                                <button type="submit">{{ $department->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.departments.destroy', $department->id) }}" onsubmit="return confirm('Delete this department? Departments in use cannot be deleted.')">
                                @csrf @method('DELETE')<button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell"><strong>No departments yet</strong><span>Add a department to make it available in document forms.</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $departments->links() }}</div>
    </section>
@endsection