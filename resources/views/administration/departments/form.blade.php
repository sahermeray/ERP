@extends('layouts.documents')

@php($isEditing = $department->exists)

@section('title', $isEditing ? __('administration.edit_department') : __('administration.add_department'))

@section('content')
    <div class="page-heading">
        <div><p class="section-label">{{ __('administration.administration') }} / {{ __('administration.departments') }}</p><h1>{{ $isEditing ? __('administration.edit_department') : __('administration.add_department') }}</h1><p class="page-intro">{{ __('administration.department_details_for', ['organization' => $organization->name]) }}</p></div>
        <a class="back-link" href="{{ route('admin.departments.index') }}">&#8592; {{ __('administration.back_to_departments') }}</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>{{ __('app.please_review_fields') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" action="{{ $isEditing ? route('admin.departments.update', $department->id) : route('admin.departments.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>{{ __('administration.department_details') }}</h2><p>{{ __('administration.belongs_to_organization', ['organization' => $organization->name]) }}</p></div></div>
        <div class="document-fields">
            <label class="document-field"><span>{{ __('administration.name') }} <b aria-hidden="true">*</b></span><input name="name" value="{{ old('name', $department->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.code') }}</span><input name="code" value="{{ old('code', $department->code) }}" maxlength="64">@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>{{ __('administration.description') }}</span><textarea name="description" rows="4">{{ old('description', $department->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="remember-option"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $department->is_active))> {{ __('administration.active') }}</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ route('admin.departments.index') }}">{{ __('app.cancel') }}</a><button class="primary-action" type="submit">{{ $isEditing ? __('administration.save_department') : __('administration.add_department') }}</button></footer>
    </form>
@endsection