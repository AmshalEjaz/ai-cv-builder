@extends('layouts.app')
@section('content')
<section class="section-heading page-title">
     <div>
          <p class="eyebrow">CV DETAILS</p>
          <h1>{{ $cv->title }}</h1>
          <p class="muted">{{ $cv->original_filename }}</p>
    </div>
    <div class="action-group">
         <a class="button button-secondary" href="{{ route('cvs.edit', $cv) }}">Edit Name</a>
         <a class="button" href="{{ route('cvs.download', $cv) }}">Download CV <span>↓</span></a>
    </div>
</section>
<div class="panel cv-summary"><span class="status {{ $cv->status }}">{{ ucfirst($cv->status) }}</span>
    <h2>{{ data_get($cv->ai_enhanced_data, 'name', 'Your resume') }}</h2>
    <p>{{ data_get($cv->ai_enhanced_data, 'summary', 'Your parsed CV content will appear here.') }}</p>
    <p class="muted">Your CV is ready. Download the formatted PDF or edit the details and template.</p>
</div>
@endsection