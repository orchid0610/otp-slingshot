@extends('layout')
@section('title', 'Sling your code')
@section('content')
<div class="wrap narrow">
    <div class="card">
        <h1>Sling your code</h1>
        <p class="muted">We'll give you a 6-digit code. Sling it in digit by digit with the fewest mistakes to climb the leaderboard.</p>

        @if(session('error')) <div class="flash error">{{ session('error') }}</div> @endif
        @if($errors->any()) <div class="flash error">{{ $errors->first() }}</div> @endif

        <form method="POST" action="{{ route('otp.send') }}">
            @csrf
            <label for="email">Your email <span class="muted">(shown masked on the leaderboard)</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
            <button class="full" type="submit">Get my code</button>
        </form>
    </div>

    <div class="card lb">
        <h2>🏆 Leaderboard</h2>
        <ol>@include('partials.leaderboard')</ol>
    </div>
</div>
@endsection
