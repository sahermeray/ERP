@extends('layouts.documents')

@php
    $incoming = $direction === 'incoming';
    $partyType = $incoming ? 'sender' : 'recipient';
    $party = $document->parties->firstWhere('party_type', $partyType);
    $isEditing = $document->exists;
@endphp

@section('title', $isEditing ? __('documents.edit_document') : ($incoming ? __('documents.add_incoming') : __('documents.add_outgoing')))

@section('content')
    <div class="page-heading">
        <div>
            <p class="section-label">{{ __('documents.register_label') }}: {{ $incoming ? __('documents.incoming') : __('documents.outgoing') }}</p>
            <h1>{{ $isEditing ? __('documents.edit_document') : __('documents.add_document', ['direction' => $incoming ? __('documents.incoming') : __('documents.outgoing')]) }}</h1>
            <p class="page-intro">{{ __('documents.record_official_details') }}</p>
        </div>
        <a class="back-link" href="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}">&#8592; {{ __('app.back_to_register') }}</a>
    </div>

    @unless ($organization)
        <section class="notice-panel compact-notice" role="status"><span class="notice-mark" aria-hidden="true">!</span><div><h2>{{ __('app.no_organization_heading') }}</h2><p>{{ __('app.no_organization_create') }}</p></div></section>
    @endunless

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>{{ __('app.please_review_fields') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" enctype="multipart/form-data" action="{{ $isEditing ? route('documents.update', $document) : route('documents.store', $direction) }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>{{ __('documents.document_details') }}</h2><p>{{ __('documents.identification_classification') }}</p></div><span class="direction-tag">{{ $incoming ? __('documents.incoming') : __('documents.outgoing') }}</span></div>
        <div class="document-fields">
            <label class="document-field"><span>{{ __('documents.reference_number') }} <b aria-hidden="true">*</b></span><input name="reference_number" value="{{ old('reference_number', $document->reference_number) }}" required maxlength="64" placeholder="{{ __('app.reference_example') }}">@error('reference_number')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('documents.document_date') }}</span><input type="date" name="document_date" value="{{ old('document_date', $document->document_date?->format('Y-m-d')) }}">@error('document_date')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>{{ __('documents.subject') }} <b aria-hidden="true">*</b></span><input name="subject" value="{{ old('subject', $document->subject) }}" required maxlength="255" placeholder="{{ __('documents.enter_subject') }}">@error('subject')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>{{ __('documents.summary') }}</span><textarea name="summary" rows="3" placeholder="{{ __('documents.brief_summary') }}">{{ old('summary', $document->summary) }}</textarea>@error('summary')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ $incoming ? __('documents.sender') : __('documents.recipient') }}</span><input name="party_name" value="{{ old('party_name', $party?->organization_name ?? $party?->contact_name) }}" maxlength="255" placeholder="{{ __('documents.organization_or_contact') }}">@error('party_name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('documents.document_type') }}</span><select name="document_type_id"><option value="">{{ __('documents.select_document_type') }}</option>@foreach ($documentTypes as $type)<option value="{{ $type->id }}" @selected((string) old('document_type_id', $document->document_type_id) === (string) $type->id)>{{ $type->name }}</option>@endforeach</select>@error('document_type_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('documents.sending_department') }}</span><select name="sending_department_id"><option value="">{{ __('documents.select_department') }}</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('sending_department_id', $document->sending_department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select>@error('sending_department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('documents.receiving_department') }}</span><select name="recipient_department_id"><option value="">{{ __('documents.select_department') }}</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('recipient_department_id', $document->recipient_department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select>@error('recipient_department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('documents.priority') }}</span><select name="priority" required>@foreach (['low', 'normal', 'high', 'urgent'] as $value)<option value="{{ $value }}" @selected(old('priority', $document->priority ?? 'normal') === $value)>{{ __('documents.priorities.'.$value) }}</option>@endforeach</select>@error('priority')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ __('documents.status') }}</span><select name="status" required>@foreach (['registered', 'under_review', 'assigned', 'responded', 'closed'] as $value)<option value="{{ $value }}" @selected(old('status', $document->status ?? 'registered') === $value)>{{ __('documents.statuses.'.$value) }}</option>@endforeach</select>@error('status')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>{{ __('documents.notes') }}</span><textarea name="notes" rows="3" placeholder="{{ __('documents.internal_notes') }}">{{ old('notes', $document->notes) }}</textarea>@error('notes')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide attachment-picker"><span>{{ __('documents.attachments') }}</span><input type="file" name="attachments[]" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" multiple><small>{{ __('documents.file_requirements') }}</small>@error('attachments')<small class="field-error">{{ $message }}</small>@enderror @error('attachments.*')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}">{{ __('app.cancel') }}</a><button class="primary-action" type="submit">{{ $isEditing ? __('documents.save_changes') : __('documents.register_document') }} <span aria-hidden="true">&#8594;</span></button></footer>
    </form>
@endsection