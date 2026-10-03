@extends('layouts.documents')

@php($isEditing = $user->exists)

@section('title', $isEditing ? 'Edit User' : 'Add User')

@section('content')
    <div class="page-heading">
        <div><p class="section-label">Administration / Users</p><h1>{{ $isEditing ? 'Edit user' : 'Add user' }}</h1><p class="page-intro">Manage the account and its membership in {{ $organization->name }}.</p></div>
        <a class="back-link" href="{{ route('admin.users.index') }}">&#8592; Back to users</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>Please review the highlighted fields.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" action="{{ $isEditing ? route('admin.users.update', $user->id) : route('admin.users.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>User details</h2><p>Account credentials and organization assignment</p></div></div>
        <div class="document-fields">
            <label class="document-field"><span>Name <b aria-hidden="true">*</b></span><input name="name" value="{{ old('name', $user->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Email <b aria-hidden="true">*</b></span><input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ $isEditing ? 'New password' : 'Password' }}{{ $isEditing ? '' : ' *' }}</span><input type="password" name="password" @required(! $isEditing) autocomplete="new-password">@error('password')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Confirm password{{ $isEditing ? '' : ' *' }}</span><input type="password" name="password_confirmation" @required(! $isEditing) autocomplete="new-password"></label>
            <label class="document-field"><span>Department</span><select name="department_id"><option value="">No department assigned</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id', $membership->department_id) === (string) $department->id)>{{ $department->name }}{{ $department->is_active ? '' : ' (Inactive)' }}</option>@endforeach</select>@error('department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Organization membership</span>@if ($isEditing)<select name="membership_status" required><option value="active" @selected(old('membership_status', $membership->status) === 'active')>Active</option><option value="inactive" @selected(old('membership_status', $membership->status) === 'inactive')>Inactive</option></select>@else<input type="hidden" name="membership_status" value="active"><input value="Active" readonly>@endif @error('membership_status')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Role</span><select name="role" required><option value="user" @selected(old('role', $isAdmin ? 'admin' : 'user') === 'user')>User</option><option value="admin" @selected(old('role', $isAdmin ? 'admin' : 'user') === 'admin')>Administrator</option></select>@error('role')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="remember-option"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user->is_active))> Account active</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ route('admin.users.index') }}">Cancel</a><button class="primary-action" type="submit">{{ $isEditing ? 'Save user' : 'Add user' }}</button></footer>
    </form>
@endsection