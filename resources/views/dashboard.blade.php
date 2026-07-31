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
     <div class="stat-card"><span class="stat-label"><i>✦</i> AI enhancement</span><strong>Ready</strong><small>Smart wording is available</small></div>
     <div class="stat-card"><span class="stat-label"><i>◈</i> Templates</span><strong>{{ \App\Models\Template::where('is_active', true)->count() }}</strong><small>Styles ready to explore</small></div>
</section>
<section class="dashboard-showcase">
     <article class="showcase-card showcase-ai">
          <div class="showcase-art"><span>✦</span><i></i><i></i><i></i></div>
          <div><p class="eyebrow">SMART ENHANCEMENT</p><h3>Let your experience speak clearly.</h3><p class="muted">Turn simple responsibilities into confident, achievement-focused wording.</p></div>
     </article>
     <article class="showcase-card showcase-design">
          <div class="showcase-art"><span>◈</span><i></i><i></i><i></i></div>
          <div><p class="eyebrow">BEAUTIFUL DESIGNS</p><h3>Find a style that feels like you.</h3><p class="muted">Choose from polished templates built for modern applications.</p></div>
     </article>
     <article class="showcase-card showcase-ready">
          <div class="showcase-art"><span>✓</span><i></i><i></i><i></i></div>
          <div><p class="eyebrow">READY TO SHARE</p><h3>One focused workspace.</h3><p class="muted">Keep every version of your CV organized and ready for the next opportunity.</p></div>
     </article>
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