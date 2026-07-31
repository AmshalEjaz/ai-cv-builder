@extends('layouts.app')
@section('content')
<section class="hero compact"><div><p class="eyebrow">YOUR WORKSPACE</p><h1>Hello, {{ auth()->user()->name }}.</h1><p class="muted">Create, improve, and share a CV you can be proud of.</p></div><a class="button" href="{{ route('cvs.create') }}">+ Upload a CV</a></section>
<section class="stats"><div class="stat-card"><span>CVs created</span><strong>{{ $cvCount }}</strong></div><div class="stat-card"><span>AI enhancement</span><strong>Ready</strong></div><div class="stat-card"><span>Templates</span><strong>{{ \App\Models\Template::where('is_active', true)->count() }}</strong></div></section>
<section class="section-heading"><div><p class="eyebrow">RECENT WORK</p><h2>Your CVs</h2></div><a href="{{ route('cvs.index') }}">View all →</a></section>
<div class="card-list">@forelse($recentCvs as $cv)<a class="cv-row" href="{{ route('cvs.show', $cv) }}"><span class="file-icon">CV</span><span><strong>{{ $cv->title }}</strong><small>{{ $cv->original_filename }}</small></span><span class="status {{ $cv->status }}">{{ ucfirst($cv->status) }}</span><span>→</span></a>@empty<div class="empty-state"><h3>Your first great CV is one upload away.</h3><p>Upload a PDF or DOCX and we will help turn it into a polished resume.</p><a class="button button-small" href="{{ route('cvs.create') }}">Upload your CV</a></div>@endforelse</div>
@endsection
