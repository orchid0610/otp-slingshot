@extends('layout')
@section('title', 'Admin')
@section('content')
<div class="card admin">
    <div class="topbar">
        <h1>Admin</h1>
        <form method="POST" action="{{ route('admin.logout') }}">@csrf <button class="ghost" type="submit">Log out</button></form>
    </div>

    @if(session('success')) <div class="flash success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="flash error">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="flash error">{{ $errors->first() }}</div> @endif

    <hr>
    <h2>SMTP settings</h2>
    <p class="muted">Until a host is saved, codes are written to <code>storage/logs/laravel.log</code> instead of being emailed.</p>
    <form method="POST" action="{{ route('admin.smtp.save') }}">
        @csrf @method('PATCH')
        <div class="grid">
            <div><label>Host</label><input name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host']) }}" placeholder="smtp.example.com" required></div>
            <div><label>Port</label><input name="smtp_port" type="number" value="{{ old('smtp_port', $settings['smtp_port']) }}" required></div>
            <div><label>Username</label><input name="smtp_username" value="{{ old('smtp_username', $settings['smtp_username']) }}" autocomplete="off"></div>
            <div><label>Password {{ $settings['has_password'] ? '(saved, leave blank to keep)' : '' }}</label><input name="smtp_password" type="password" autocomplete="new-password"></div>
            <div>
                <label>Encryption</label>
                <select name="smtp_encryption">
                    @foreach(['tls' => 'TLS / STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('smtp_encryption', $settings['smtp_encryption']) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>From address</label><input name="smtp_from_address" type="email" value="{{ old('smtp_from_address', $settings['smtp_from_address']) }}"></div>
            <div><label>From name</label><input name="smtp_from_name" value="{{ old('smtp_from_name', $settings['smtp_from_name']) }}"></div>
        </div>
        <button type="submit">Save SMTP</button>
    </form>

    <form method="POST" action="{{ route('admin.smtp.test') }}" style="margin-top:8px">
        @csrf
        <label>Send a test email to</label>
        <div style="display:flex; gap:10px;">
            <input name="to" type="email" placeholder="you@example.com" required>
            <button class="ghost" type="submit" style="margin:0">Send test</button>
        </div>
    </form>

    <hr>
    <div class="topbar">
        <h2>Leaderboard <span class="muted">({{ $finished }} finished of {{ $started }} started)</span></h2>
        @if($started)
        <form method="POST" action="{{ route('admin.leaderboard.clear') }}" onsubmit="return confirm('Delete all attempts?')">
            @csrf @method('DELETE') <button class="danger" type="submit">Clear</button>
        </form>
        @endif
    </div>
    <p class="muted">Fewest mistakes wins; ties are broken by time.</p>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Email</th><th>Mistakes</th><th>Time</th><th>Finished</th></tr></thead>
            <tbody>
            @forelse($board as $i => $row)
                <tr class="{{ $i === 0 ? 'rank-1' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->mistakes }}</td>
                    <td>{{ number_format($row->duration_ms / 1000, 1) }}s</td>
                    <td class="muted">{{ $row->updated_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No finished attempts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
