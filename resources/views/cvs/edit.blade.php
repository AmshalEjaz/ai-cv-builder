@extends('layouts.app')
@section('content')
<div class="form-page">
    <p class="eyebrow">EDIT DOCUMENT</p>
    <h1>Update your CV</h1>
    @php($data = $cv->ai_enhanced_data ?? [])
    <form method="POST" action="{{ route('cvs.update', $cv) }}" class="panel form-stack" enctype="multipart/form-data">
        @csrf @method('PUT')
        <label>CV title<input type="text" name="title" value="{{ old('title', $cv->title) }}" required></label>
        <label>Full name<input type="text" name="data[name]" value="{{ old('data.name', data_get($data, 'name')) }}"></label>
        <label>Professional title<input type="text" name="data[title]" value="{{ old('data.title', data_get($data, 'title')) }}"></label>
        <label>Email<input type="email" name="data[email]" value="{{ old('data.email', data_get($data, 'email')) }}"></label>
        <label>Phone<input type="text" name="data[phone]" value="{{ old('data.phone', data_get($data, 'phone')) }}"></label>
        <label>Professional summary<textarea name="data[summary]" rows="5">{{ old('data.summary', data_get($data, 'summary')) }}</textarea></label>
        <label>Skills <small class="field-help">Separate skills with commas</small><input type="text" name="data[skills]" value="{{ old('data.skills', implode(', ', data_get($data, 'skills', []))) }}"></label>
        <label>Template<select name="template_id" required>@foreach($templates as $template)<option value="{{ $template->id }}" @selected($cv->template_id === $template->id)>{{ $template->name }}</option>@endforeach</select></label>

        <hr>
        <label>Replace CV file <small class="field-help">Optional — upload a new/updated PDF or DOCX and AI will re-parse it. The fields above (if filled) will still override whatever AI returns.</small>
            <input type="file" name="cv_file" accept=".pdf,.docx">
        </label>
        <label class="check">
            <input type="checkbox" name="reprocess" value="1">
            Re-run AI on the existing file (no new upload) — use this if you only edited fields above but still want AI to re-clean/re-enhance the rest
        </label>

        <button class="button" type="submit">Save changes</button>
    </form>
</div>
@endsection
