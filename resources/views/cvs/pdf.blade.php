<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $cv->title }}</title>
    <style>
        @page {
            margin: 16mm;
            size: A4;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #17252b;
            background: #ffffff;
        }

        .page {
            width: 100%;
            box-sizing: border-box;
        }

        .content {
            background: #ffffff;
        }

        .template-banner {
            margin-bottom: 12px;
        }

        .template-banner img {
            display: block;
            width: 100%;
            max-height: 90px;
            object-fit: contain;
            border-radius: 6px;
        }

        .hero {
            padding-bottom: 12px;
            margin-bottom: 14px;
            border-bottom: 2px solid var(--accent);
        }

        .name {
            margin: 0 0 4px;
            font-size: 24px;
            color: var(--accent);
        }

        .muted {
            color: #6d7c82;
            margin: 2px 0;
        }

        .contact {
            margin: 4px 0 0;
            color: #425157;
        }

        .two-col {
            display: block;
            width: 100%;
        }

        .left,
        .right {
            display: block;
            width: 100%;
            padding: 0;
        }

        .section {
            margin-bottom: 14px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .section-label {
            display: block;
            margin-bottom: 6px;
            color: var(--accent);
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .item {
            margin-bottom: 8px;
        }

        .item strong {
            font-size: 11px;
        }

        .skill-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .skill-pill {
            display: inline-block;
            margin: 0 6px 6px 0;
            padding: 3px 7px;
            background: rgba(22, 122, 116, 0.10);
            border-radius: 4px;
        }
    </style>
</head>
<body style="--accent: {{ data_get($cv->template?->settings, 'accent', '#167a74') }};">
    @php
        $data = $cv->rendered_data ?? [];
    @endphp

    <div class="page">
        <div class="content">
            @if(!empty($bgBase64))
                <div class="template-banner">
                    <img src="data:image/png;base64,{{ $bgBase64 }}" />
                </div>
            @endif

            <div class="hero">
                <h1 class="name">{{ data_get($data, 'name') ?: $cv->title }}</h1>
                @if(data_get($data, 'title'))
                    <p class="muted">{{ data_get($data, 'title') }}</p>
                @endif
                @if(data_get($data, 'email') || data_get($data, 'phone'))
                    <p class="contact">
                        {{ data_get($data, 'email') }}
                        @if(data_get($data, 'email') && data_get($data, 'phone')) · @endif
                        {{ data_get($data, 'phone') }}
                    </p>
                @endif
            </div>

            <div class="two-col">
                <div class="left">
                    @if(data_get($data, 'summary'))
                        <div class="section">
                            <span class="section-label">Professional Summary</span>
                            <p>{{ data_get($data, 'summary') }}</p>
                        </div>
                    @endif

                    @if(count(data_get($data, 'skills', [])))
                        <div class="section">
                            <span class="section-label">Skills</span>
                            <ul class="skill-list">
                                @foreach(data_get($data, 'skills', []) as $skill)
                                    <li class="skill-pill">{{ $skill }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(count(data_get($data, 'education', [])))
                        <div class="section">
                            <span class="section-label">Education</span>
                            @foreach(data_get($data, 'education', []) as $education)
                                <div class="item">
                                    <strong>{{ data_get($education, 'degree') }}</strong><br>
                                    {{ data_get($education, 'institution') }}<br>
                                    <span class="muted">{{ data_get($education, 'year') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="right">
                    @if(count(data_get($data, 'experience', [])))
                        <div class="section">
                            <span class="section-label">Work Experience</span>
                            @foreach(data_get($data, 'experience', []) as $experience)
                                <div class="item">
                                    <strong>{{ data_get($experience, 'position') }}</strong> · {{ data_get($experience, 'company') }}<br>
                                    <span class="muted">{{ data_get($experience, 'start_date') }} - {{ data_get($experience, 'end_date') }}</span>
                                    <p>{{ data_get($experience, 'description') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
