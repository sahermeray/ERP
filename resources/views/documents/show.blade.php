@extends('layouts.documents')

@php
    $incoming = $document->direction === 'incoming';
    $partyType = $incoming ? 'sender' : 'recipient';
    $party = $document->parties->firstWhere('party_type', $partyType);
@endphp

@section('title', $document->reference_number)

@section('content')
    @php($isArchived = $document->status === 'closed')
    <div class="page-heading list-heading">
        <div><p class="section-label">{{ $isArchived ? __('documents.archived_document') : ($incoming ? __('documents.incoming_document') : __('documents.outgoing_document')) }}</p><h1>{{ $document->reference_number }}</h1><p class="page-intro">{{ $document->subject }}</p></div>
        <div class="heading-actions"><a class="secondary-button" href="{{ $isArchived ? route('documents.archive') : ($incoming ? route('documents.incoming.index') : route('documents.outgoing.index')) }}">{{ $isArchived ? __('documents.back_to_archive') : __('app.back_to_register') }}</a><a class="primary-action" href="{{ route('documents.edit', $document) }}">{{ __('documents.edit_document_action') }}</a></div>
    </div>
    <section class="detail-panel">
        <div class="detail-topline"><span class="direction-tag">{{ __('documents.'.$document->direction) }}</span><span class="status-badge status-{{ str_replace('_', '-', $document->status) }}">{{ __('documents.statuses.'.$document->status) }}</span><span class="priority-label priority-{{ $document->priority }}">{{ __('documents.priority_label', ['priority' => __('documents.priorities.'.$document->priority)]) }}</span></div>
        <dl class="detail-grid">
            <div><dt>{{ __('documents.reference_number') }}</dt><dd>{{ $document->reference_number }}</dd></div>
            <div><dt>{{ __('documents.document_date') }}</dt><dd>{{ $document->document_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '—' }}</dd></div>
            <div><dt>{{ __('documents.subject') }}</dt><dd>{{ $document->subject }}</dd></div>
            <div><dt>{{ $incoming ? __('documents.sender') : __('documents.recipient') }}</dt><dd>{{ $party?->organization_name ?? $party?->contact_name ?? '—' }}</dd></div>
            <div><dt>{{ __('documents.sending_department') }}</dt><dd>{{ $document->sendingDepartment?->name ?? '—' }}</dd></div>
            <div><dt>{{ __('documents.receiving_department') }}</dt><dd>{{ $document->recipientDepartment?->name ?? '—' }}</dd></div>
            <div><dt>{{ __('documents.document_type') }}</dt><dd>{{ $document->documentType?->name ?? '—' }}</dd></div>
            <div><dt>{{ __('documents.registered_by') }}</dt><dd>{{ $document->creator?->name ?? '—' }}</dd></div>
            <div class="detail-wide"><dt>{{ __('documents.summary') }}</dt><dd>{{ $document->summary ?: '—' }}</dd></div>
            <div class="detail-wide"><dt>{{ __('documents.notes') }}</dt><dd>{{ $document->notes ?: '—' }}</dd></div>
        </dl>
    </section>
    <section class="attachment-section" aria-labelledby="attachments-heading">
        <div class="attachment-section-heading"><div><h2 id="attachments-heading">{{ __('documents.attachments') }}</h2><p>{{ $document->attachments->count() }} {{ $document->attachments->count() === 1 ? __('documents.file') : __('documents.files') }}</p></div></div>
        @forelse ($document->attachments as $attachment)
            <div class="attachment-row">
                <div class="attachment-information"><small>{{ __('documents.file_name') }}</small><strong>{{ $attachment->original_file_name }}</strong><span>{{ __('documents.file_type') }}: {{ strtoupper(pathinfo($attachment->original_file_name, PATHINFO_EXTENSION)) }} · {{ $attachment->mime_type }} · {{ __('documents.file_size') }}: @if ($attachment->size_bytes >= 1048576){{ number_format($attachment->size_bytes / 1048576, 1) }} MB @else{{ number_format($attachment->size_bytes / 1024, 1) }} KB @endif</span></div>
                <div class="attachment-actions"><a href="{{ route('documents.attachments.open', [$document, $attachment]) }}" target="_blank" rel="noopener">{{ __('documents.view_open') }}</a><a href="{{ route('documents.attachments.download', [$document, $attachment]) }}">{{ __('documents.download') }}</a>@unless ($isArchived)<form method="POST" action="{{ route('documents.attachments.destroy', [$document, $attachment]) }}" onsubmit="return confirm(@js(__('documents.delete_attachment_confirm')))">@csrf @method('DELETE')<button type="submit">{{ __('app.delete') }}</button></form>@endunless</div>
            </div>
        @empty
            <p class="attachments-empty">{{ __('documents.no_attachments') }}</p>
        @endforelse
    </section>
@endsection