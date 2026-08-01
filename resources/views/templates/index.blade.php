@extends('layouts.app')
@section('content')
<section class="section-heading page-title">
     <div>
          <p class="eyebrow">DESIGN YOUR STORY</p>
          <h1>Choose a template</h1>
          <p class="muted">Your uploaded CV content stays the same. Pick a visual style, then AI helps polish the wording.</p>
     </div>
     <div class="action-group">
          <a class="button button-secondary" href="{{ route('templates.manage') }}">Manage templates</a>
          <a class="button" href="{{ route('templates.create') }}">+ Add template</a>
     </div>
</section>
<div class="template-grid">@forelse($templates as $template)@php($settings = $template->settings ?? [])<a class="template-card" href="{{ route('templates.preview', $template) }}">
          <div class="template-preview" style="--template-accent: {{ $settings['accent'] ?? '#167a74' }}">
               @if($template->thumbnail)
                    <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }} preview">
               @else
                    <b>{{ strtoupper(substr($template->name, 0, 1)) }}</b><i></i><i></i><i class="short"></i>
               @endif
          </div>
          <h3>{{ $template->name }}</h3>
          <p>{{ $template->description }}</p><span class="template-link">Preview template →</span>
     </a>@empty<div class="empty-state">
          <h3>No templates available yet</h3>
          <p>Run the database seeder to add the starter templates.</p>
     </div>@endforelse</div>
@endsection