@extends('layouts.app')
@section('content')
<section class="section-heading page-title">
    <div><p class="eyebrow">TEMPLATE LIBRARY</p><h1>Manage templates</h1><p class="muted">Add PDF designs to the database and update their details whenever you need.</p></div>
    <a class="button" href="{{ route('templates.create') }}">+ Add template</a>
</section>
<div class="card-list">
    @forelse($templates as $template)
        <div class="cv-row">
            <span class="file-icon">PDF</span>
            <span><strong>{{ $template->name }}</strong><small>{{ $template->pdf_path ? 'PDF uploaded' : 'PDF missing' }} · {{ $template->is_active ? 'Active' : 'Inactive' }}</small></span>
            <a class="button button-secondary button-small" href="{{ route('templates.edit', $template) }}">Edit</a>
            <span>→</span>
        </div>
    @empty
        <div class="empty-state"><h3>No templates yet.</h3><p>Add your first PDF design.</p></div>
    @endforelse
</div>
@endsection
