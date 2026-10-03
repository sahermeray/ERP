@extends('layouts.documents')

@php
    $incoming = $direction === 'incoming';
    $partyType = $incoming ? 'sender' : 'recipient';
    $party = $document->parties->firstWhere('party_type', $partyType);
    $isEditing = $document->exists;
@endphp

@section('title', ($isEditing ? 'Edit ' : 'Add ').($incoming ? 'Incoming Document' : 'Outgoing Document'))

@section('content')
    <div class="page-heading">
        <div>
            <p class="section-label">{{ $incoming ? 'Incoming register' : 'Outgoing register' }}</p>
            <h1>{{ $isEditing ? 'Edit document' : 'Add '.($incoming ? 'Incoming' : 'Outgoing').' Document' }}</h1>
            <p class="page-intro">Record the official details below. Direction is set by this register.</p>
        </div>
        <a class="back-link" href="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}">&#8592; Back to register</a>
    </div>

    @unless ($organization)
        <section class="notice-panel compact-notice" role="status"><span class="notice-mark" aria-hidden="true">!</span><div><h2>Organization assignment required</h2><p>Your account must belong to an active organization before documents can be created.</p></div></section>
    @endunless

    @if ($errors->any())
        <div class="form-alert" role="alert"><strong>Please review the highlighted fields.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="document-form" method="POST" enctype="multipart/form-data" action="{{ $isEditing ? route('documents.update', $document) : route('documents.store', $direction) }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        <div class="form-section-heading"><span class="step-number">01</span><div><h2>Document details</h2><p>Identification and classification</p></div><span class="direction-tag">{{ $incoming ? 'Incoming' : 'Outgoing' }}</span></div>
        <div class="document-fields">
            <label class="document-field"><span>Reference number <b aria-hidden="true">*</b></span><input name="reference_number" value="{{ old('reference_number', $document->reference_number) }}" required maxlength="64" placeholder="e.g. REG-2026-014">@error('reference_number')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Document date</span><input type="date" name="document_date" value="{{ old('document_date', $document->document_date?->format('Y-m-d')) }}">@error('document_date')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>Subject <b aria-hidden="true">*</b></span><input name="subject" value="{{ old('subject', $document->subject) }}" required maxlength="255" placeholder="Enter the document subject">@error('subject')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>Summary</span><textarea name="summary" rows="3" placeholder="Brief summary of the document">{{ old('summary', $document->summary) }}</textarea>@error('summary')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>{{ $incoming ? 'Sender' : 'Recipient' }}</span><input name="party_name" value="{{ old('party_name', $party?->organization_name ?? $party?->contact_name) }}" maxlength="255" placeholder="Organization or contact name">@error('party_name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Document type</span><select name="document_type_id"><option value="">Select document type</option>@foreach ($documentTypes as $type)<option value="{{ $type->id }}" @selected((string) old('document_type_id', $document->document_type_id) === (string) $type->id)>{{ $type->name }}</option>@endforeach</select>@error('document_type_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Sending department</span><select name="sending_department_id"><option value="">Select department</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('sending_department_id', $document->sending_department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select>@error('sending_department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Receiving department</span><select name="recipient_department_id"><option value="">Select department</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('recipient_department_id', $document->recipient_department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select>@error('recipient_department_id')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Priority</span><select name="priority" required>@foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', $document->priority ?? 'normal') === $value)>{{ $label }}</option>@endforeach</select>@error('priority')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field"><span>Status</span><select name="status" required>@foreach (['registered' => 'Registered', 'under_review' => 'Under review', 'assigned' => 'Assigned', 'responded' => 'Responded', 'closed' => 'Closed'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $document->status ?? 'registered') === $value)>{{ $label }}</option>@endforeach</select>@error('status')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide"><span>Notes</span><textarea name="notes" rows="3" placeholder="Additional internal notes">{{ old('notes', $document->notes) }}</textarea>@error('notes')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="document-field field-wide attachment-picker"><span>Attachments</span><input type="file" name="attachments[]" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" multiple><small>PDF, JPG, JPEG, or PNG. Up to 10 files, 10 MB each.</small>@error('attachments')<small class="field-error">{{ $message }}</small>@enderror @error('attachments.*')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
        <footer class="form-footer"><a class="cancel-link" href="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}">Cancel</a><button class="primary-action" type="submit">{{ $isEditing ? 'Save changes' : 'Register document' }} <span aria-hidden="true">&#8594;</span></button></footer>
    </form>
@endsection