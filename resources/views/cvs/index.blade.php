@extends('layouts.app')
@section('content')
<section class="section-heading page-title">
     <div>
          <p class="eyebrow">MY DOCUMENTS</p>
          <h1>Your CVs</h1>
     </div>
</section>
<div class="card-list">@forelse($cvs as $cv)<a class="cv-row" href="{{ route('cvs.show', $cv) }}"><span class="file-icon">CV</span><span><strong>{{ $cv->title }}</strong><small>{{ $cv->original_filename }} · {{ $cv->created_at->format('M j, Y') }}</small></span><span class="status {{ $cv->status }}">{{ ucfirst($cv->status) }}</span><span>→</span></a>@empty<div class="empty-state">
          <h3>No CVs yet</h3>
          <p>Upload your existing resume to get started.</p><a class="button" href="{{ route('cvs.create') }}">Upload a CV</a>
     </div>@endforelse</div>
@endsection