@extends('layouts.app')
@section('content')
<div class="form-page"><p class="eyebrow">EDIT DOCUMENT</p><h1>Update your CV</h1><form method="POST" action="{{ route('cvs.update', $cv) }}" class="panel form-stack">@csrf @method('PUT')<label>CV title<input type="text" name="title" value="{{ old('title', $cv->title) }}" required></label><label>Template<select name="template_id" required>@foreach($templates as $template)<option value="{{ $template->id }}" @selected($cv->template_id === $template->id)>{{ $template->name }}</option>@endforeach</select></label><button class="button" type="submit">Save changes</button></form></div>
@endsection
