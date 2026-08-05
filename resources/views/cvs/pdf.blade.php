<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $cv->title }}</title>
    <style>
        @page { margin: 34px; }
        body { color: #17252b; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        h1 { margin: 0; color: {{ data_get($cv->template?->settings, 'accent', '#167a74') }}; font-size: 28px; }
        h2 { margin: 22px 0 7px; padding-bottom: 4px; color: {{ data_get($cv->template?->settings, 'accent', '#167a74') }}; border-bottom: 1px solid #dbe5e1; font-size: 14px; }
        p { margin: 4px 0; }
        .muted { color: #6d7c82; }
        .contact { margin: 5px 0 18px; }
        .item { margin-bottom: 10px; }
        .item strong { font-size: 12px; }
        ul { margin: 5px 0; padding-left: 18px; }
    </style>
</head>
<body>
    @php
        $data = $cv->rendered_data ?? [];
    @endphp

    {{-- If a precomputed background was provided, embed it as a full-page background --}}
    @if(!empty($bgBase64))
        <div style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:-1;">
            <img src="data:image/png;base64,{{ $bgBase64 }}" style="width:100%; height:100%; object-fit:cover;" />
        </div>
    @endif

    <h1>{{ data_get($data, 'name') ?: $cv->title }}</h1>
    <p class="muted">{{ data_get($data, 'title') }}</p>
    <p class="contact">{{ data_get($data, 'email') }} @if(data_get($data, 'email') && data_get($data, 'phone')) · @endif {{ data_get($data, 'phone') }}</p>

    @if(data_get($data, 'summary'))
        <h2>Professional Summary</h2>
        <p>{{ data_get($data, 'summary') }}</p>
    @endif

    @if(count(data_get($data, 'skills', [])))
        <h2>Skills</h2>
        <p>{{ implode(' · ', data_get($data, 'skills', [])) }}</p>
    @endif

    @if(count(data_get($data, 'experience', [])))
        <h2>Experience</h2>
        @foreach(data_get($data, 'experience', []) as $experience)
            <div class="item">
                <strong>{{ data_get($experience, 'position') }}</strong> · {{ data_get($experience, 'company') }}
                <p class="muted">{{ data_get($experience, 'start_date') }} - {{ data_get($experience, 'end_date') }}</p>
                <p>{{ data_get($experience, 'description') }}</p>
            </div>
        @endforeach
    @endif

    @if(count(data_get($data, 'education', [])))
        <h2>Education</h2>
        @foreach(data_get($data, 'education', []) as $education)
            <div class="item"><strong>{{ data_get($education, 'degree') }}</strong> · {{ data_get($education, 'institution') }} <span class="muted">{{ data_get($education, 'year') }}</span></div>
        @endforeach
    @endif
</body>
</html>
