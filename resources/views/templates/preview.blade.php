@extends('layouts.app')
@section('content')
<div class="form-page"><a href="{{ route('templates.index') }}">← Back to templates</a>
     <div class="template-large">
          <div class="template-preview large">
               @if($template->thumbnail)
                    <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }} preview">
               @else
                    {{ strtoupper(substr($template->name, 0, 1)) }}
               @endif
          </div>
          <h1>{{ $template->name }}</h1>
          <p class="muted">{{ $template->description }}</p><a class="button" href="{{ route('cvs.create') }}">Use this template</a>
     </div>
</div>
@endsection