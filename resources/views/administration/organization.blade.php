@extends('layouts.documents')

@section('title', __('administration.organization'))

@section('content')
    <div class="page-heading">
        <div><p class="section-label">{{ __('administration.administration') }}</p><h1>{{ __('administration.organization') }}</h1><p class="page-intro">{{ __('administration.organization_profile_intro') }}</p></div>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>{{ __('app.please_review_fields') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" action="{{ route('admin.organization.update') }}">
        @csrf
        @method('PUT')
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>{{ __('administration.organization_details') }}</h2><p>{{ $organization->name }}</p></div></div>
        <div class="document-fields">
            <label class="document-field field-wide"><span>{{ __('administration.organization_name') }} <b aria-hidden="true">*</b></span><input name="name" value="{{ old('name', $organization->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.email') }}</span><input type="email" name="email" value="{{ old('email', $organization->email) }}" maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('administration.phone') }}</span><input name="phone" value="{{ old('phone', $organization->phone) }}" maxlength="32">@error('phone')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>{{ __('administration.address') }}</span><textarea name="address" rows="3">{{ old('address', $organization->address) }}</textarea>@error('address')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>{{ __('administration.description') }}</span><textarea name="description" rows="4">{{ old('description', $organization->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ route('dashboard') }}">{{ __('app.cancel') }}</a><button class="primary-action" type="submit">{{ __('administration.save_organization') }}</button></footer>
    </form>
@endsection