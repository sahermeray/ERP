@extends('layouts.documents')

@section('title', 'Archive')

@section('content')
    <div class="page-heading"><div><p class="section-label">Closed documents</p><h1>Archive</h1><p class="page-intro">Incoming and outgoing documents with Closed status.</p></div></div>
    @unless ($organization)
        <section class="notice-panel compact-notice" role="status"><span class="notice-mark" aria-hidden="true">!</span><div><h2>Organization assignment required</h2><p>An active organization membership is needed to access the archive.</p></div></section>
    @endunless
    <section class="register-panel">
        <form method="GET" action="{{ route('documents.archive') }}" class="filter-form">
            <div class="search-row">
                <label class="search-field" for="search">
                    <span class="search-icon" aria-hidden="true">&#9906;</span>
                    <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search reference or subject">
                </label>
                <button class="secondary-button" type="submit">Search</button>
                @if (request()->query())
                    <a class="clear-link" href="{{ route('documents.archive') }}">Clear filters</a>
                @endif
            </div>
            <div class="filter-grid">
                <label class="filter-field"><span>Reference number</span><input name="reference_number" value="{{ request('reference_number') }}" placeholder="Search reference"></label>
                <label class="filter-field"><span>Subject</span><input name="subject" value="{{ request('subject') }}" placeholder="Search subject"></label>
                <label class="filter-field"><span>Direction</span><select name="direction"><option value="">Incoming and outgoing</option><option value="incoming" @selected(request('direction') === 'incoming')>Incoming</option><option value="outgoing" @selected(request('direction') === 'outgoing')>Outgoing</option></select></label>
                <label class="filter-field"><span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach (['registered' => 'Registered', 'under_review' => 'Under review', 'assigned' => 'Assigned', 'responded' => 'Responded', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
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
        <div class="table-meta"><span>{{ $documents->total() }} {{ $documents->total() === 1 ? 'closed document' : 'closed documents' }}</span></div>
        <div class="table-scroll"><table class="document-table">
            <thead><tr><th>Reference</th><th>Subject</th><th>Direction</th><th>Status</th><th>Document date</th><th>Department</th><th>Attachments</th><th></th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr><td class="reference-cell">{{ $document->reference_number }}</td><td><a class="subject-link" href="{{ route('documents.show', $document) }}">{{ $document->subject }}</a></td><td>{{ ucfirst($document->direction) }}</td><td><span class="status-badge status-closed">Closed</span></td><td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td><td>{{ $document->sendingDepartment?->name ?? '—' }}{{ $document->sendingDepartment && $document->recipientDepartment ? ' / ' : '' }}{{ $document->recipientDepartment?->name ?? '' }}</td><td>{{ $document->attachments_count }}</td><td class="row-actions"><a href="{{ route('documents.show', $document) }}">View</a></td></tr>
                @empty
                    <tr><td colspan="8" class="empty-cell"><span class="empty-mark" aria-hidden="true">&#8212;</span><strong>No closed documents found</strong><span>Try adjusting your filters.</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $documents->links() }}</div>
    </section>
@endsection