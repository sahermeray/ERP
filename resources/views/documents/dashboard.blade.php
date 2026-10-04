@extends('layouts.documents')

@section('title', __('app.navigation.dashboard'))

@section('content')
    <div class="page-heading">
        <div>
            <p class="section-label">{{ __('app.records_workspace') }}</p>
            <h1>{{ $organization?->name ?? __('app.page_title') }}</h1>
            <p class="page-intro">{{ __('app.manage_correspondence') }}</p>
        </div>
    </div>

    @unless ($organization)
        <section class="notice-panel" role="status">
            <span class="notice-mark" aria-hidden="true">!</span>
            <div>
                <h2>{{ __('app.no_organization_heading') }}</h2>
                <p>{{ __('app.no_organization_documents') }}</p>
            </div>
        </section>
    @endunless

    <section class="summary-grid" aria-label="{{ __('app.document_totals') }}">
        <a class="summary-item" href="{{ route('documents.incoming.index') }}">
            <span class="summary-label">{{ __('documents.incoming') }}</span>
            <strong>{{ number_format($incomingCount) }}</strong>
            <span class="summary-link">{{ __('app.view_register') }} <span aria-hidden="true">&#8594;</span></span>
        </a>
        <a class="summary-item" href="{{ route('documents.outgoing.index') }}">
            <span class="summary-label">{{ __('documents.outgoing') }}</span>
            <strong>{{ number_format($outgoingCount) }}</strong>
            <span class="summary-link">{{ __('app.view_register') }} <span aria-hidden="true">&#8594;</span></span>
        </a>
        <a class="summary-item" href="{{ route('documents.archive') }}">
            <span class="summary-label">{{ __('app.closed_documents') }}</span>
            <strong>{{ number_format($archiveCount) }}</strong>
            <span class="summary-link">{{ __('app.browse_archive') }} <span aria-hidden="true">&#8594;</span></span>
        </a>
    </section>

    <section class="workspace-links" aria-labelledby="registers-heading">
        <div class="section-heading">
            <div>
                <p class="section-label">{{ __('app.registers') }}</p>
                <h2 id="registers-heading">{{ __('app.correspondence') }}</h2>
            </div>
        </div>
        <div class="register-links">
            <a href="{{ route('documents.incoming.index') }}">
                <span class="register-index">01</span>
                <span><strong>{{ __('documents.incoming_documents') }}</strong><small>{{ __('app.register_received') }}</small></span>
                <span class="register-arrow" aria-hidden="true">&#8594;</span>
            </a>
            <a href="{{ route('documents.outgoing.index') }}">
                <span class="register-index">02</span>
                <span><strong>{{ __('documents.outgoing_documents') }}</strong><small>{{ __('app.register_sent') }}</small></span>
                <span class="register-arrow" aria-hidden="true">&#8594;</span>
            </a>
            <a href="{{ route('documents.archive') }}">
                <span class="register-index">03</span>
                <span><strong>{{ __('documents.archive') }}</strong><small>{{ __('app.browse_closed') }}</small></span>
                <span class="register-arrow" aria-hidden="true">&#8594;</span>
            </a>
        </div>
    </section>
@endsection