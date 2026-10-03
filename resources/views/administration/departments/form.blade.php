@extends('layouts.documents')

@php($isEditing = $department->exists)

@section('title', $isEditing ? 'Edit Department' : 'Add Department')

@section('content')
    <div class="page-heading">
        <div><p class="section-label">Administration / Departments</p><h1>{{ $isEditing ? 'Edit department' : 'Add department' }}</h1><p class="page-intro">Department details for {{ $organization->name }}.</p></div>
        <a class="back-link" href="{{ route('admin.departments.index') }}">&#8592; Back to departments</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>Please review the highlighted fields.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" action="{{ $isEditing ? route('admin.departments.update', $department->id) : route('admin.departments.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>Department details</h2><p>Each department belongs to {{ $organization->name }}.</p></div></div>
        <div class="document-fields">
            <label class="document-field"><span>Name <b aria-hidden="true">*</b></span><input name="name" value="{{ old('name', $department->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Code</span><input name="code" value="{{ old('code', $department->code) }}" maxlength="64">@error('code')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>Description</span><textarea name="description" rows="4">{{ old('description', $department->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="remember-option"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $department->is_active))> Active</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ route('admin.departments.index') }}">Cancel</a><button class="primary-action" type="submit">{{ $isEditing ? 'Save department' : 'Add department' }}</button></footer>
    </form>
@endsection