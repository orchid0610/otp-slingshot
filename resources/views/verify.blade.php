@extends('layout')
@section('title', 'Enter your code')

@push('styles')
<style>
    .code-banner { display:flex; align-items:center; gap:16px; flex-wrap:wrap; padding:14px 22px; background:linear-gradient(90deg,#fff8e1,#fff); border-bottom:1px solid var(--line); }
    .code-banner .lbl { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#8a6d0a; }
    .code-banner .hint { font-size:12px; color:var(--muted); margin-left:auto; }
    .chips { display:flex; gap:8px; }
    .chip { width:38px; height:46px; display:flex; align-items:center; justify-content:center; border-radius:9px; background:#fff; border:2px solid var(--accent);
            font:700 24px ui-monospace, SFMono-Regular, Menlo, monospace; box-shadow:0 3px 0 rgba(201,162,39,.35); }
    .stage { position:relative; height:min(78vh, 600px); min-height:460px; background:#fff; user-select:none; }
    .stage .title { text-align:center; font-weight:700; font-size:13px; padding-top:22px; }
    .stage .sub { text-align:center; font-size:12px; color:var(--muted); margin-top:4px; }
    .boxes { display:flex; justify-content:center; gap:8px; margin-top:16px; }
    .box { width:36px; height:40px; border-radius:7px; background:#f1f1f3; display:flex; align-items:center; justify-content:center;
           font-weight:700; font-size:18px; border:1.5px solid transparent; transition:transform .15s, background .15s; }
    .box.active { border-color:var(--accent); background:#fff8e1; }
    .box.filled { background:#e9f6ee; color:var(--ok); }
    .box.bad { background:#fde8e9; animation:shake .35s; }
    @keyframes shake { 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)} }
    .hud { position:absolute; top:14px; right:18px; font-size:12px; color:var(--muted); text-align:right; line-height:1.6; }
    #c { position:absolute; inset:0; width:100%; height:100%; touch-action:none; cursor:grab; }
    .done { position:absolute; inset:0; background:rgba(255,255,255,.96); display:none; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:20px; }
    .done.show { display:flex; }
    .done h1 { font-size:26px; } .done .stats { display:flex; gap:28px; margin:18px 0 6px; }
    .done .stats b { display:block; font-size:28px; } .done .stats span { font-size:12px; color:var(--muted); }
</style>
@endpush

@section('content')
<div class="wrap">
<div class="card wide">
    <div class="code-banner">
        <span class="lbl">Your code</span>
        <div class="chips">
            @foreach(str_split($attempt->code) as $ch)<span class="chip">{{ $ch }}</span>@endforeach
        </div>
        <span class="hint">Sling these in, in order</span>
    </div>
    <div class="stage" id="stage">
        <div class="title">Enter your code</div>
        <div class="sub">Tap a number, pull the sling back, and let go.</div>
        <div class="boxes" id="boxes">
            @for($i = 0; $i < $length; $i++)
                <div class="box {{ $i === $attempt->progress ? 'active' : '' }} {{ $i < $attempt->progress ? 'filled' : '' }}"></div>
            @endfor
        </div>
        <div class="hud"><div>Mistakes: <b id="mistakes">{{ $attempt->mistakes }}</b></div><div>Time: <b id="timer">0.0s</b></div></div>
        <canvas id="c"></canvas>

        <div class="done" id="done">
            <h1>Verified</h1>
            <div class="stats">
                <div><b id="d-mistakes">0</b><span>mistakes</span></div>
                <div><b id="d-time">0s</b><span>time</span></div>
                <div><b id="d-rank">#1</b><span>rank</span></div>
            </div>
            <a class="btn" href="{{ route('home') }}">Play again</a>
        </div>
    </div>
</div>

<div class="card lb">
    <h2>🏆 Leaderboard</h2>
    <ol id="lb">@include('partials.leaderboard')</ol>
</div>
</div>
@endsection


@push('scripts')
<script>
(() => {
    const LEN = {{ $length }};
    const URL = @json(route('verify.throw'));
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    const stage = document.getElementById('stage');
    const cv = document.getElementById('c');
    const ctx = cv.getContext('2d');
    const boxEls = [...document.querySelectorAll('#boxes .box')];
    const mistakesEl = document.getElementById('mistakes');
    const timerEl = document.getElementById('timer');

    const GRAVITY = 0.35, POWER = 0.17, MAX_PULL = 120, PICK_R = 26, POUCH_R = 46;

    let W, H, dpr, rest, tipL, tipR, boxRects = [], floor = [];
    let loaded = null, pouch = { x: 0, y: 0 }, dragging = false, proj = null, busy = false;
    let progress = {{ $attempt->progress }}, startedAt = {{ $attempt->started_ms ?? 'null' }}, finished = false;

    const rnd = (a, b) => a + Math.random() * (b - a);
    const newFloorDigit = (d) => ({ d, x: rnd(24, W - 24), y: H - rnd(10, 34), r: rnd(-.6, .6), s: rnd(15, 21) });

    function resize() {
        const r = stage.getBoundingClientRect();
        dpr = window.devicePixelRatio || 1; W = r.width; H = r.height;
        cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        tipL = { x: W / 2 - 17, y: H - 92 }; tipR = { x: W / 2 + 17, y: H - 92 };
        rest = { x: W / 2, y: H - 80 };
        if (!dragging) pouch = { ...rest };
        floor = [];
        for (let k = 0; k < 3; k++) for (let d = 0; d < 10; d++) floor.push(newFloorDigit(d));
        measureBoxes();
    }

    function measureBoxes() {
        const s = stage.getBoundingClientRect();
        boxRects = boxEls.map(el => { const r = el.getBoundingClientRect(); return { x: r.left - s.left, y: r.top - s.top, w: r.width, h: r.height }; });
    }

    function pos(e) { const r = cv.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
    const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

    cv.addEventListener('pointerdown', e => {
        if (proj || busy || finished) return;
        const p = pos(e);
        if (loaded !== null && dist(p, pouch) < POUCH_R) { dragging = true; cv.setPointerCapture(e.pointerId); cv.style.cursor = 'grabbing'; return; }
        let best = -1, bd = PICK_R;
        floor.forEach((f, i) => { const dd = dist(p, f); if (dd < bd) { bd = dd; best = i; } });
        if (best >= 0) {
            const f = floor[best];
            if (loaded !== null) floor.push(newFloorDigit(loaded));   // put the old one back
            loaded = f.d; floor.splice(best, 1); floor.push(newFloorDigit(f.d));
            pouch = { ...rest };
        }
    });

    cv.addEventListener('pointermove', e => {
        if (!dragging) return;
        const p = pos(e);
        let dx = p.x - rest.x, dy = p.y - rest.y, len = Math.hypot(dx, dy);
        if (len > MAX_PULL) { dx *= MAX_PULL / len; dy *= MAX_PULL / len; }
        pouch = { x: rest.x + dx, y: rest.y + dy };
    });

    const release = () => {
        if (!dragging) return;
        dragging = false; cv.style.cursor = 'grab';
        const px = rest.x - pouch.x, py = rest.y - pouch.y;
        if (Math.hypot(px, py) < 22) { pouch = { ...rest }; return; }
        measureBoxes();
        proj = { x: pouch.x, y: pouch.y, vx: px * POWER, vy: py * POWER, d: loaded, rot: 0 };
        loaded = null; pouch = { ...rest };
    };
    cv.addEventListener('pointerup', release);
    cv.addEventListener('pointercancel', release);

    function trajectory() {
        const px = rest.x - pouch.x, py = rest.y - pouch.y;
        let x = pouch.x, y = pouch.y, vx = px * POWER, vy = py * POWER; const pts = [];
        for (let i = 0; i < 28; i++) { x += vx; y += vy; vy += GRAVITY; if (i % 3 === 2) pts.push({ x, y }); }
        return pts;
    }

    async function report(result, digit) {
        busy = true;
        try {
            const res = await fetch(URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ digit, result }) });
            if (res.status === 410 || res.status === 419) { location.href = @json(route('home')); return null; }
            return await res.json();
        } catch (e) { return null; } finally { busy = false; }
    }

    async function resolve(kind, index, digit) {
        const p = proj; proj = null;
        const j = await report(kind, digit);
        if (!j) return;
        startedAt = j.started; mistakesEl.textContent = j.mistakes;
        if (kind === 'active' && j.ok) {
            boxEls[index].textContent = digit;
            boxEls[index].classList.remove('active'); boxEls[index].classList.add('filled');
            progress = j.progress;
            if (boxEls[progress]) boxEls[progress].classList.add('active');
        } else {
            const el = boxEls[index >= 0 ? index : progress];
            if (el) { el.classList.remove('bad'); void el.offsetWidth; el.classList.add('bad'); }
        }
        if (j.done) {
            finished = true;
            document.getElementById('d-mistakes').textContent = j.mistakes;
            document.getElementById('d-time').textContent = (j.duration / 1000).toFixed(1) + 's';
            document.getElementById('d-rank').textContent = '#' + j.rank;
            setTimeout(() => document.getElementById('done').classList.add('show'), 450);
            fetch(@json(route('leaderboard'))).then(r => r.text()).then(h => document.getElementById('lb').innerHTML = h).catch(() => {});
        }
    }

    function step() {
        if (proj && !busy) {
            proj.x += proj.vx; proj.y += proj.vy; proj.vy += GRAVITY; proj.rot += .25;
            const rowBottom = boxRects[0].y + boxRects[0].h;
            if (proj.vy < 0 && proj.y <= rowBottom + 6 && proj.y >= boxRects[0].y - 40) {
                const i = boxRects.findIndex(b => proj.x >= b.x - 6 && proj.x <= b.x + b.w + 6);
                if (i === -1) resolve('miss', -1, proj.d);
                else resolve(i === progress ? 'active' : 'wrong_box', i, proj.d);
            } else if (proj.y > H + 40 || proj.x < -40 || proj.x > W + 40 || proj.y < -80) {
                resolve('miss', -1, proj.d);
            }
        }
        draw(); requestAnimationFrame(step);
    }

    function digitBox(x, y, d, size) {
        ctx.fillStyle = '#fff'; ctx.strokeStyle = '#d9d9de'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.roundRect(x - size, y - size, size * 2, size * 2, 6); ctx.fill(); ctx.stroke();
        ctx.fillStyle = '#1d1d1f'; ctx.font = `700 ${size + 3}px system-ui, sans-serif`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(d, x, y + 1);
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);

        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        floor.forEach(f => { ctx.save(); ctx.translate(f.x, f.y); ctx.rotate(f.r); ctx.fillStyle = '#9a9aa3'; ctx.font = `600 ${f.s}px system-ui, sans-serif`; ctx.fillText(f.d, 0, 0); ctx.restore(); });

        // wooden sling
        ctx.lineCap = 'round'; ctx.strokeStyle = '#2b2b2e'; ctx.lineWidth = 9;
        ctx.beginPath(); ctx.moveTo(W / 2, H); ctx.lineTo(W / 2, H - 46); ctx.stroke();
        ctx.lineWidth = 7;
        ctx.beginPath(); ctx.moveTo(W / 2, H - 46); ctx.lineTo(tipL.x, tipL.y); ctx.moveTo(W / 2, H - 46); ctx.lineTo(tipR.x, tipR.y); ctx.stroke();

        if (dragging && loaded !== null) {
            ctx.fillStyle = '#c9a227';
            trajectory().forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, 2.4, 0, 7); ctx.fill(); });
        }

        // band
        ctx.strokeStyle = '#7a1230'; ctx.lineWidth = 3;
        ctx.beginPath(); ctx.moveTo(tipL.x, tipL.y); ctx.lineTo(pouch.x, pouch.y); ctx.lineTo(tipR.x, tipR.y); ctx.stroke();

        if (loaded !== null) digitBox(pouch.x, pouch.y - 6, loaded, 12);
        if (proj) { ctx.save(); ctx.translate(proj.x, proj.y); ctx.rotate(Math.sin(proj.rot) * .4); digitBox(0, 0, proj.d, 12); ctx.restore(); }

        if (startedAt && !finished) timerEl.textContent = ((Date.now() - startedAt) / 1000).toFixed(1) + 's';
    }

    window.addEventListener('resize', resize);
    resize(); requestAnimationFrame(step);
})();
</script>
@endpush
