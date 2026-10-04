@extends('layouts.documents')

@section('title', __('documents.archive'))

@section('content')
    <div class="page-heading"><div><p class="section-label">{{ __('app.closed_documents') }}</p><h1>{{ __('documents.archive') }}</h1><p class="page-intro">{{ __('documents.archive_description') }}</p></div></div>
    @unless ($organization)
        <section class="notice-panel compact-notice" role="status"><span class="notice-mark" aria-hidden="true">!</span><div><h2>{{ __('app.no_organization_heading') }}</h2><p>{{ __('app.no_organization_archive') }}</p></div></section>
    @endunless
    <section class="register-panel">
        <form method="GET" action="{{ route('documents.archive') }}" class="filter-form">
            <div class="search-row">
                <label class="search-field" for="search">
                    <span class="search-icon" aria-hidden="true">&#9906;</span>
                    <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('documents.search_reference_subject') }}">
                </label>
                <button class="secondary-button" type="submit">{{ __('app.search') }}</button>
                @if (request()->query())
                    <a class="clear-link" href="{{ route('documents.archive') }}">{{ __('app.clear_filters') }}</a>
                @endif
            </div>
            <div class="filter-grid">
                <label class="filter-field"><span>{{ __('documents.reference_number') }}</span><input name="reference_number" value="{{ request('reference_number') }}" placeholder="{{ __('documents.search_reference') }}"></label>
                <label class="filter-field"><span>{{ __('documents.subject') }}</span><input name="subject" value="{{ request('subject') }}" placeholder="{{ __('app.search_subject') }}"></label>
                <label class="filter-field"><span>{{ __('documents.direction') }}</span><select name="direction"><option value="">{{ __('documents.all_directions') }}</option><option value="incoming" @selected(request('direction') === 'incoming')>{{ __('documents.incoming') }}</option><option value="outgoing" @selected(request('direction') === 'outgoing')>{{ __('documents.outgoing') }}</option></select></label>
                <label class="filter-field"><span>{{ __('documents.status') }}</span>
                    <select name="status">
                        <option value="">{{ __('documents.all_statuses') }}</option>
                        @foreach (['registered', 'under_review', 'assigned', 'responded', 'closed'] as $value)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ __('documents.statuses.'.$value) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field"><span>{{ __('app.department') }}</span>
                    <select name="department_id">
                        <option value="">{{ __('app.all_departments') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field"><span>{{ __('app.date_from') }}</span><input type="date" name="date_from" value="{{ request('date_from') }}"></label>
                <label class="filter-field"><span>{{ __('app.date_to') }}</span><input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            </div>
            <div class="filter-actions"><button class="filter-button" type="submit">{{ __('app.apply_filters') }}</button></div>
        </form>
        <div class="table-meta"><span>{{ $documents->total() }} {{ $documents->total() === 1 ? __('documents.closed_document') : __('documents.closed_documents_plural') }}</span></div>
        <div class="table-scroll"><table class="document-table">
            <thead><tr><th>{{ __('app.reference') }}</th><th>{{ __('documents.subject') }}</th><th>{{ __('documents.direction') }}</th><th>{{ __('documents.status') }}</th><th>{{ __('documents.document_date') }}</th><th>{{ __('app.department') }}</th><th>{{ __('documents.attachments') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr><td class="reference-cell">{{ $document->reference_number }}</td><td><a class="subject-link" href="{{ route('documents.show', $document) }}">{{ $document->subject }}</a></td><td>{{ __('documents.'.$document->direction) }}</td><td><span class="status-badge status-closed">{{ __('documents.statuses.closed') }}</span></td><td>{{ $document->document_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '—' }}</td><td>{{ $document->sendingDepartment?->name ?? '—' }}{{ $document->sendingDepartment && $document->recipientDepartment ? ' / ' : '' }}{{ $document->recipientDepartment?->name ?? '' }}</td><td>{{ $document->attachments_count }}</td><td class="row-actions"><a href="{{ route('documents.show', $document) }}">{{ __('app.view') }}</a></td></tr>
                @empty
                    <tr><td colspan="8" class="empty-cell"><span class="empty-mark" aria-hidden="true">&#8212;</span><strong>{{ __('documents.no_closed_documents') }}</strong><span>{{ __('app.adjust_filters') }}</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $documents->links() }}</div>
    </section>
@endsection