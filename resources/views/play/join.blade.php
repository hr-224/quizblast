@extends('layouts.app')
@section('title', 'Join a Game')

@push('head')
<style>
/* ── Join page: navbar override (dark glass + yellow underline) ── */
.navbar {
  background: rgba(15,10,30,.78) !important;
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 2px solid #ffd000 !important;
  box-shadow: 0 2px 28px rgba(255,208,0,.12) !important;
}
.navbar .btn-white {
  background: #ffd000 !important;
  color: #1a0533 !important;
  box-shadow: 0 2px 12px rgba(255,208,0,.35) !important;
}

/* ── Canvas layer ── */
#join-bg {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  display: block;
}

/* ── Page body ── */
.join-page-body {
  position: relative;
  z-index: 5;
  min-height: calc(100vh - 62px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

/* ── Join card ── */
.join-card {
  width: 100%;
  max-width: 400px;
  background: rgba(20,12,40,.82); /* fallback for no backdrop-filter */
  background: rgba(255,255,255,.065);
  backdrop-filter: blur(22px);
  -webkit-backdrop-filter: blur(22px);
  border: 1px solid rgba(255,208,0,.3);
  border-radius: 10px;
  padding: 2.2rem 1.75rem;
  text-align: center;
  box-shadow: 0 8px 48px rgba(0,0,0,.55),
              0 0 0 1px rgba(255,255,255,.04) inset,
              0 0 40px rgba(255,208,0,.06);
}

.join-card-bolt {
  font-size: 2.6rem;
  margin-bottom: .2rem;
  display: block;
  filter: drop-shadow(0 0 10px rgba(255,208,0,.8))
          drop-shadow(0 0 28px rgba(255,208,0,.4));
}

.join-card-title {
  font-family: 'Montserrat', sans-serif;
  font-size: 2.2rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: -1px;
  margin-bottom: .2rem;
  background: linear-gradient(135deg, #ffd000 0%, #fff 28%, #c084fc 68%, #a855f7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.join-card-sub {
  font-size: .82rem;
  color: rgba(255,255,255,.45);
  margin-bottom: 1.6rem;
  letter-spacing: .3px;
}

/* Override global .form-label colour for this page */
.join-card .form-label {
  color: rgba(200,150,255,.55);
}

/* Override global .form-control for this page */
.join-card .form-control {
  background: rgba(255,255,255,.07);
  border: 1.5px solid rgba(200,150,255,.22);
}
.join-card .form-control:focus {
  border-color: rgba(200,150,255,.55);
  background: rgba(255,255,255,.09);
}

.join-pin-input {
  font-family: 'Montserrat', sans-serif;
  font-size: 1.7rem;
  font-weight: 900;
  letter-spacing: .35em;
  text-align: center;
  padding: .75rem .5rem;
}

.join-nick-input {
  font-family: 'Montserrat', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  text-align: center;
}

.join-btn {
  width: 100%;
  padding: .9rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-family: 'Montserrat', sans-serif;
  font-size: 1rem;
  font-weight: 900;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  background: #ffd000;
  color: #1a0533;
  margin-top: .35rem;
  box-shadow: 0 4px 24px rgba(255,208,0,.45), 0 2px 0 rgba(0,0,0,.25);
  transition: transform .1s, box-shadow .1s;
}
.join-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 28px rgba(255,208,0,.55), 0 2px 0 rgba(0,0,0,.25);
}
.join-btn:active {
  transform: translateY(1px);
  box-shadow: 0 2px 12px rgba(255,208,0,.4);
}

.join-footer-links {
  margin-top: 1.1rem;
  font-size: .78rem;
  color: rgba(255,255,255,.4);
  display: flex;
  gap: .5rem;
  justify-content: center;
  flex-wrap: wrap;
  align-items: center;
}
.join-footer-links a { font-weight: 700; }
.join-footer-links .link-yellow { color: #ffd000; }
.join-footer-links .link-cyan   { color: #00d2ff; }
.join-footer-links .sep         { color: rgba(255,255,255,.2); }
</style>
@endpush

@section('content')
<canvas id="join-bg" aria-hidden="true"></canvas>

<div class="join-page-body">
  <div class="join-card slide-up">

    <span class="join-card-bolt">⚡</span>
    <div class="join-card-title">QuizBlast</div>
    <div class="join-card-sub">Enter a game PIN to join</div>

    @if(request('kicked'))
      <div class="alert alert-error" style="text-align:center;margin-bottom:1rem">
        You were kicked from the game.
      </div>
    @endif

    <form method="POST" action="{{ route('play.join.post') }}">
      @csrf
      <div class="form-group">
        <label class="form-label">Game PIN</label>
        <input type="text" name="pin" class="form-control join-pin-input"
               value="{{ old('pin', request('pin')) }}"
               placeholder="000000" maxlength="6" inputmode="numeric"
               autofocus required />
        @error('pin')
          <span class="field-error" style="text-align:center;display:block">{{ $message }}</span>
        @enderror
      </div>
      <div class="form-group">
        <label class="form-label">Nickname</label>
        <input type="text" name="nickname" class="form-control join-nick-input"
               value="{{ old('nickname') }}"
               placeholder="Enter nickname" maxlength="20"
               required />
        @error('nickname')
          <span class="field-error" style="text-align:center;display:block">{{ $message }}</span>
        @enderror
      </div>
      <button type="submit" class="join-btn">JOIN GAME →</button>
    </form>

    <div class="join-footer-links">
      Want to host?
      <a href="{{ route('register') }}" class="link-yellow">Create a host account</a>
      @if(!session('player_account_id'))
        <span class="sep">·</span>
        <a href="{{ route('player.register') }}" class="link-cyan">Player account</a>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var canvas = document.getElementById('join-bg');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var W, H;

  function resize() {
    var oldW = W || window.innerWidth;
    var oldH = H || window.innerHeight;
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
    if (orbs) {
      orbs.forEach(function(o) {
        o.cx = o.cx * (W / oldW);
        o.cy = o.cy * (H / oldH);
      });
    }
  }
  resize();
  window.addEventListener('resize', resize);

  /* ── Nebula orbs ── */
  var ORB_DEFS = [
    { rx:.12, ry:.18, r:320, hue:270, phase:0.0 },
    { rx:.85, ry:.75, r:380, hue:288, phase:1.2 },
    { rx:.50, ry:.06, r:280, hue:252, phase:2.5 },
    { rx:.22, ry:.88, r:260, hue:308, phase:4.0 },
    { rx:.78, ry:.34, r:300, hue:262, phase:5.3 },
    { rx:.40, ry:.55, r:250, hue:278, phase:3.1 },
  ];
  var orbs = ORB_DEFS.map(function(o) {
    return Object.assign({}, o, {
      cx: o.rx * W,
      cy: o.ry * H,
      dx: (Math.random() - .5) * .12,
      dy: (Math.random() - .5) * .12,
    });
  });

  /* ── Answer-colour shapes ── */
  var COLS = ['#e21b3c', '#1368ce', '#d89e00', '#26890c'];
  var shapes = [];
  function mkShape() {
    return {
      x:    Math.random() * W,
      y:    Math.random() * H,
      type: Math.floor(Math.random() * 4),
      size: Math.random() * 24 + 7,
      vx:   (Math.random() - .5) * .45,
      vy:   (Math.random() - .5) * .45,
      rot:  Math.random() * Math.PI * 2,
      rotV: (Math.random() - .5) * .011,
      alpha: Math.random() * .35 + .07,
      col:  COLS[Math.floor(Math.random() * 4)],
    };
  }
  for (var i = 0; i < 90; i++) shapes.push(mkShape());

  /* ── Star particles ── */
  var stars = [];
  for (var j = 0; j < 110; j++) {
    stars.push({
      x:     Math.random() * W,
      y:     Math.random() * H,
      r:     Math.random() * 1.5 + .25,
      vx:    (Math.random() - .5) * .18,
      vy:    (Math.random() - .5) * .18,
      alpha: Math.random() * .45 + .07,
      hue:   252 + Math.random() * 66,
    });
  }

  function drawShape(s) {
    ctx.save();
    ctx.translate(s.x, s.y);
    ctx.rotate(s.rot);
    ctx.globalAlpha = s.alpha;
    ctx.fillStyle = s.col;
    ctx.beginPath();
    var z = s.size;
    if (s.type === 0) {          /* triangle ▲ */
      ctx.moveTo(0, -z); ctx.lineTo(z * .87, z * .5); ctx.lineTo(-z * .87, z * .5); ctx.closePath();
    } else if (s.type === 1) {   /* diamond ◆ */
      ctx.moveTo(0, -z); ctx.lineTo(z * .6, 0); ctx.lineTo(0, z); ctx.lineTo(-z * .6, 0); ctx.closePath();
    } else if (s.type === 2) {   /* circle ● */
      ctx.arc(0, 0, z * .7, 0, Math.PI * 2);
    } else {                     /* square ■ */
      ctx.rect(-z * .7, -z * .7, z * 1.4, z * 1.4);
    }
    ctx.fill();
    ctx.restore();
  }

  var t = 0;
  function draw() {
    ctx.fillStyle = '#0a0010';
    ctx.fillRect(0, 0, W, H);

    /* nebula orbs */
    for (var oi = 0; oi < orbs.length; oi++) {
      var o = orbs[oi];
      o.cx += o.dx;
      o.cy += o.dy;
      if (o.cx < W * .05 || o.cx > W * .95) o.dx *= -1;
      if (o.cy < H * .05 || o.cy > H * .95) o.dy *= -1;
      var breathe = .13 + Math.sin(t * 0.008 + o.phase) * .07;
      var pulse   = 1   + Math.sin(t * 0.005 + o.phase * 1.3) * .07;
      var rr = o.r * pulse;
      var g = ctx.createRadialGradient(o.cx, o.cy, 0, o.cx, o.cy, rr);
      g.addColorStop(0,   'hsla(' + o.hue + ',78%,62%,' + (breathe * .5)  + ')');
      g.addColorStop(.45, 'hsla(' + o.hue + ',70%,52%,' + (breathe * .18) + ')');
      g.addColorStop(1,   'hsla(' + o.hue + ',70%,52%,0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.arc(o.cx, o.cy, rr, 0, Math.PI * 2);
      ctx.fill();
    }

    /* answer-colour shapes */
    for (var si = 0; si < shapes.length; si++) {
      var s = shapes[si];
      s.x += s.vx; s.y += s.vy; s.rot += s.rotV;
      if (s.x < -50) s.x = W + 50; if (s.x > W + 50) s.x = -50;
      if (s.y < -50) s.y = H + 50; if (s.y > H + 50) s.y = -50;
      drawShape(s);
    }

    /* star particles */
    for (var pi = 0; pi < stars.length; pi++) {
      var p = stars[pi];
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
      if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'hsla(' + p.hue + ',70%,78%,' + p.alpha + ')';
      ctx.fill();
    }

    t++;
    rafId = requestAnimationFrame(draw);
  }
  var rafId;
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      cancelAnimationFrame(rafId);
    } else {
      draw();
    }
  });
  draw();
})();
</script>
@endpush
