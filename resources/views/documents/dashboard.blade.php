@extends('layouts.documents')

@section('title', 'Dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <p class="section-label">Records workspace</p>
            <h1>{{ $organization?->name ?? 'Document Management' }}</h1>
            <p class="page-intro">Manage official correspondence and closed documents.</p>
        </div>
    </div>

    @unless ($organization)
        <section class="notice-panel" role="status">
            <span class="notice-mark" aria-hidden="true">!</span>
            <div>
                <h2>Organization assignment required</h2>
                <p>Your account is signed in, but it is not yet an active member of an organization. Ask an administrator to add your account before registering or viewing documents.</p>
            </div>
        </section>
    @endunless

    <section class="summary-grid" aria-label="Document totals">
        <a class="summary-item" href="{{ route('documents.incoming.index') }}">
            <span class="summary-label">Incoming</span>
            <strong>{{ number_format($incomingCount) }}</strong>
            <span class="summary-link">View register <span aria-hidden="true">&#8594;</span></span>
        </a>
        <a class="summary-item" href="{{ route('documents.outgoing.index') }}">
            <span class="summary-label">Outgoing</span>
            <strong>{{ number_format($outgoingCount) }}</strong>
            <span class="summary-link">View register <span aria-hidden="true">&#8594;</span></span>
        </a>
        <a class="summary-item" href="{{ route('documents.archive') }}">
            <span class="summary-label">Closed documents</span>
            <strong>{{ number_format($archiveCount) }}</strong>
            <span class="summary-link">Browse archive <span aria-hidden="true">&#8594;</span></span>
        </a>
    </section>

    <section class="workspace-links" aria-labelledby="registers-heading">
        <div class="section-heading">
            <div>
                <p class="section-label">Registers</p>
                <h2 id="registers-heading">Correspondence</h2>
            </div>
        </div>
        <div class="register-links">
            <a href="{{ route('documents.incoming.index') }}">
                <span class="register-index">01</span>
                <span><strong>Incoming documents</strong><small>Register and track correspondence received</small></span>
                <span class="register-arrow" aria-hidden="true">&#8594;</span>
            </a>
            <a href="{{ route('documents.outgoing.index') }}">
                <span class="register-index">02</span>
                <span><strong>Outgoing documents</strong><small>Prepare and track correspondence sent</small></span>
                <span class="register-arrow" aria-hidden="true">&#8594;</span>
            </a>
            <a href="{{ route('documents.archive') }}">
                <span class="register-index">03</span>
                <span><strong>Archive</strong><small>Browse closed correspondence</small></span>
                <span class="register-arrow" aria-hidden="true">&#8594;</span>
            </a>
        </div>
    </section>
@endsection