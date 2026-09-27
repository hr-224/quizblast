/* Loads the Pusher client asynchronously so a slow or unreachable CDN never
   blocks the rest of the page's script from running (a blocking <script src>
   can hang ALL script execution on the page — timers, polling, button
   handlers — until the request resolves or the browser's own connection
   timeout fires, which can be tens of seconds to minutes).
   Usage: QB.loadPusher(function (Pusher) { if (Pusher) { ... } else { ...fallback... } });
   The callback always fires — with the Pusher constructor, or null if the
   script didn't load within TIMEOUT_MS or failed to load at all. */
(function () {
  var SRC = 'https://js.pusher.com/8.2.0/pusher.min.js';
  var TIMEOUT_MS = 6000;
  var pending = [];
  var settled = false;
  var result = null;

  function notify() {
    settled = true;
    var callbacks = pending;
    pending = [];
    for (var i = 0; i < callbacks.length; i++) callbacks[i](result);
  }

  function start() {
    var script = document.createElement('script');
    script.src = SRC;
    script.async = true;
    var timer = setTimeout(function () {
      if (!settled) notify();
    }, TIMEOUT_MS);
    script.onload = function () {
      clearTimeout(timer);
      if (!settled) { result = window.Pusher || null; notify(); }
    };
    script.onerror = function () {
      clearTimeout(timer);
      if (!settled) { result = null; notify(); }
    };
    document.head.appendChild(script);
  }

  window.QB = window.QB || {};
  window.QB.loadPusher = function (callback) {
    if (settled) { callback(result); return; }
    pending.push(callback);
    if (pending.length === 1) start();
  };
})();
