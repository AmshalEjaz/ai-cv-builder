@extends('layouts.app')
@section('content')
<section class="hero compact dashboard-hero">
     <div>
          <p class="eyebrow">YOUR WORKSPACE</p>
         <h1>Hi, {{ auth()->user()->name }}.</h1>
          <p class="muted">Create, improve, and share a CV you can be proud of.</p>
     </div><a class="button add-cv hero-action" href="{{ route('cvs.create') }}">+ Upload a CV</a>
</section>
<section class="stats">
     <div class="stat-card"><span class="stat-label"><i>▣</i> CVs created</span><strong>{{ $cvCount }}</strong><small>Keep building your story</small></div>
     <div class="stat-card"><span class="stat-label"><i>✦</i> CV processing</span><strong>Ready</strong><small>Local processing is enabled</small></div>
     <div class="stat-card"><span class="stat-label"><i>◈</i> Templates</span><strong>{{ \App\Models\Template::where('is_active', true)->count() }}</strong><small>Styles ready to explore</small></div>
</section>
<section class="section-heading">
     <div>
          <p class="eyebrow">YOUR DESIGN LIBRARY</p>
          <h2>Choose your CV style</h2>
     </div><a href="{{ route('templates.index') }}">View all →</a>
</section>
<section class="dashboard-templates">
     @forelse($templates as $template)
          @php($settings = $template->settings ?? [])
          <a class="dashboard-template-card" href="{{ route('templates.preview', $template) }}">
               <div class="dashboard-template-image" style="--template-accent: {{ $settings['accent'] ?? '#167a74' }}">
                    @if($template->thumbnail)
                         <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }} preview">
                    @else
                         <span>{{ strtoupper(substr($template->name, 0, 1)) }}</span>
                    @endif
               </div>
               <div class="dashboard-template-copy">
                    <strong>{{ $template->name }}</strong>
                    <span>{{ $template->description }}</span>
               </div>
          </a>
     @empty
          <div class="empty-state"><h3>No templates available yet.</h3><p>Run the template seeder to add your designs.</p></div>
     @endforelse
</section>
<section class="section-heading">
     <div>
          <p class="eyebrow">RECENT WORK</p>
          <h2>Your CVs</h2>
     </div><a href="{{ route('cvs.index') }}">View all →</a>
</section>
<div class="card-list">@forelse($recentCvs as $cv)<a class="cv-row" href="{{ route('cvs.show', $cv) }}"><span class="file-icon">CV</span><span><strong>{{ $cv->title }}</strong><small>{{ $cv->original_filename }}</small></span><span class="status {{ $cv->status }}">{{ ucfirst($cv->status) }}</span><span>→</span></a>@empty<div class="empty-state">
          <h3>Your first great CV is one upload away.</h3>
          <p>Upload a PDF or DOCX from the button above and we will help turn it into a polished resume.</p>
     </div>@endforelse</div>
@endsection