@extends('layouts.documents')

@php
    $incoming = $direction === 'incoming';
    $partyFilter = $incoming ? 'sender' : 'recipient';
    $partyTitle = $incoming ? 'Sender' : 'Recipient';
@endphp

@section('title', $incoming ? 'Incoming Documents' : 'Outgoing Documents')

@section('content')
    <div class="page-heading list-heading">
        <div>
            <p class="section-label">Correspondence register</p>
            <h1>{{ $incoming ? 'Incoming Documents' : 'Outgoing Documents' }}</h1>
            <p class="page-intro">{{ $incoming ? 'Documents received by your organization.' : 'Documents issued by your organization.' }}</p>
        </div>
        <a class="primary-action" href="{{ route('documents.create', $direction) }}"><span aria-hidden="true">+</span> Add {{ $incoming ? 'Incoming' : 'Outgoing' }} Document</a>
    </div>

    @unless ($organization)
        <section class="notice-panel compact-notice" role="status">
            <span class="notice-mark" aria-hidden="true">!</span>
            <div><h2>Organization assignment required</h2><p>An active organization membership is needed to access this register.</p></div>
        </section>
    @endunless

    <section class="register-panel" aria-label="Document register">
        <form method="GET" action="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}" class="filter-form">
            <div class="search-row">
                <label class="search-field" for="search">
                    <span class="search-icon" aria-hidden="true">&#9906;</span>
                    <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search reference, subject or {{ strtolower($partyTitle) }}">
                </label>
                <button class="secondary-button" type="submit">Search</button>
                @if (request()->query())
                    <a class="clear-link" href="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}">Clear filters</a>
                @endif
            </div>
            <div class="filter-grid">
                <label class="filter-field"><span>Reference number</span><input name="reference_number" value="{{ request('reference_number') }}" placeholder="e.g. REG-2026-014"></label>
                <label class="filter-field"><span>Subject</span><input name="subject" value="{{ request('subject') }}" placeholder="Search subject"></label>
                <label class="filter-field"><span>{{ $partyTitle }}</span><input name="{{ $partyFilter }}" value="{{ request($partyFilter) }}" placeholder="Search {{ strtolower($partyTitle) }}"></label>
                <label class="filter-field"><span>Department</span>
                    <select name="department_id">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field"><span>Date from</span><input type="date" name="date_from" value="{{ request('date_from') }}"></label>
                <label class="filter-field"><span>Date to</span><input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            </div>
            <div class="filter-actions"><button class="filter-button" type="submit">Apply filters</button></div>
        </form>

        <div class="table-meta"><span>{{ $documents->total() }} {{ $documents->total() === 1 ? 'record' : 'records' }}</span><span>Page {{ $documents->currentPage() }} of {{ max(1, $documents->lastPage()) }}</span></div>
        <div class="table-scroll">
            <table class="document-table">
                <thead><tr><th>Reference</th><th>Subject</th><th>{{ $partyTitle }}</th><th>Department</th><th>Document date</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php
                            $party = $document->parties->firstWhere('party_type', $partyFilter);
                            $department = $incoming ? $document->recipientDepartment : $document->sendingDepartment;
                        @endphp
                        <tr>
                            <td class="reference-cell">{{ $document->reference_number }}</td>
                            <td><a class="subject-link" href="{{ route('documents.show', $document) }}">{{ $document->subject }}</a><small>{{ $document->documentType?->name ?? 'Unclassified' }}</small></td>
                            <td>{{ $party?->organization_name ?? $party?->contact_name ?? '—' }}</td>
                            <td>{{ $department?->name ?? '—' }}</td>
                            <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                            <td><span class="status-badge status-{{ str_replace('_', '-', $document->status) }}">{{ str_replace('_', ' ', ucfirst($document->status)) }}</span></td>
                            <td class="row-actions"><a href="{{ route('documents.show', $document) }}">View</a><a href="{{ route('documents.edit', $document) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell"><span class="empty-mark" aria-hidden="true">&#8212;</span><strong>No documents found</strong><span>Try adjusting your filters or register a new document.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $documents->links() }}</div>
    </section>
@endsection