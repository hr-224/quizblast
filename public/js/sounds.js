const QB = window.QB || {};
QB.Audio = (function(){
  var ctx=null, mg=null, bh=[], mus=true, sfxOn=true, init_=false;
  try{ if(window.localStorage.getItem('qb-muted')==='1'){ mus=false; sfxOn=false; } }catch(e){}

  function init(){
    if(init_)return;
    try{
      ctx=new(window.AudioContext||window.webkitAudioContext)();
      mg=ctx.createGain(); mg.gain.value=0.45; mg.connect(ctx.destination);
      init_=true;
    }catch(e){}
  }
  function resume(){ if(ctx&&ctx.state==='suspended')ctx.resume(); }

  function n(freq,type,start,dur,vol){
    if(!ctx)return;
    var o=ctx.createOscillator(), g=ctx.createGain();
    o.connect(g); g.connect(mg); o.type=type; o.frequency.value=freq;
    g.gain.setValueAtTime(0,start);
    g.gain.linearRampToValueAtTime(vol,start+0.005);
    g.gain.setValueAtTime(vol,start+dur-0.03);
    g.gain.linearRampToValueAtTime(0,start+dur);
    o.start(start); o.stop(start+dur+0.01); bh.push(o);
  }

  function kick(t){
    if(!ctx)return;
    var o=ctx.createOscillator(), g=ctx.createGain();
    o.connect(g); g.connect(mg); o.type='sine';
    o.frequency.setValueAtTime(160,t); o.frequency.exponentialRampToValueAtTime(40,t+0.08);
    g.gain.setValueAtTime(0.9,t); g.gain.exponentialRampToValueAtTime(0.001,t+0.15);
    o.start(t); o.stop(t+0.18); bh.push(o);
  }

  function snare(t){
    if(!ctx)return;
    var sz=Math.floor(ctx.sampleRate*0.1);
    var buf=ctx.createBuffer(1,sz,ctx.sampleRate);
    var d=buf.getChannelData(0);
    for(var i=0;i<sz;i++) d[i]=(Math.random()*2-1)*(1-i/sz);
    var src=ctx.createBufferSource(), g=ctx.createGain(), f=ctx.createBiquadFilter();
    f.type='highpass'; f.frequency.value=2000;
    src.buffer=buf; src.connect(f); f.connect(g); g.connect(mg);
    g.gain.setValueAtTime(0.4,t); g.gain.exponentialRampToValueAtTime(0.001,t+0.1);
    src.start(t); src.stop(t+0.12); bh.push(src);
  }

  function hh(t,v){
    if(!ctx)return;
    var sz=Math.floor(ctx.sampleRate*0.03);
    var buf=ctx.createBuffer(1,sz,ctx.sampleRate);
    var d=buf.getChannelData(0);
    for(var i=0;i<sz;i++) d[i]=Math.random()*2-1;
    var src=ctx.createBufferSource(), g=ctx.createGain(), f=ctx.createBiquadFilter();
    f.type='highpass'; f.frequency.value=8000;
    src.buffer=buf; src.connect(f); f.connect(g); g.connect(mg);
    g.gain.setValueAtTime(v||0.1,t); g.gain.exponentialRampToValueAtTime(0.001,t+0.03);
    src.start(t); src.stop(t+0.04); bh.push(src);
  }

  function sn(freq,type,delay,dur,vol){
    if(!sfxOn||!ctx)return;
    n(freq,type,ctx.currentTime+delay,dur,vol);
  }

  var SFX={
    correct:       function(){ sn(523,'sine',0,.08,.4); sn(659,'sine',.09,.08,.45); sn(784,'sine',.18,.08,.45); sn(1047,'sine',.27,.3,.5); },
    wrong:         function(){ sn(320,'sawtooth',0,.08,.4); sn(240,'sawtooth',.09,.08,.4); sn(160,'sawtooth',.18,.25,.45); },
    countdown:     function(x){ sn(x<=3?1047:880,'square',0,.06,.25); },
    timeUp:        function(){ sn(440,'sawtooth',0,.05,.3); sn(330,'sawtooth',.06,.05,.3); sn(220,'sawtooth',.12,.2,.3); },
    playerJoin:    function(){ sn(784,'sine',0,.06,.25); sn(1047,'sine',.07,.1,.25); },
    questionStart: function(){ [523,659,784,1047].forEach(function(f,i){ sn(f,'sine',i*.08,.12,.35); }); },
    answerLocked:  function(){ sn(880,'sine',0,.05,.2); sn(1175,'sine',.05,.08,.18); },
    reveal:        function(){ [300,400,500,600].forEach(function(f,i){ sn(f,'square',i*.04,.1,.2); }); },
    leaderboard:   function(){ [523,659,784,880,1047].forEach(function(f,i){ sn(f,'sine',i*.07,.15,.3); }); },
    streakBonus:   function(){ [659,784,880,1047,1319].forEach(function(f,i){ sn(f,'sine',i*.06,.12,.32); }); },
    podium: function(){
      [523,523,523,415,523].forEach(function(f,i){ sn(f,'sine',i*.15,.18,.4); });
      sn(659,'sine',.85,.5,.45);
      setTimeout(function(){ [784,784,784,659,784,1047].forEach(function(f,i){ sn(f,'sine',i*.12,.2,.4); }); },1200);
    },
  };

  function playLobbyMusic(){
    if(!mus||!ctx)return;
    resume(); stopBg();

    var B=0.5; // 120 BPM — beat duration in seconds

    // Smooth triangle-wave tone with gentle ADSR
    function tone(f,t,d,v){
      if(!ctx)return;
      var o=ctx.createOscillator(), g=ctx.createGain();
      o.type='triangle'; o.frequency.value=f;
      o.connect(g); g.connect(mg);
      var att=0.012, rel=Math.min(0.07,d*0.25);
      g.gain.setValueAtTime(0,t);
      g.gain.linearRampToValueAtTime(v,t+att);
      g.gain.setValueAtTime(v,t+d-rel);
      g.gain.linearRampToValueAtTime(0,t+d);
      o.start(t); o.stop(t+d+0.01); bh.push(o);
    }

    // Soft sine bass
    function bs(f,t,d){
      if(!ctx)return;
      var o=ctx.createOscillator(), g=ctx.createGain();
      o.type='sine'; o.frequency.value=f;
      o.connect(g); g.connect(mg);
      g.gain.setValueAtTime(0,t);
      g.gain.linearRampToValueAtTime(0.26,t+0.02);
      g.gain.setValueAtTime(0.26,t+d-0.05);
      g.gain.linearRampToValueAtTime(0,t+d);
      o.start(t); o.stop(t+d+0.01); bh.push(o);
    }

    // 4-bar loop (16 beats = 8s), C major, I–V–vi–IV
    // Melody: [freq, beat, dur_beats, vol]
    var MEL=[
      // Bar 1 – C major (I): rising C-E-G-C
      [523.25,0,.42,.38],[659.25,1,.42,.38],[783.99,2,.42,.38],[1046.5,3,.85,.40],
      // Bar 2 – G major (V): A-G-E-D
      [880.00,4,.42,.36],[783.99,5,.42,.36],[659.25,6,.42,.36],[587.33,7,.85,.36],
      // Bar 3 – A minor (vi): E-F-G-A
      [659.25,8,.42,.36],[698.46,8.5,.38,.34],[783.99,9,.42,.38],[880.00,10,.85,.40],
      // Bar 3 tail – F major (IV): A-G
      [880.00,11,.35,.34],[783.99,11.5,.35,.34],
      // Bar 4 – C major (I): resolution E-D-C, then E-C
      [659.25,12,.42,.38],[587.33,12.5,.35,.34],[523.25,13,.80,.38],
      [659.25,14,.42,.36],[523.25,15,.85,.40],
    ];

    // Chord pads (very soft, just fill the harmony)
    // [freq array, beat_start, dur_beats]
    var PADS=[
      {t:0,  d:4, notes:[261.63,329.63,392.00]}, // C major
      {t:4,  d:4, notes:[196.00,246.94,293.66]}, // G major
      {t:8,  d:2, notes:[220.00,261.63,329.63]}, // A minor
      {t:10, d:2, notes:[174.61,220.00,261.63]}, // F major
      {t:12, d:4, notes:[261.63,329.63,392.00]}, // C major
    ];

    // Bass: root on beats 1 & 3 of each bar
    var BASS=[
      [130.81,0,1.8],[130.81,2,1.8],  // C bar 1
      [98.00, 4,1.8],[98.00, 6,1.8],  // G bar 2
      [110.00,8,1.8],[174.61,10,1.8], // Am, F bar 3
      [130.81,12,1.8],[130.81,14,1.8],// C bar 4
    ];

    function loop(startT){
      if(!mus)return;
      var loopDur=B*16;

      MEL.forEach(function(m){ tone(m[0],startT+m[1]*B,m[2]*B,m[3]); });
      PADS.forEach(function(p){ p.notes.forEach(function(f){ tone(f,startT+p.t*B,p.d*B-0.05,0.07); }); });
      BASS.forEach(function(b){ bs(b[0],startT+b[1]*B,b[2]*B); });

      for(var bar=0;bar<4;bar++){
        var bt=startT+bar*4*B;
        kick(bt); kick(bt+B*2); kick(bt+B*2.5);
        snare(bt+B); snare(bt+B*3);
        for(var h=0;h<8;h++){ hh(bt+h*B*0.5, h%2===0?0.09:0.05); }
      }

      var tid=setTimeout(function(){ if(mus) loop(startT+loopDur); },(loopDur-0.3)*1000);
      bh.push({stop:function(){ clearTimeout(tid); }});
    }

    loop(ctx.currentTime+0.1);
  }

  function playGameMusic(){}

  function stopBg(){
    bh.forEach(function(h){ try{ if(h.stop) h.stop(); }catch(e){} });
    bh=[];
  }

  function createControls(){
    var d=document.createElement('div');
    d.style.cssText='position:fixed;bottom:1rem;right:1rem;z-index:500;display:flex;gap:.4rem';
    d.innerHTML='<button id="btn-music" onclick="QB.Audio.toggleMusic()" title="Music" style="background:rgba(0,0,0,.6);border:1px solid rgba(255,255,255,.2);color:#fff;width:36px;height:36px;border-radius:4px;cursor:pointer;font-size:1rem;line-height:1">🎵</button>'
      +'<button id="btn-sfx" onclick="QB.Audio.toggleSFX()" title="SFX" style="background:rgba(0,0,0,.6);border:1px solid rgba(255,255,255,.2);color:#fff;width:36px;height:36px;border-radius:4px;cursor:pointer;font-size:1rem;line-height:1">🔊</button>';
    document.body.appendChild(d);
    updateBtns();
  }

  function updateBtns(){
    var bm=document.getElementById('btn-music'), bs=document.getElementById('btn-sfx');
    if(bm) bm.style.opacity=mus?'1':'.3';
    if(bs) bs.style.opacity=sfxOn?'1':'.3';
  }

  return{
    init:init, resume:resume, sfx:SFX,
    playLobbyMusic:playLobbyMusic, playGameMusic:playGameMusic, stopBg:stopBg,
    toggleMusic:function(){ mus=!mus; if(!mus) stopBg(); else playLobbyMusic(); updateBtns(); },
    toggleSFX:  function(){ sfxOn=!sfxOn; updateBtns(); },
    setMuted:   function(m){ mus=!m; sfxOn=!m; if(m) stopBg(); updateBtns(); },
    createControls:createControls,
  };
})();
window.QB=QB;
