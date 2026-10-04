@extends('layouts.documents')

@php
    $incoming = $direction === 'incoming';
    $partyFilter = $incoming ? 'sender' : 'recipient';
    $partyTitle = $incoming ? __('documents.sender') : __('documents.recipient');
@endphp

@section('title', $incoming ? __('documents.incoming_documents') : __('documents.outgoing_documents'))

@section('content')
    <div class="page-heading list-heading">
        <div>
            <p class="section-label">{{ __('documents.register_label') }}</p>
            <h1>{{ $incoming ? __('documents.incoming_documents') : __('documents.outgoing_documents') }}</h1>
            <p class="page-intro">{{ $incoming ? __('documents.incoming_description') : __('documents.outgoing_description') }}</p>
        </div>
        <a class="primary-action" href="{{ route('documents.create', $direction) }}"><span aria-hidden="true">+</span> {{ $incoming ? __('documents.add_incoming') : __('documents.add_outgoing') }}</a>
    </div>

    @unless ($organization)
        <section class="notice-panel compact-notice" role="status">
            <span class="notice-mark" aria-hidden="true">!</span>
            <div><h2>{{ __('app.no_organization_heading') }}</h2><p>{{ __('app.no_organization_register') }}</p></div>
        </section>
    @endunless

    <section class="register-panel" aria-label="{{ __('app.document_register') }}">
        <form method="GET" action="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}" class="filter-form">
            <div class="search-row">
                <label class="search-field" for="search">
                    <span class="search-icon" aria-hidden="true">&#9906;</span>
                    <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('documents.search_document_party', ['party' => mb_strtolower($partyTitle)]) }}">
                </label>
                <button class="secondary-button" type="submit">{{ __('app.search') }}</button>
                @if (request()->query())
                    <a class="clear-link" href="{{ $incoming ? route('documents.incoming.index') : route('documents.outgoing.index') }}">{{ __('app.clear_filters') }}</a>
                @endif
            </div>
            <div class="filter-grid">
                <label class="filter-field"><span>{{ __('documents.reference_number') }}</span><input name="reference_number" value="{{ request('reference_number') }}" placeholder="{{ __('app.reference_example') }}"></label>
                <label class="filter-field"><span>{{ __('documents.subject') }}</span><input name="subject" value="{{ request('subject') }}" placeholder="{{ __('app.search_subject') }}"></label>
                <label class="filter-field"><span>{{ $partyTitle }}</span><input name="{{ $partyFilter }}" value="{{ request($partyFilter) }}" placeholder="{{ __('documents.search_document_party', ['party' => mb_strtolower($partyTitle)]) }}"></label>
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

        <div class="table-meta"><span>{{ $documents->total() }} {{ $documents->total() === 1 ? __('app.record') : __('app.records') }}</span><span>{{ __('app.page_of', ['current' => $documents->currentPage(), 'last' => max(1, $documents->lastPage())]) }}</span></div>
        <div class="table-scroll">
            <table class="document-table">
                <thead><tr><th>{{ __('app.reference') }}</th><th>{{ __('documents.subject') }}</th><th>{{ $partyTitle }}</th><th>{{ __('app.department') }}</th><th>{{ __('documents.document_date') }}</th><th>{{ __('documents.status') }}</th><th><span class="visually-hidden">{{ __('app.actions') }}</span></th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php
                            $party = $document->parties->firstWhere('party_type', $partyFilter);
                            $department = $incoming ? $document->recipientDepartment : $document->sendingDepartment;
                        @endphp
                        <tr>
                            <td class="reference-cell">{{ $document->reference_number }}</td>
                            <td><a class="subject-link" href="{{ route('documents.show', $document) }}">{{ $document->subject }}</a><small>{{ $document->documentType?->name ?? __('documents.unclassified') }}</small></td>
                            <td>{{ $party?->organization_name ?? $party?->contact_name ?? '—' }}</td>
                            <td>{{ $department?->name ?? '—' }}</td>
                            <td>{{ $document->document_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '—' }}</td>
                            <td><span class="status-badge status-{{ str_replace('_', '-', $document->status) }}">{{ __('documents.statuses.'.$document->status) }}</span></td>
                            <td class="row-actions"><a href="{{ route('documents.show', $document) }}">{{ __('app.view') }}</a><a href="{{ route('documents.edit', $document) }}">{{ __('app.edit') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell"><span class="empty-mark" aria-hidden="true">&#8212;</span><strong>{{ __('app.no_documents') }}</strong><span>{{ __('app.adjust_filters_register') }}</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $documents->links() }}</div>
    </section>
@endsection