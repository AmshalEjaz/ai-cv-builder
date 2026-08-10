@extends('layouts.app')
@section('content')
<div class="form-page">
    <p class="eyebrow">TEMPLATE LIBRARY</p>
    <h1>{{ $template->exists ? 'Edit template' : 'Add a template' }}</h1>
    <p class="muted">Choose a layout and accent color. The PDF/thumbnail you upload is only shown as a preview image on the selection screen — the actual downloaded CV is always generated live from the candidate's real data in the chosen layout, so no sample/placeholder text can ever leak into a user's CV.</p>
    <form method="POST" action="{{ $template->exists ? route('templates.update', $template) : route('templates.store') }}" enctype="multipart/form-data" class="panel form-stack">
        @csrf
        @if($template->exists) @method('PUT') @endif
        <label>Template name<input type="text" name="name" value="{{ old('name', $template->name) }}" required></label>
        <label>Slug<input type="text" name="slug" value="{{ old('slug', $template->slug) }}" placeholder="modern-teal" required></label>
        <label>Description<textarea name="description" rows="3">{{ old('description', $template->description) }}</textarea></label>

        <label>Layout
            @php $currentLayout = old('layout', data_get($template->settings, 'layout', 'modern-teal')); @endphp
            <select name="layout" required>
                <option value="modern-teal" @selected($currentLayout === 'modern-teal')>Modern Teal — teal banner header, 2-column body</option>
                <option value="executive-slate" @selected($currentLayout === 'executive-slate')>Executive Slate — light header bar, dark sidebar</option>
                <option value="gray-golden" @selected($currentLayout === 'gray-golden')>Gray &amp; Golden — dark sidebar with initials avatar, timeline</option>
                <option value="centered-classic" @selected($currentLayout === 'centered-classic')>Centered Classic — bordered centered name, split columns</option>
                <option value="art-director" @selected($currentLayout === 'art-director')>Art Director — bold dark banner, timeline body</option>
            </select>
            <small class="field-help">This controls the actual structure/design used when generating a CV with this template.</small>
        </label>

        <label>Accent color<input type="text" name="accent" value="{{ old('accent', data_get($template->settings, 'accent', '#167a74')) }}" required></label>
        <label>Preview PDF <small class="field-help">Optional — shown only as a download/reference on the selection screen, never used to render a user's CV</small>
            <input type="file" name="pdf" accept=".pdf">
        </label>
        <label>Thumbnail preview
            <input type="file" name="thumbnail_file" accept=".png,.jpg,.jpeg,.webp">
            <small class="field-help">Optional image, maximum 5 MB, shown on the template picker</small>
        </label>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->exists ? $template->is_active : true))> Show this template to users</label>
        <button class="button" type="submit">{{ $template->exists ? 'Save template' : 'Add template' }}</button>
    </form>
</div>
@endsection
