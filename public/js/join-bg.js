/* Join-page backdrop: drifting nebula, the four answer shapes and star dust on a canvas.
   Party mode only — Calm shows the static CSS gradient (.join-static). ES5 for old iPads. */
(function () {
  var canvas = document.getElementById('join-bg');
  if (!canvas || !canvas.getContext) return;
  var ctx = canvas.getContext('2d');
  var root = document.documentElement;

  var COLS = ['#ff5d73', '#22d3ee', '#a3e635', '#fbbf24'];
  var ORB_DEFS = [
    { rx: 0.12, ry: 0.18, r: 320, hue: 262, phase: 0.0 },
    { rx: 0.85, ry: 0.75, r: 380, hue: 285, phase: 1.2 },
    { rx: 0.50, ry: 0.06, r: 280, hue: 250, phase: 2.5 },
    { rx: 0.22, ry: 0.88, r: 260, hue: 195, phase: 4.0 }
  ];

  var W = 0, H = 0, t = 0, rafId = null;
  var orbs = [], shapes = [], stars = [], paths = [];

  function partyOn() { return root.getAttribute('data-fx') !== 'calm'; }

  function buildPaths() {
    paths = [];
    if (typeof window.Path2D !== 'function' || !window.QB || !window.QB.Shapes) return;
    for (var i = 0; i < window.QB.Shapes.PATHS.length; i++) {
      paths.push(new window.Path2D(window.QB.Shapes.PATHS[i]));
    }
  }

  function build() {
    var i;
    orbs = [];
    for (i = 0; i < ORB_DEFS.length; i++) {
      var d = ORB_DEFS[i];
      orbs.push({ cx: d.rx * W, cy: d.ry * H, r: d.r, hue: d.hue, phase: d.phase,
                  dx: (Math.random() - 0.5) * 0.12, dy: (Math.random() - 0.5) * 0.12 });
    }
    shapes = [];
    for (i = 0; i < 44; i++) {
      shapes.push({
        x: Math.random() * W, y: Math.random() * H,
        type: Math.floor(Math.random() * 4),
        size: Math.random() * 14 + 9,
        vx: (Math.random() - 0.5) * 0.4, vy: (Math.random() - 0.5) * 0.4,
        rot: Math.random() * Math.PI * 2, rotV: (Math.random() - 0.5) * 0.01,
        alpha: Math.random() * 0.3 + 0.08
      });
    }
    stars = [];
    for (i = 0; i < 70; i++) {
      stars.push({
        x: Math.random() * W, y: Math.random() * H, r: Math.random() * 1.4 + 0.25,
        vx: (Math.random() - 0.5) * 0.16, vy: (Math.random() - 0.5) * 0.16,
        alpha: Math.random() * 0.4 + 0.07
      });
    }
  }

  function resize() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
    build();
  }

  function drawShape(s) {
    ctx.save();
    ctx.translate(s.x, s.y);
    ctx.rotate(s.rot);
    ctx.globalAlpha = s.alpha;
    ctx.fillStyle = COLS[s.type];
    if (paths.length === 4) {
      var k = s.size / 12;
      ctx.scale(k, k);
      ctx.translate(-12, -12);
      ctx.fill(paths[s.type]);
    } else {
      ctx.beginPath();
      ctx.arc(0, 0, s.size * 0.6, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.restore();
  }

  function draw() {
    rafId = null;
    if (!partyOn() || document.hidden) return;
    var i;

    ctx.globalAlpha = 1;
    ctx.fillStyle = '#0b0a1a';
    ctx.fillRect(0, 0, W, H);

    for (i = 0; i < orbs.length; i++) {
      var o = orbs[i];
      o.cx += o.dx; o.cy += o.dy;
      if (o.cx < W * 0.05 || o.cx > W * 0.95) o.dx *= -1;
      if (o.cy < H * 0.05 || o.cy > H * 0.95) o.dy *= -1;
      var breathe = 0.13 + Math.sin(t * 0.008 + o.phase) * 0.07;
      var rr = o.r * (1 + Math.sin(t * 0.005 + o.phase * 1.3) * 0.07);
      var g = ctx.createRadialGradient(o.cx, o.cy, 0, o.cx, o.cy, rr);
      g.addColorStop(0, 'hsla(' + o.hue + ',78%,62%,' + (breathe * 0.5) + ')');
      g.addColorStop(0.45, 'hsla(' + o.hue + ',70%,52%,' + (breathe * 0.18) + ')');
      g.addColorStop(1, 'hsla(' + o.hue + ',70%,52%,0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.arc(o.cx, o.cy, rr, 0, Math.PI * 2);
      ctx.fill();
    }

    for (i = 0; i < shapes.length; i++) {
      var s = shapes[i];
      s.x += s.vx; s.y += s.vy; s.rot += s.rotV;
      if (s.x < -40) s.x = W + 40;
      if (s.x > W + 40) s.x = -40;
      if (s.y < -40) s.y = H + 40;
      if (s.y > H + 40) s.y = -40;
      drawShape(s);
    }

    ctx.globalAlpha = 1;
    for (i = 0; i < stars.length; i++) {
      var p = stars[i];
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0) p.x = W;
      if (p.x > W) p.x = 0;
      if (p.y < 0) p.y = H;
      if (p.y > H) p.y = 0;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(220,210,255,' + p.alpha + ')';
      ctx.fill();
    }

    t++;
    rafId = window.requestAnimationFrame(draw);
  }

  function start() {
    if (rafId === null && partyOn() && !document.hidden) rafId = window.requestAnimationFrame(draw);
  }
  function stop() {
    if (rafId !== null) { window.cancelAnimationFrame(rafId); rafId = null; }
  }

  document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
  document.addEventListener('qb:fx', function () { if (partyOn()) start(); else stop(); });
  window.addEventListener('resize', resize);

  buildPaths();
  resize();
  start();
})();
