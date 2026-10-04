@extends('layouts.documents')

@php($isEditing = $user->exists)

@section('title', $isEditing ? __('administration.edit_user') : __('administration.add_user'))

@section('content')
    <div class="page-heading">
        <div><p class="section-label">{{ __('administration.administration') }} / {{ __('administration.users') }}</p><h1>{{ $isEditing ? __('administration.edit_user') : __('administration.add_user') }}</h1><p class="page-intro">{{ __('administration.users_for', ['organization' => $organization->name]) }}</p></div>
        <a class="back-link" href="{{ route('admin.users.index') }}">&#8592; {{ __('administration.back_to_users') }}</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>{{ __('app.please_review_fields') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" action="{{ $isEditing ? route('admin.users.update', $user->id) : route('admin.users.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>{{ __('administration.user_details') }}</h2><p>{{ __('administration.account_credentials_assignment') }}</p></div></div>
        <div class="document-fields">
            <label class="document-field"><span>{{ __('administration.name') }} <b aria-hidden="true">*</b></span><input name="name" value="{{ old('name', $user->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.email') }} <b aria-hidden="true">*</b></span><input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ $isEditing ? __('administration.new_password') : __('app.password') }}{{ $isEditing ? '' : ' *' }}</span><input type="password" name="password" @required(! $isEditing) autocomplete="new-password">@error('password')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.confirm_password') }}{{ $isEditing ? '' : ' *' }}</span><input type="password" name="password_confirmation" @required(! $isEditing) autocomplete="new-password"></label>
            <label class="document-field"><span>{{ __('app.department') }}</span><select name="department_id"><option value="">{{ __('administration.no_department_assigned') }}</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id', $membership->department_id) === (string) $department->id)>{{ $department->name }}{{ $department->is_active ? '' : ' ('.__('administration.inactive').')' }}</option>@endforeach</select>@error('department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.organization_membership') }}</span>@if ($isEditing)<select name="membership_status" required><option value="active" @selected(old('membership_status', $membership->status) === 'active')>{{ __('administration.active') }}</option><option value="inactive" @selected(old('membership_status', $membership->status) === 'inactive')>{{ __('administration.inactive') }}</option></select>@else<input type="hidden" name="membership_status" value="active"><input value="{{ __('administration.active') }}" readonly>@endif @error('membership_status')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.role') }}</span><select name="role" required><option value="user" @selected(old('role', $isAdmin ? 'admin' : 'user') === 'user')>{{ __('administration.user') }}</option><option value="admin" @selected(old('role', $isAdmin ? 'admin' : 'user') === 'admin')>{{ __('administration.administrator') }}</option></select>@error('role')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="remember-option"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user->is_active))> {{ __('administration.account_active') }}</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ route('admin.users.index') }}">{{ __('app.cancel') }}</a><button class="primary-action" type="submit">{{ $isEditing ? __('administration.save_user') : __('administration.add_user') }}</button></footer>
    </form>
@endsection