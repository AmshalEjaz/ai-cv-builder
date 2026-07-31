@extends('layouts.app')
@section('content')
<section class="section-heading page-title"><div><p class="eyebrow">CV DETAILS</p><h1>{{ $cv->title }}</h1><p class="muted">{{ $cv->original_filename }}</p></div><a class="button button-secondary" href="{{ route('cvs.edit', $cv) }}">Edit CV</a></section><div class="panel"><span class="status {{ $cv->status }}">{{ ucfirst($cv->status) }}</span><h2>{{ data_get($cv->ai_enhanced_data, 'name', 'Your resume') }}</h2><p>{{ data_get($cv->ai_enhanced_data, 'summary', 'Your parsed CV content will appear here.') }}</p></div>
@endsection
