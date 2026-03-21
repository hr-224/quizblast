@extends('layouts.game')
@section('title', 'Game Lobby')

@section('topbar-center')
  <div style="text-align:center">
    <div style="font-size:0.7rem;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px">Game PIN</div>
    <div class="pin-display" style="font-size:2rem;letter-spacing:.15em">{{ $game->pin }}</div>
  </div>
@endsection

@section('topbar-right')
  <form method="POST" action="{{ route('game.end', $game) }}">
    @csrf
    <button class="btn btn-outline btn-sm">✕ End</button>
  </form>
@endsection

@section('content')
<div style="min-height:calc(100vh - 61px);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1.5rem">

  <div style="text-align:center;margin-bottom:2rem">
    <p style="font-size:.78rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:.5rem">
      Players join at <strong style="color:var(--qb-yellow)">quizblast.ultmods.com</strong>
    </p>
    <div class="pin-display">{{ $game->pin }}</div>
    <div style="margin:1rem auto;background:#fff;padding:.6rem;border-radius:var(--radius);display:inline-block">
      <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode(url('/play') . '?pin=' . $game->pin) }}&size=140x140&color=46178f" width="140" height="140" alt="QR" style="display:block" />
    </div>
    <div style="display:flex;align-items:center;justify-content:center;gap:.6rem;margin-bottom:.3rem">
      <div class="pulse-dot" id="ws-dot" style="background:var(--qb-muted)"></div>
      <span id="player-count" style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:1.1rem">
        <span id="count-num">{{ $game->players->where('is_spectator', false)->count() }}</span> waiting
      </span>
    </div>
    <p style="font-size:.72rem;color:rgba(255,255,255,.3);margin-bottom:1.25rem">Click a player name to kick</p>

    @if(session('error'))<div class="alert alert-error mb-2">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('game.launch', $game) }}">
      @csrf
      <button type="submit" class="btn btn-success btn-xl" id="launch-btn" {{ $game->players->where('is_spectator', false)->isEmpty() ? 'disabled' : '' }}>
        ▶ START GAME
        <span style="font-size:.82rem;opacity:.7;font-weight:600;margin-left:.3rem">({{ $game->quiz->questions->count() }} questions)</span>
      </button>
    </form>

    <p style="font-size:.8rem;color:var(--qb-muted);margin-top:.75rem">
      Quiz: <strong style="color:var(--qb-text)">{{ $game->quiz->title }}</strong>
    </p>

    @if($game->spectator_mode)
      <p style="font-size:.72rem;color:rgba(255,255,255,.3);margin-top:.4rem">
        Spectate: <a href="{{ route('spectate.join', $game->pin) }}" target="_blank" style="color:var(--qb-cyan)">/spectate/{{ $game->pin }}</a>
      </p>
    @endif
  </div>

  <div id="player-grid" class="player-grid" style="max-width:900px;width:100%"></div>
</div>

<div id="kick-confirm" style="display:none;position:fixed;z-index:999;background:var(--qb-card);border:2px solid var(--qb-red);border-radius:var(--radius);padding:.6rem 1rem;font-size:.82rem;font-family:'Montserrat',sans-serif;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.5)">
  <span id="kick-name" style="color:#fff"></span>
  <span style="color:var(--qb-muted);margin:0 .4rem">·</span>
  <span id="kick-yes" style="color:var(--qb-red);cursor:pointer;text-transform:uppercase">KICK ✕</span>
  <span style="color:var(--qb-muted);margin:0 .4rem">·</span>
  <span id="kick-no" style="color:var(--qb-muted);cursor:pointer;text-transform:uppercase">Cancel</span>
</div>
@endsection

@push('head')
<style>
html { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar { display: none; }
</style>
<style>
@keyframes float-in{0%{opacity:0;transform:translateY(12px) scale(.85)}100%{opacity:1;transform:none}}
@keyframes float-idle{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.player-chip{animation:float-in .4s cubic-bezier(.34,1.4,.64,1) forwards;cursor:pointer;transition:background .15s,outline .15s}
.player-chip:hover{background:#a0344c!important;outline:2px solid var(--qb-red)}
.player-chip.idle{animation:float-idle var(--dur,3s) ease-in-out infinite;animation-delay:var(--delay,0s)}
</style>
@endpush

@push('scripts')
<script src="/js/sounds.js?v=8"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin       = '{{ $game->pin }}';
  const appKey    = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost    = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  let knownPlayers = new Set();
  let kickTarget   = null;

  // Start lobby music on first click
  function startMusic() {
    QB.Audio.init();
    QB.Audio.resume();
    QB.Audio.playLobbyMusic();
  }
  document.addEventListener('click', startMusic, { once: true });
  // Also try after short delay in case context unlocks on load
  setTimeout(() => { if (window.QB && QB.Audio) { QB.Audio.init(); } }, 500);
  QB.Audio.createControls();

  const confirmBox = document.getElementById('kick-confirm');
  const kickNameEl = document.getElementById('kick-name');

  document.getElementById('kick-yes').addEventListener('click', async () => {
    if (!kickTarget) return;
    confirmBox.style.display = 'none';
    try {
      await fetch(`/play/${pin}/kick/${kickTarget.id}`, {
        method:'DELETE', headers:{'X-CSRF-TOKEN':csrfToken,'Content-Type':'application/json'}
      });
      removeChip(kickTarget.id);
    } catch(e) {}
    kickTarget = null;
  });

  document.getElementById('kick-no').addEventListener('click', ()=>{ confirmBox.style.display='none'; kickTarget=null; });
  document.addEventListener('click', (e)=>{
    if(!confirmBox.contains(e.target)&&!e.target.classList.contains('player-chip')){ confirmBox.style.display='none'; kickTarget=null; }
  });

  function setIdle(el) {
    el.style.setProperty('--dur',(2.5+Math.random()*2).toFixed(1)+'s');
    el.style.setProperty('--delay',(Math.random()*2).toFixed(1)+'s');
    setTimeout(()=>el.classList.add('idle'),420);
  }

  function addChip(id, nickname) {
    if(knownPlayers.has(String(id))) return;
    knownPlayers.add(String(id));
    QB.Audio.sfx.playerJoin();
    const chip=document.createElement('div');
    chip.className='player-chip'; chip.textContent=nickname; chip.dataset.id=id;
    chip.addEventListener('click',(e)=>{
      kickTarget={id,nickname}; kickNameEl.textContent=nickname;
      confirmBox.style.display='block';
      confirmBox.style.left=(e.pageX-10)+'px'; confirmBox.style.top=(e.pageY+10)+'px';
      e.stopPropagation();
    });
    document.getElementById('player-grid').appendChild(chip);
    setIdle(chip);
    document.getElementById('launch-btn').removeAttribute('disabled');
  }

  function removeChip(id) {
    const el=document.querySelector(`.player-chip[data-id="${id}"]`);
    if(el){ el.style.transition='opacity .3s,transform .3s'; el.style.opacity='0'; el.style.transform='scale(.7)'; setTimeout(()=>el.remove(),300); }
    knownPlayers.delete(String(id));
  }

  @foreach($game->players->where('is_spectator', false) as $p)
  addChip({{ $p->id }}, '{{ addslashes($p->nickname) }}');
  @endforeach

  const pusher = new Pusher(appKey, {
    wsHost, wsPort:443, wssPort:443, forceTLS:true, enabledTransports:['ws','wss'], disableStats:true, cluster:'mt1'
  });
  pusher.connection.bind('connected',()=>{ document.getElementById('ws-dot').style.background='var(--qb-green)'; });

  const channel = pusher.subscribe('game.' + pin);
  channel.bind('player-joined',function(data){ document.getElementById('count-num').textContent=data.count; addChip(data.id,data.nickname); });
  channel.bind('player-left',function(data){
    document.getElementById('count-num').textContent=data.count;
    const ids=new Set(data.players.map(p=>String(p.id)));
    [...knownPlayers].forEach(id=>{ if(!ids.has(id)) removeChip(id); });
    if(data.count===0) document.getElementById('launch-btn').setAttribute('disabled',true);
  });
  channel.bind('player-kicked',function(data){
    document.getElementById('count-num').textContent=data.count;
    removeChip(data.player_id);
    if(data.count===0) document.getElementById('launch-btn').setAttribute('disabled',true);
  });

  setInterval(async()=>{
    if(pusher.connection.state!=='connected'){
      try{
        const res=await fetch('/api/game/'+pin+'/players'); const data=await res.json();
        document.getElementById('count-num').textContent=data.count;
        data.players.forEach(p=>addChip(p.id,p.nickname));
        if(data.count>0) document.getElementById('launch-btn').removeAttribute('disabled');
      }catch(e){}
    }
  },3000);
})();
</script>
@endpush
