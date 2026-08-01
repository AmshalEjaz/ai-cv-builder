@extends('layouts.app')
@section('content')
<div class="form-page">
    <p class="eyebrow">TEMPLATE LIBRARY</p>
    <h1>{{ $template->exists ? 'Edit template' : 'Add a template' }}</h1>
    <p class="muted">Upload the original PDF design. A thumbnail is optional and is only used on selection screens.</p>
    <form method="POST" action="{{ $template->exists ? route('templates.update', $template) : route('templates.store') }}" enctype="multipart/form-data" class="panel form-stack">
        @csrf
        @if($template->exists) @method('PUT') @endif
        <label>Template name<input type="text" name="name" value="{{ old('name', $template->name) }}" required></label>
        <label>Slug<input type="text" name="slug" value="{{ old('slug', $template->slug) }}" placeholder="modern-teal" required></label>
        <label>Description<textarea name="description" rows="3">{{ old('description', $template->description) }}</textarea></label>
        <label>Accent color<input type="text" name="accent" value="{{ old('accent', data_get($template->settings, 'accent', '#167a74')) }}" required></label>
        <label>PDF design
            <input type="file" name="pdf" accept=".pdf" {{ $template->exists && $template->pdf_path ? '' : 'required' }}>
            <small class="field-help">PDF only, maximum 10 MB{{ $template->pdf_path ? ' · Leave empty to keep the current PDF' : '' }}</small>
        </label>
        <label>Thumbnail preview
            <input type="file" name="thumbnail_file" accept=".png,.jpg,.jpeg,.webp">
            <small class="field-help">Optional image, maximum 5 MB</small>
        </label>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->exists ? $template->is_active : true))> Show this template to users</label>
        <button class="button" type="submit">{{ $template->exists ? 'Save template' : 'Add template' }}</button>
    </form>
</div>
@endsection
