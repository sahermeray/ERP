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
        <div><p class="section-label">{{ $isArchived ? 'Archived document' : ($incoming ? 'Incoming document' : 'Outgoing document') }}</p><h1>{{ $document->reference_number }}</h1><p class="page-intro">{{ $document->subject }}</p></div>
        <div class="heading-actions"><a class="secondary-button" href="{{ $isArchived ? route('documents.archive') : ($incoming ? route('documents.incoming.index') : route('documents.outgoing.index')) }}">{{ $isArchived ? 'Back to archive' : 'Back to register' }}</a><a class="primary-action" href="{{ route('documents.edit', $document) }}">Edit document</a></div>
    </div>
    <section class="detail-panel">
        <div class="detail-topline"><span class="direction-tag">{{ ucfirst($document->direction) }}</span><span class="status-badge status-{{ str_replace('_', '-', $document->status) }}">{{ str_replace('_', ' ', ucfirst($document->status)) }}</span><span class="priority-label priority-{{ $document->priority }}">{{ ucfirst($document->priority) }} priority</span></div>
        <dl class="detail-grid">
            <div><dt>Reference number</dt><dd>{{ $document->reference_number }}</dd></div>
            <div><dt>Document date</dt><dd>{{ $document->document_date?->format('d M Y') ?? '—' }}</dd></div>
            <div><dt>Subject</dt><dd>{{ $document->subject }}</dd></div>
            <div><dt>{{ $incoming ? 'Sender' : 'Recipient' }}</dt><dd>{{ $party?->organization_name ?? $party?->contact_name ?? '—' }}</dd></div>
            <div><dt>Sending department</dt><dd>{{ $document->sendingDepartment?->name ?? '—' }}</dd></div>
            <div><dt>Receiving department</dt><dd>{{ $document->recipientDepartment?->name ?? '—' }}</dd></div>
            <div><dt>Document type</dt><dd>{{ $document->documentType?->name ?? '—' }}</dd></div>
            <div><dt>Registered by</dt><dd>{{ $document->creator?->name ?? '—' }}</dd></div>
            <div class="detail-wide"><dt>Summary</dt><dd>{{ $document->summary ?: '—' }}</dd></div>
            <div class="detail-wide"><dt>Notes</dt><dd>{{ $document->notes ?: '—' }}</dd></div>
        </dl>
    </section>
    <section class="attachment-section" aria-labelledby="attachments-heading">
        <div class="attachment-section-heading"><div><h2 id="attachments-heading">Attachments</h2><p>{{ $document->attachments->count() }} {{ $document->attachments->count() === 1 ? 'file' : 'files' }}</p></div></div>
        @forelse ($document->attachments as $attachment)
            <div class="attachment-row">
                <div class="attachment-information"><strong>{{ $attachment->original_file_name }}</strong><span>{{ strtoupper(pathinfo($attachment->original_file_name, PATHINFO_EXTENSION)) }} · {{ $attachment->mime_type }} · @if ($attachment->size_bytes >= 1048576){{ number_format($attachment->size_bytes / 1048576, 1) }} MB @else{{ number_format($attachment->size_bytes / 1024, 1) }} KB @endif</span></div>
                <div class="attachment-actions"><a href="{{ route('documents.attachments.open', [$document, $attachment]) }}" target="_blank" rel="noopener">View / Open</a><a href="{{ route('documents.attachments.download', [$document, $attachment]) }}">Download</a>@unless ($isArchived)<form method="POST" action="{{ route('documents.attachments.destroy', [$document, $attachment]) }}" onsubmit="return confirm('Delete this attachment?')">@csrf @method('DELETE')<button type="submit">Delete</button></form>@endunless</div>
            </div>
        @empty
            <p class="attachments-empty">No attachments have been uploaded.</p>
        @endforelse
    </section>
@endsection