@php
    $data = $cv->rendered_data ?? [];
    $accent = data_get($cv->template?->settings, 'accent', '#167a74');

    $name = data_get($data, 'name') ?: $cv->title;
    $title = data_get($data, 'title');
    $email = data_get($data, 'email');
    $phone = data_get($data, 'phone');
    $skills = data_get($data, 'skills', []);
    $education = data_get($data, 'education', []);
    $summary = data_get($data, 'summary');
    $experience = data_get($data, 'experience', []);
    $languages = data_get($data, 'languages', []);

    $initials = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
        if ($part !== '') { $initials .= mb_strtoupper(mb_substr($part, 0, 1)); }
    }

    // The admin picks the actual layout to use for a template via the Template
    // CRUD (settings->layout). That is the source of truth. For any template
    // saved before that field existed, fall back to guessing from the slug so
    // nothing breaks, and finally default to "modern-teal".
    $validLayouts = ['modern-teal', 'executive-slate', 'gray-golden', 'centered-classic', 'art-director'];
    $layout = data_get($cv->template?->settings, 'layout');

    if (! in_array($layout, $validLayouts, true)) {
        $slug = mb_strtolower($cv->template?->slug ?? '');
        if (str_contains($slug, 'teal')) {
            $layout = 'modern-teal';
        } elseif (str_contains($slug, 'slate') || str_contains($slug, 'executive')) {
            $layout = 'executive-slate';
        } elseif (str_contains($slug, 'gray') || str_contains($slug, 'grey') || str_contains($slug, 'gold')) {
            $layout = 'gray-golden';
        } elseif (str_contains($slug, 'black') || str_contains($slug, 'white') || str_contains($slug, 'simple')) {
            $layout = 'centered-classic';
        } elseif (str_contains($slug, 'modern')) {
            $layout = 'art-director';
        } else {
            $layout = 'modern-teal';
        }
    }
@endphp
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $cv->title }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #262626; font-family: DejaVu Sans, sans-serif; font-size: 10.5px; line-height: 1.55; }
        .clearfix::after { content: ""; display: block; clear: both; }
        p { margin: 0 0 10px; }

        /* ============================================================
           MODERN-TEAL — teal banner header, summary, 2-col body
        ============================================================ */
        .modern-teal .banner { background: {{ $accent }}; padding: 30px 40px 24px; }
        .modern-teal .banner h1 { margin: 0 0 4px; font-size: 27px; color: #ffffff; letter-spacing: 0.02em; }
        .modern-teal .banner .role { font-size: 12px; color: rgba(255,255,255,0.85); }
        .modern-teal .accent-strip { height: 8px; background: {{ $accent }}; opacity: 0.55; }
        .modern-teal .body { padding: 26px 40px 34px; }
        .modern-teal .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: {{ $accent }}; margin: 0 0 10px; }
        .modern-teal .section-title.spaced { margin-top: 22px; }
        .modern-teal .col-left { float: left; width: 34%; }
        .modern-teal .col-right { float: left; width: 66%; padding-left: 24px; }
        .modern-teal .edu-item, .modern-teal .exp-item { margin-bottom: 13px; }
        .modern-teal .edu-item .degree, .modern-teal .exp-item .position { font-weight: bold; font-size: 11px; color: #1f1f1f; }
        .modern-teal .edu-item .meta, .modern-teal .exp-item .meta { font-size: 9.5px; color: {{ $accent }}; margin-bottom: 4px; }
        .modern-teal .skill-pill { display: inline-block; background: #eef4f3; color: #1f1f1f; padding: 3px 9px; border-radius: 3px; font-size: 9px; margin: 0 5px 5px 0; }
        .modern-teal .lang-line { font-size: 10px; margin-bottom: 4px; }
        .modern-teal .contact-row { font-size: 10px; color: {{ $accent }}; margin-top: 22px; }
        .modern-teal .contact-row span { margin-right: 22px; }

        /* ============================================================
           EXECUTIVE-SLATE — light header bar, dark sidebar + white main
        ============================================================ */
        .executive-slate .header { background: #f2f2f2; padding: 26px 36px; }
        .executive-slate .header h1 { margin: 0 0 3px; font-size: 24px; color: #1f1f1f; }
        .executive-slate .header .role { font-size: 12px; font-style: italic; color: #555555; }
        .executive-slate .header .contact-row { font-size: 9.5px; color: #555555; margin-top: 8px; }
        .executive-slate .sidebar { float: left; width: 36%; background: #2c2c2c; color: #f2f2f2; min-height: 700px; }
        .executive-slate .sidebar-inner { padding: 24px 20px; }
        .executive-slate .sidebar .section-title { font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.12em; color: #ffffff; margin: 0 0 10px; }
        .executive-slate .sidebar .section-title.spaced { margin-top: 22px; }
        .executive-slate .sidebar .item-line { font-size: 9.5px; color: #dcdcdc; padding: 3px 0; border-bottom: 1px dotted #4a4a4a; }
        .executive-slate .sidebar .edu-item { margin-bottom: 10px; }
        .executive-slate .sidebar .edu-item .degree { font-weight: bold; font-size: 9.5px; color: #ffffff; }
        .executive-slate .sidebar .edu-item .meta { font-size: 8.5px; color: #b8b8b8; }
        .executive-slate .main { float: left; width: 64%; }
        .executive-slate .main-inner { padding: 24px 26px; }
        .executive-slate .main .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.12em; color: #1f1f1f; margin: 0 0 10px; }
        .executive-slate .main .section-title.spaced { margin-top: 22px; }
        .executive-slate .exp-item { margin-bottom: 14px; }
        .executive-slate .exp-item .position { font-weight: bold; font-size: 11px; color: #1f1f1f; }
        .executive-slate .exp-item .meta { font-size: 9.5px; color: #767676; margin-bottom: 4px; }

        /* ============================================================
           GRAY-GOLDEN — dark sidebar w/ initials circle, timeline main
        ============================================================ */
        .gray-golden .sidebar { float: left; width: 30%; background: #333333; color: #f2f2f2; min-height: 780px; }
        .gray-golden .sidebar-inner { padding: 26px 16px; }
        .gray-golden .avatar { width: 90px; height: 90px; border: 2px solid #cda45e; border-radius: 50%; margin: 0 auto 24px; text-align: center; line-height: 86px; font-size: 26px; color: #cda45e; font-weight: bold; }
        .gray-golden .sidebar .section-title { font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; color: #cda45e; margin: 18px 0 9px; }
        .gray-golden .sidebar .section-title:first-of-type { margin-top: 0; }
        .gray-golden .sidebar .contact-row { font-size: 9px; color: #e2e2e2; margin-bottom: 6px; }
        .gray-golden .sidebar .edu-item { margin-bottom: 10px; }
        .gray-golden .sidebar .edu-item .degree { font-weight: bold; font-size: 9.5px; color: #ffffff; }
        .gray-golden .sidebar .edu-item .meta { font-size: 8.5px; color: #c9c9c9; }
        .gray-golden .sidebar .skill-line, .gray-golden .sidebar .lang-line { font-size: 9px; color: #e2e2e2; margin-bottom: 4px; }
        .gray-golden .main { float: left; width: 70%; }
        .gray-golden .main-inner { padding: 30px 28px; }
        .gray-golden .main h1 { margin: 0 0 3px; font-size: 24px; color: #1f1f1f; }
        .gray-golden .main .role { font-size: 11px; color: #767676; margin-bottom: 16px; }
        .gray-golden .main .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #333333; border-bottom: 1px solid #cda45e; padding-bottom: 4px; margin: 18px 0 10px; }
        .gray-golden .main .section-title.first { margin-top: 0; }
        .gray-golden .timeline-item { padding-left: 14px; border-left: 2px solid #cda45e; margin-bottom: 14px; }
        .gray-golden .timeline-item .position { font-weight: bold; font-size: 11px; text-transform: uppercase; color: #1f1f1f; }
        .gray-golden .timeline-item .meta { font-size: 9.5px; color: #767676; margin-bottom: 4px; }

        /* ============================================================
           CENTERED-CLASSIC — bordered centered name, vertical divider
        ============================================================ */
        .centered-classic .name-box { border: 2px solid #f0ad4e; padding: 16px 30px; text-align: center; margin: 30px auto 0; width: 60%; }
        .centered-classic .name-box h1 { margin: 0; font-size: 26px; font-weight: normal; color: #1f1f1f; }
        .centered-classic .subtitle-band { background: #efefef; text-align: center; padding: 14px; margin: 18px 0 0; font-size: 13px; color: #444444; }
        .centered-classic .body { padding: 26px 36px 34px; }
        .centered-classic .col-left { float: left; width: 30%; border-right: 1px solid #f0ad4e; padding-right: 18px; }
        .centered-classic .col-right { float: left; width: 68%; padding-left: 24px; }
        .centered-classic .section-title { font-size: 12.5px; font-weight: bold; text-transform: uppercase; color: #1f1f1f; margin: 18px 0 9px; }
        .centered-classic .section-title.first { margin-top: 0; }
        .centered-classic .contact-row { font-size: 9.5px; color: #444444; margin-bottom: 6px; }
        .centered-classic .edu-item { margin-bottom: 10px; }
        .centered-classic .edu-item .degree { font-weight: bold; font-size: 10px; color: #1f1f1f; }
        .centered-classic .edu-item .meta { font-size: 9px; color: #767676; }
        .centered-classic .skill-line, .centered-classic .lang-line { font-size: 9.5px; color: #333333; margin-bottom: 4px; }
        .centered-classic .exp-item { margin-bottom: 14px; }
        .centered-classic .exp-item .position { font-weight: bold; font-size: 11px; color: #1f1f1f; }
        .centered-classic .exp-item .meta { font-size: 9.5px; color: #767676; margin-bottom: 4px; }

        /* ============================================================
           ART-DIRECTOR — dark+accent banner, 2-col body w/ timeline
        ============================================================ */
        .art-director .banner { background: #2c2c2c; padding: 30px 40px 22px; }
        .art-director .banner h1 { margin: 0 0 4px; font-size: 30px; color: #ffffff; }
        .art-director .banner h1 .accent { color: {{ $accent }}; font-weight: bold; }
        .art-director .banner .role { font-size: 12px; color: #cfcfcf; letter-spacing: 0.06em; }
        .art-director .banner .contact-row { font-size: 9px; color: #cfcfcf; margin-top: 10px; }
        .art-director .accent-strip { height: 7px; background: {{ $accent }}; }
        .art-director .body { padding: 26px 40px 34px; }
        .art-director .col-left { float: left; width: 34%; }
        .art-director .col-right { float: left; width: 66%; padding-left: 24px; }
        .art-director .section-title { font-size: 12.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #1f1f1f; margin: 0 0 10px; }
        .art-director .section-title.spaced { margin-top: 20px; }
        .art-director .edu-item { margin-bottom: 12px; }
        .art-director .edu-item .degree { font-weight: bold; font-size: 10px; color: #1f1f1f; }
        .art-director .edu-item .meta { font-size: 9px; color: #767676; }
        .art-director .skill-line, .art-director .lang-line { font-size: 9.5px; color: #333333; margin-bottom: 4px; }
        .art-director .timeline-item { padding-left: 14px; border-left: 2px solid {{ $accent }}; margin-bottom: 14px; }
        .art-director .timeline-item .position { font-weight: bold; font-size: 11px; color: #1f1f1f; }
        .art-director .timeline-item .meta { font-size: 9.5px; color: #767676; margin-bottom: 4px; }
    </style>
</head>

<body class="{{ $layout }}">

    {{-- ================= MODERN-TEAL ================= --}}
    @if($layout === 'modern-teal')
        <div class="modern-teal">
            <div class="banner">
                <h1>{{ $name }}</h1>
                @if($title)<p class="role">{{ $title }}</p>@endif
            </div>
            <div class="accent-strip"></div>
            <div class="body">
                @if($summary)
                    <div class="section-title first">Summary</div>
                    <p>{{ $summary }}</p>
                @endif

                <div class="clearfix">
                    <div class="col-left">
                        @if(count($education))
                            <div class="section-title spaced">Education</div>
                            @foreach($education as $edu)
                                <div class="edu-item">
                                    <div class="degree">{{ data_get($edu, 'degree') }}</div>
                                    <div class="meta">{{ data_get($edu, 'institution') }} · {{ data_get($edu, 'year') }}</div>
                                </div>
                            @endforeach
                        @endif

                        @if(count($skills))
                            <div class="section-title spaced">Skills</div>
                            <div>
                                @foreach($skills as $skill)<span class="skill-pill">{{ $skill }}</span>@endforeach
                            </div>
                        @endif

                        @if(count($languages))
                            <div class="section-title spaced">Languages</div>
                            @foreach($languages as $lang)
                                <div class="lang-line">{{ data_get($lang, 'language') }}@if(data_get($lang, 'proficiency')) ({{ data_get($lang, 'proficiency') }})@endif</div>
                            @endforeach
                        @endif
                    </div>

                    <div class="col-right">
                        @if(count($experience))
                            <div class="section-title spaced">Work Experience</div>
                            @foreach($experience as $exp)
                                <div class="exp-item">
                                    <div class="position">{{ data_get($exp, 'position') }}, {{ data_get($exp, 'company') }}</div>
                                    <div class="meta">{{ data_get($exp, 'start_date') }} - {{ data_get($exp, 'end_date') }}</div>
                                    @if(data_get($exp, 'description'))<p>{{ data_get($exp, 'description') }}</p>@endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                @if($email || $phone)
                    <div class="contact-row">
                        @if($phone)<span>{{ $phone }}</span>@endif
                        @if($email)<span>{{ $email }}</span>@endif
                    </div>
                @endif
            </div>
        </div>

    {{-- ================= EXECUTIVE-SLATE ================= --}}
    @elseif($layout === 'executive-slate')
        <div class="executive-slate">
            <div class="header clearfix">
                <h1>{{ $name }}</h1>
                @if($title)<p class="role">{{ $title }}</p>@endif
                @if($email || $phone)
                    <p class="contact-row">{{ $phone }}{{ $phone && $email ? '  ·  ' : '' }}{{ $email }}</p>
                @endif
            </div>
            <div class="clearfix">
                <div class="sidebar">
                  <div class="sidebar-inner">
                    @if(count($skills))
                        <div class="section-title">Skills</div>
                        @foreach($skills as $skill)<div class="item-line">{{ $skill }}</div>@endforeach
                    @endif

                    @if(count($education))
                        <div class="section-title spaced">Education</div>
                        @foreach($education as $edu)
                            <div class="edu-item">
                                <div class="degree">{{ data_get($edu, 'degree') }}</div>
                                <div class="meta">{{ data_get($edu, 'institution') }}</div>
                                <div class="meta">{{ data_get($edu, 'year') }}</div>
                            </div>
                        @endforeach
                    @endif

                    @if(count($languages))
                        <div class="section-title spaced">Languages</div>
                        @foreach($languages as $lang)
                            <div class="item-line">{{ data_get($lang, 'language') }}@if(data_get($lang, 'proficiency')) ({{ data_get($lang, 'proficiency') }})@endif</div>
                        @endforeach
                    @endif
                  </div>
                </div>

                <div class="main">
                  <div class="main-inner">
                    @if($summary)
                        <div class="section-title">Professional Summary</div>
                        <p>{{ $summary }}</p>
                    @endif

                    @if(count($experience))
                        <div class="section-title spaced">Work Experience</div>
                        @foreach($experience as $exp)
                            <div class="exp-item">
                                <div class="position">{{ data_get($exp, 'position') }} · {{ data_get($exp, 'start_date') }} to {{ data_get($exp, 'end_date') }}</div>
                                <div class="meta">{{ data_get($exp, 'company') }}</div>
                                @if(data_get($exp, 'description'))<p>{{ data_get($exp, 'description') }}</p>@endif
                            </div>
                        @endforeach
                    @endif
                  </div>
                </div>
            </div>
        </div>

    {{-- ================= GRAY-GOLDEN ================= --}}
    @elseif($layout === 'gray-golden')
        <div class="gray-golden clearfix">
            <div class="sidebar">
              <div class="sidebar-inner">
                <div class="avatar">{{ $initials }}</div>

                @if($email || $phone)
                    <div class="section-title">Contact</div>
                    @if($phone)<div class="contact-row">{{ $phone }}</div>@endif
                    @if($email)<div class="contact-row">{{ $email }}</div>@endif
                @endif

                @if(count($education))
                    <div class="section-title">Education</div>
                    @foreach($education as $edu)
                        <div class="edu-item">
                            <div class="degree">{{ data_get($edu, 'degree') }}</div>
                            <div class="meta">{{ data_get($edu, 'institution') }}</div>
                            <div class="meta">{{ data_get($edu, 'year') }}</div>
                        </div>
                    @endforeach
                @endif

                @if(count($skills))
                    <div class="section-title">Skills</div>
                    @foreach($skills as $skill)<div class="skill-line">{{ $skill }}</div>@endforeach
                @endif

                @if(count($languages))
                    <div class="section-title">Languages</div>
                    @foreach($languages as $lang)
                        <div class="lang-line">{{ data_get($lang, 'language') }}@if(data_get($lang, 'proficiency')) ({{ data_get($lang, 'proficiency') }})@endif</div>
                    @endforeach
                @endif
              </div>
            </div>

            <div class="main">
              <div class="main-inner">
                <h1>{{ $name }}</h1>
                @if($title)<p class="role">{{ $title }}</p>@endif

                @if($summary)
                    <div class="section-title first">Summary</div>
                    <p>{{ $summary }}</p>
                @endif

                @if(count($experience))
                    <div class="section-title">Work Experience</div>
                    @foreach($experience as $exp)
                        <div class="timeline-item">
                            <div class="meta">({{ data_get($exp, 'start_date') }} - {{ data_get($exp, 'end_date') }})</div>
                            <div class="position">{{ data_get($exp, 'position') }}</div>
                            <div class="meta">{{ data_get($exp, 'company') }}</div>
                            @if(data_get($exp, 'description'))<p>{{ data_get($exp, 'description') }}</p>@endif
                        </div>
                    @endforeach
                @endif
              </div>
            </div>
        </div>

    {{-- ================= CENTERED-CLASSIC ================= --}}
    @elseif($layout === 'centered-classic')
        <div class="centered-classic">
            <div class="name-box"><h1>{{ $name }}</h1></div>
            @if($title)<div class="subtitle-band">{{ $title }}</div>@endif

            <div class="body clearfix">
                <div class="col-left">
                    @if($email || $phone)
                        <div class="section-title first">Contact</div>
                        @if($phone)<div class="contact-row">{{ $phone }}</div>@endif
                        @if($email)<div class="contact-row">{{ $email }}</div>@endif
                    @endif

                    @if(count($education))
                        <div class="section-title">Education</div>
                        @foreach($education as $edu)
                            <div class="edu-item">
                                <div class="degree">{{ data_get($edu, 'degree') }}</div>
                                <div class="meta">{{ data_get($edu, 'institution') }} · {{ data_get($edu, 'year') }}</div>
                            </div>
                        @endforeach
                    @endif

                    @if(count($skills))
                        <div class="section-title">Skills</div>
                        @foreach($skills as $skill)<div class="skill-line">{{ $skill }}</div>@endforeach
                    @endif

                    @if(count($languages))
                        <div class="section-title">Language</div>
                        @foreach($languages as $lang)
                            <div class="lang-line">{{ data_get($lang, 'language') }}@if(data_get($lang, 'proficiency')) ({{ data_get($lang, 'proficiency') }})@endif</div>
                        @endforeach
                    @endif
                </div>

                <div class="col-right">
                    @if($summary)
                        <div class="section-title first">Summary</div>
                        <p>{{ $summary }}</p>
                    @endif

                    @if(count($experience))
                        <div class="section-title">Experience</div>
                        @foreach($experience as $exp)
                            <div class="exp-item">
                                <div class="position">{{ data_get($exp, 'position') }}</div>
                                <div class="meta">{{ data_get($exp, 'company') }} · {{ data_get($exp, 'start_date') }} - {{ data_get($exp, 'end_date') }}</div>
                                @if(data_get($exp, 'description'))<p>{{ data_get($exp, 'description') }}</p>@endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

    {{-- ================= ART-DIRECTOR (default/"modern") ================= --}}
    @else
        <div class="art-director">
            <div class="banner">
                <h1>{{ $name }}</h1>
                @if($title)<p class="role">{{ $title }}</p>@endif
                @if($email || $phone)
                    <p class="contact-row">{{ $phone }}{{ $phone && $email ? '  ·  ' : '' }}{{ $email }}</p>
                @endif
            </div>
            <div class="accent-strip"></div>
            <div class="body clearfix">
                <div class="col-left">
                    @if(count($education))
                        <div class="section-title">Education</div>
                        @foreach($education as $edu)
                            <div class="edu-item">
                                <div class="degree">{{ data_get($edu, 'degree') }}</div>
                                <div class="meta">{{ data_get($edu, 'institution') }}</div>
                                <div class="meta">{{ data_get($edu, 'year') }}</div>
                            </div>
                        @endforeach
                    @endif

                    @if(count($skills))
                        <div class="section-title spaced">Skills</div>
                        @foreach($skills as $skill)<div class="skill-line">{{ $skill }}</div>@endforeach
                    @endif

                    @if(count($languages))
                        <div class="section-title spaced">Languages</div>
                        @foreach($languages as $lang)
                            <div class="lang-line">{{ data_get($lang, 'language') }}@if(data_get($lang, 'proficiency')) ({{ data_get($lang, 'proficiency') }})@endif</div>
                        @endforeach
                    @endif
                </div>

                <div class="col-right">
                    @if($summary)
                        <div class="section-title">Profile Info</div>
                        <p>{{ $summary }}</p>
                    @endif

                    @if(count($experience))
                        <div class="section-title spaced">Experience</div>
                        @foreach($experience as $exp)
                            <div class="timeline-item">
                                <div class="position">{{ data_get($exp, 'position') }}</div>
                                <div class="meta">{{ data_get($exp, 'company') }} · {{ data_get($exp, 'start_date') }} - {{ data_get($exp, 'end_date') }}</div>
                                @if(data_get($exp, 'description'))<p>{{ data_get($exp, 'description') }}</p>@endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endif
</body>

</html>
