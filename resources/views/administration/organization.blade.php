@extends('layouts.documents')

@section('title', 'Organization')

@section('content')
    <div class="page-heading">
        <div><p class="section-label">Administration</p><h1>Organization</h1><p class="page-intro">Manage the organization profile shown throughout the system.</p></div>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>Please review the highlighted fields.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" action="{{ route('admin.organization.update') }}">
        @csrf
        @method('PUT')
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>Organization details</h2><p>{{ $organization->name }}</p></div></div>
        <div class="document-fields">
            <label class="document-field field-wide"><span>Organization name <b aria-hidden="true">*</b></span><input name="name" value="{{ old('name', $organization->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Email</span><input type="email" name="email" value="{{ old('email', $organization->email) }}" maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Phone</span><input name="phone" value="{{ old('phone', $organization->phone) }}" maxlength="32">@error('phone')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>Address</span><textarea name="address" rows="3">{{ old('address', $organization->address) }}</textarea>@error('address')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>Description</span><textarea name="description" rows="4">{{ old('description', $organization->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ route('dashboard') }}">Cancel</a><button class="primary-action" type="submit">Save organization</button></footer>
    </form>
@endsection