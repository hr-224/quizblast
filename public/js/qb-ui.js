/* QuizBlast shared UI: Calm/Party effects switch and sound mute.
   ES5 on purpose (old school iPads). Every storage call is guarded — storage may be blocked. */
(function () {
  var root = document.documentElement;

  function get(key) {
    try { return window.localStorage.getItem(key); } catch (e) { return null; }
  }
  function set(key, val) {
    try { window.localStorage.setItem(key, val); } catch (e) {}
  }

  function fx() {
    return root.getAttribute('data-fx') === 'calm' ? 'calm' : 'party';
  }

  function renderFx() {
    var party = fx() === 'party';
    var label = party ? 'Party effects on. Switch to calm mode.' : 'Calm mode on. Switch to party effects.';
    var btns = document.querySelectorAll('[data-fx-toggle]');
    for (var i = 0; i < btns.length; i++) {
      btns[i].setAttribute('aria-pressed', party ? 'true' : 'false');
      btns[i].setAttribute('title', label);
      btns[i].textContent = party ? '✨' : '🌙';
    }
  }

  function setFx(next) {
    next = next === 'calm' ? 'calm' : 'party';
    root.setAttribute('data-fx', next);
    set('qb-fx', next);
    renderFx();
    document.dispatchEvent(new CustomEvent('qb:fx', { detail: { fx: next } }));
  }

  function muted() {
    return get('qb-muted') === '1';
  }

  function renderMute() {
    var m = muted();
    var label = m ? 'Sound is muted. Unmute.' : 'Sound is on. Mute.';
    var btns = document.querySelectorAll('[data-mute-toggle]');
    for (var i = 0; i < btns.length; i++) {
      btns[i].setAttribute('aria-pressed', m ? 'false' : 'true'); // pressed = sound is on
      btns[i].setAttribute('title', label);
      btns[i].textContent = m ? '🔇' : '🔊';
    }
  }

  function setMuted(m) {
    set('qb-muted', m ? '1' : '0');
    if (window.QB && window.QB.Audio && window.QB.Audio.setMuted) window.QB.Audio.setMuted(m);
    renderMute();
  }

  document.addEventListener('click', function (e) {
    var el = e.target;
    while (el && el !== document) {
      if (el.hasAttribute && el.hasAttribute('data-fx-toggle')) { setFx(fx() === 'party' ? 'calm' : 'party'); return; }
      if (el.hasAttribute && el.hasAttribute('data-mute-toggle')) { setMuted(!muted()); return; }
      el = el.parentNode;
    }
  });

  renderFx();
  renderMute();

  window.QB = window.QB || {};
  window.QB.UI = { fx: fx, setFx: setFx, muted: muted, setMuted: setMuted };
})();
