@extends('layouts.documents')

@section('title', __('administration.departments'))

@section('content')
    <div class="page-heading list-heading">
        <div><p class="section-label">{{ __('administration.administration') }}</p><h1>{{ __('administration.departments') }}</h1><p class="page-intro">{{ __('administration.departments_for', ['organization' => $organization->name]) }}</p></div>
        <a class="primary-action" href="{{ route('admin.departments.create') }}"><span aria-hidden="true">+</span> {{ __('administration.add_department') }}</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="register-panel" aria-label="{{ __('administration.departments') }}">
        <div class="table-meta"><span>{{ $departments->total() }} {{ $departments->total() === 1 ? __('administration.department') : __('administration.departments_plural') }}</span></div>
        <div class="table-scroll"><table class="document-table">
            <thead><tr><th>{{ __('administration.department') }}</th><th>{{ __('administration.code') }}</th><th>{{ __('administration.users_count') }}</th><th>{{ __('administration.documents_count') }}</th><th>{{ __('documents.status') }}</th><th><span class="visually-hidden">{{ __('app.actions') }}</span></th></tr></thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td><strong>{{ $department->name }}</strong>@if ($department->description)<small>{{ $department->description }}</small>@endif</td>
                        <td>{{ $department->code ?: '—' }}</td>
                        <td>{{ $department->memberships_count }}</td>
                        <td>{{ $department->sending_documents_count + $department->receiving_documents_count }}</td>
                        <td><span class="status-badge status-{{ $department->is_active ? 'active' : 'inactive' }}">{{ $department->is_active ? __('administration.active') : __('administration.inactive') }}</span></td>
                        <td class="row-actions">
                            <a href="{{ route('admin.departments.edit', $department->id) }}">{{ __('app.edit') }}</a>
                            <form method="POST" action="{{ route('admin.departments.status', $department->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $department->is_active ? 0 : 1 }}">
                                <button type="submit">{{ $department->is_active ? __('administration.deactivate') : __('administration.activate') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.departments.destroy', $department->id) }}" onsubmit="return confirm(@js(__('administration.delete_department_confirm'))) ">
                                @csrf @method('DELETE')<button type="submit">{{ __('app.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-cell"><strong>{{ __('administration.no_departments') }}</strong><span>{{ __('administration.add_department_hint') }}</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $departments->links() }}</div>
    </section>
@endsection