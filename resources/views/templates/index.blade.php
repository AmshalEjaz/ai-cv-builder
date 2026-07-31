@extends('layouts.app')
@section('content')
<section class="page-title"><p class="eyebrow">DESIGN YOUR STORY</p><h1>Choose a template</h1><p class="muted">Give your experience a visual edge with a clean, professional layout.</p></section><div class="template-grid">@forelse($templates as $template)<a class="template-card" href="{{ route('templates.preview', $template) }}"><div class="template-preview">{{ strtoupper(substr($template->name, 0, 1)) }}</div><h3>{{ $template->name }}</h3><p>{{ $template->description }}</p></a>@empty<div class="empty-state"><h3>No templates available yet</h3><p>Add an active template to the templates table to see it here.</p></div>@endforelse</div>
@endsection
