@extends('layouts.app')
@section('content')
<div class="form-page upload-page">
    <p class="eyebrow">AI-POWERED CV BUILDER</p>
    <h1>Bring your experience to life.</h1>
    <p class="muted">Upload your old CV. Ollama will extract your information, improve the wording, and place it into the template you choose.</p>
    <form method="POST" action="{{ route('cvs.store') }}" enctype="multipart/form-data" class="panel form-stack">
        @csrf
        <label>CV title
            <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Product Designer Resume" required>
        </label>
        <label>Upload your old CV
            <input type="file" name="cv_file" accept=".pdf,.docx" required>
            <small class="field-help">PDF or DOCX, maximum 10 MB</small>
        </label>
        <fieldset class="template-picker">
            <legend>Choose a design</legend>
            <div class="template-options">
                @foreach($templates as $template)
                    @php($settings = $template->settings ?? [])
                    <label class="template-option">
                        <input type="radio" name="template_id" value="{{ $template->id }}" @checked(old('template_id') == $template->id) required>
                        <span class="option-preview" style="--template-accent: {{ $settings['accent'] ?? '#167a74' }}">
                            <b>{{ strtoupper(substr($template->name, 0, 1)) }}</b>
                            <i></i><i></i><i class="short"></i>
                        </span>
                        <span class="option-copy"><strong>{{ $template->name }}</strong><small>{{ $template->description }}</small></span>
                        <span class="option-check">✓</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <button class="button" type="submit">Upload, enhance & preview <span>→</span></button>
    </form>
</div>
@endsection
