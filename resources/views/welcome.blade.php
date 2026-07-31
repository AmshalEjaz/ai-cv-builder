@extends('layouts.guest')
@section('content')
<div class="landing-content">
    <div class="landing-copy">
        <p class="eyebrow">THE SMARTER WAY TO APPLY</p>
        <h1>Make your experience impossible to overlook.</h1>
        <p class="muted">ResumeForge turns your story into a polished, professional CV with smart enhancement and beautiful templates.</p>
        <div class="landing-actions"><a class="button" href="{{ route('register') }}">Create my CV</a><a class="button button-secondary" href="{{ route('login') }}">Sign in</a></div>
        <p class="landing-proof"><strong>Upload · Enhance · Stand out</strong><br>One focused workspace for your next opportunity.</p>
    </div>
    <div class="landing-art">
        <div class="resume-card">
            <div class="resume-line"></div><strong>Your Name</strong><span></span><span class="short"></span><br><strong>Experience</strong><span></span><span></span><span class="short"></span>
        </div>
    </div>
</div>
@endsection