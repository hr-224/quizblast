const QB = window.QB || {};
QB.Audio = (function(){
  var ctx=null, mg=null, bh=[], mus=true, sfxOn=true, init_=false;

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
    var audio = new Audio('/audio/lobby-classic-game.mp3');
    audio.loop = true;
    audio.volume = 0.5;
    audio.play().catch(function(){});
    bh.push({stop:function(){ audio.pause(); audio.currentTime=0; }});
    return;
    if(!mus||!ctx)return;
    resume(); stopBg();

    // 120 BPM, C minor, one note plays at a time — clean and Kahoot-like
    var B=0.5; // beat = 0.5s at 120 BPM

    // Simple clean melody — each note is short and staccato, no overlap
    // [freq_hz, beat_offset, duration_beats]
    var seq=[
      // Bar 1
      [311,.0,.4],[349,.5,.4],[392,1,.4],[311,1.5,.4],
      // Bar 2
      [554,2,.5],[587,2.6,.3],[554,3,.4],[466,3.5,.4],
      // Bar 3
      [392,4,.4],[349,4.5,.4],[311,5,.4],[349,5.5,.4],
      // Bar 4
      [392,6,.8],[311,7,.8],
      // Bar 5
      [349,8,.4],[392,8.5,.4],[466,9,.4],[392,9.5,.4],
      // Bar 6
      [554,10,.5],[587,10.6,.3],[622,11,.5],[587,11.6,.3],
      // Bar 7
      [554,12,.4],[466,12.5,.4],[392,13,.4],[349,13.5,.4],
      // Bar 8
      [311,14,1.0],[311,15,.8],
    ];

    // Bass notes — one per bar
    var bass=[130,130,196,130, 175,175,196,130];

    function loop(startT){
      if(!mus)return;
      var loopDur=B*32;

      // Bass only — no melody
      for(var b=0;b<8;b++){
        var bt=startT+b*4*B;
        n(bass[b],'sine',bt,B*1.6,0.5);
        n(bass[b],'sine',bt+B*2,B*1.6,0.4);
      }

      // Drums
      for(var b2=0;b2<8;b2++){
        var bt2=startT+b2*4*B;
        kick(bt2);
        kick(bt2+B*2);
        snare(bt2+B);
        snare(bt2+B*3);
        kick(bt2+B*2.5); // syncopated kick
        for(var q=0;q<8;q++){
          hh(bt2+q*B*0.5, q%2===0?0.14:0.07);
        }
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
    createControls:createControls,
  };
})();
window.QB=QB;
