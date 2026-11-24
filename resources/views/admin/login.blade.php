@extends('layout')
@section('title', 'Admin login')
@section('content')
<div class="card">
    <h1>Admin</h1>
    @if(session('error')) <div class="flash error">{{ session('error') }}</div> @endif
    <form method="POST" action="{{ route('admin.authenticate') }}">
        @csrf
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autofocus>
        <button class="full" type="submit">Sign in</button>
    </form>
</div>
@endsection
