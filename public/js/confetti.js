/**
 * QuizBlast Confetti — lightweight canvas-based confetti burst
 */
QB.Confetti = (function(){
  let canvas, ctx2d, particles=[], animId=null;

  const COLORS = ['#7c5cff','#fbbf24','#ff5d73','#22d3ee','#a3e635','#ffffff'];

  function init(){
    if(canvas) return;
    canvas = document.createElement('canvas');
    canvas.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
    document.body.appendChild(canvas);
    resize();
    window.addEventListener('resize', resize);
  }

  function resize(){
    if(!canvas) return;
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    ctx2d = canvas.getContext('2d');
  }

  function burst(count){
    init();
    count = count || 120;
    for(let i=0; i<count; i++){
      particles.push({
        x:     Math.random() * canvas.width,
        y:     Math.random() * canvas.height * 0.4 - 20,
        vx:    (Math.random()-0.5)*8,
        vy:    Math.random()*6+2,
        rot:   Math.random()*360,
        rotV:  (Math.random()-0.5)*8,
        w:     Math.random()*10+5,
        h:     Math.random()*5+3,
        color: COLORS[Math.floor(Math.random()*COLORS.length)],
        life:  1,
        decay: Math.random()*0.012+0.008,
      });
    }
    if(!animId) loop();
  }

  function loop(){
    ctx2d.clearRect(0,0,canvas.width,canvas.height);
    particles = particles.filter(p=>{
      p.x   += p.vx;
      p.y   += p.vy;
      p.rot += p.rotV;
      p.vy  += 0.15;
      p.vx  *= 0.99;
      p.life -= p.decay;
      ctx2d.save();
      ctx2d.translate(p.x, p.y);
      ctx2d.rotate(p.rot * Math.PI/180);
      ctx2d.globalAlpha = p.life;
      ctx2d.fillStyle = p.color;
      ctx2d.fillRect(-p.w/2, -p.h/2, p.w, p.h);
      ctx2d.restore();
      return p.life > 0;
    });
    if(particles.length > 0){
      animId = requestAnimationFrame(loop);
    } else {
      animId = null;
    }
  }

  function stop(){
    particles=[];
    if(animId){ cancelAnimationFrame(animId); animId=null; }
    if(ctx2d) ctx2d.clearRect(0,0,canvas.width,canvas.height);
  }

  return { burst, stop };
})();

window.QB = window.QB || {};
window.QB.Confetti = QB.Confetti;
