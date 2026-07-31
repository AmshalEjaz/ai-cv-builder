@extends('layouts.guest')
@section('content')
<p class="eyebrow">THE SMARTER WAY TO APPLY</p>
<h1>Make your experience impossible to overlook.</h1>
<p class="muted">ResumeForge helps you turn your story into a polished, professional CV with smart enhancement and beautiful templates.</p>
<div style="display:flex;gap:12px;margin-top:28px"><a class="button" href="{{ route('register') }}">Create my CV</a><a class="button button-secondary" href="{{ route('login') }}">Sign in</a></div>
<div style="margin-top:40px;padding-top:22px;border-top:1px solid var(--line);color:var(--muted);font-size:.9rem"><strong style="color:var(--ink)">Upload · Enhance · Stand out</strong><br>One focused workspace for your next opportunity.</div>
@endsection
