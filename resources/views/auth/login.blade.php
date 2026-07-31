@extends('layouts.guest')
@section('content')
<p class="eyebrow">WELCOME BACK</p><h1>Build a CV that opens doors.</h1><p class="muted">Sign in to continue creating your standout resume.</p>
<form method="POST" action="{{ route('login.store') }}" class="form-stack">@csrf
    <label>Email address<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
    <label>Password<input type="password" name="password" required></label>
    <label class="check"><input type="checkbox" name="remember"> Remember me</label>
    <button class="button" type="submit">Sign in</button>
</form>
<p class="form-footer">New here? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
