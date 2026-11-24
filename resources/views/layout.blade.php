<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'OTP Slingshot')</title>
<style>
    :root { --bg:#7a1230; --card:#fff; --ink:#1d1d1f; --muted:#6b6b72; --line:#e4e4e8; --accent:#c9a227; --danger:#c0262d; --ok:#1f8a4c; }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color:var(--ink);
           background: radial-gradient(1200px 600px at 20% 0%, #a3203f 0%, var(--bg) 55%, #4a0a1c 100%); display:flex; align-items:center; justify-content:center; padding:24px; }
    .card { background:var(--card); border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,.35); width:100%; max-width:440px; padding:32px; }
    .card.wide { max-width:1000px; padding:0; overflow:hidden; }
    .card.admin { max-width:980px; }
    h1 { font-size:20px; margin:0 0 6px; } h2 { font-size:16px; margin:0 0 12px; }
    p.muted, .muted { color:var(--muted); font-size:14px; }
    label { display:block; font-size:13px; font-weight:600; margin:14px 0 6px; }
    input, select { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:10px; font-size:15px; background:#fff; }
    input:focus, select:focus { outline:2px solid var(--accent); border-color:transparent; }
    button, .btn { display:inline-block; margin-top:18px; padding:11px 18px; border:0; border-radius:10px; background:var(--ink); color:#fff; font-size:15px; font-weight:600; cursor:pointer; text-decoration:none; }
    button.full { width:100%; }
    button.danger { background:var(--danger); } button.ghost { background:#efeff2; color:var(--ink); }
    .flash { padding:10px 12px; border-radius:10px; font-size:14px; margin:14px 0 0; }
    .flash.error { background:#fde8e9; color:#8f1c22; } .flash.success { background:#e3f5ea; color:#176b3b; }
    .grid { display:grid; grid-template-columns:1fr 1fr; gap:0 16px; } @media (max-width:640px){ .grid{grid-template-columns:1fr;} }
    .table-wrap { overflow-x:auto; }
    table.table { width:100%; border-collapse:collapse; font-size:14px; }
    .table th, .table td { text-align:left; padding:9px 10px; border-bottom:1px solid var(--line); white-space:nowrap; }
    .table th { font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
    .rank-1 td { background:#fff8e1; font-weight:600; }
    .topbar { display:flex; justify-content:space-between; align-items:center; }
    .topbar form button { margin:0; padding:7px 12px; font-size:13px; }
    .wrap { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px; width:100%; max-width:1180px; align-items:start; }
    .wrap.narrow { grid-template-columns:440px 320px; justify-content:center; }
    @media (max-width:900px){ .wrap, .wrap.narrow { grid-template-columns:minmax(0,1fr); } }
    .wrap .card { max-width:none; }
    .lb { padding:22px; }
    .lb h2 { display:flex; align-items:center; gap:8px; }
    .lb ol { list-style:none; margin:10px 0 0; padding:0; }
    .lb li { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--line); font-size:14px; }
    .lb li:last-child { border-bottom:0; }
    .lb .pos { width:26px; height:26px; border-radius:50%; background:#f1f1f3; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex:none; }
    .lb li:nth-child(1) .pos { background:#f6d365; } .lb li:nth-child(2) .pos { background:#dfe3e8; } .lb li:nth-child(3) .pos { background:#e6b88a; }
    .lb .who { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .lb .score { text-align:right; font-size:12px; color:var(--muted); line-height:1.35; flex:none; }
    .lb .score b { color:var(--ink); font-size:14px; }
    hr { border:0; border-top:1px solid var(--line); margin:26px 0; }
</style>
@stack('styles')
</head>
<body>
@yield('content')
@stack('scripts')
</body>
</html>
