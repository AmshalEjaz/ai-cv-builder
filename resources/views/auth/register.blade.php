@extends('layouts.guest')
@section('content')
<p class="eyebrow">GET STARTED</p><h1>Your next opportunity starts here.</h1><p class="muted">Create a free account and turn your experience into a polished CV.</p>
<form method="POST" action="{{ route('register.store') }}" class="form-stack">@csrf
    <label>Full name<input type="text" name="name" value="{{ old('name') }}" required autofocus></label>
    <label>Email address<input type="email" name="email" value="{{ old('email') }}" required></label>
    <label>Password<input type="password" name="password" required></label>
    <label>Confirm password<input type="password" name="password_confirmation" required></label>
    <button class="button" type="submit">Create account</button>
</form>
<p class="form-footer">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
