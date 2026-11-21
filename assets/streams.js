(() => {
  const root = document.querySelector('[data-nta-stream]');
  if (!root) return;
  const poster = root.querySelector('[data-stream-poster]');
  const image = root.querySelector('[data-stream-image]');
  const status = root.querySelector('[data-stream-status]');
  const counter = root.querySelector('[data-stream-countdown]');
  const message = root.querySelector('[data-stream-message]');
  const refresh = root.querySelector('[data-stream-refresh]');
  const feedback = root.querySelector('[data-stream-feedback]');
  const player = root.querySelector('[data-stream-player]');
  let event = null, offset = 0, playing = '', timer, inFlight = false;
  const labels = {countdown: 'Scheduled Stream', waiting: 'Starting soon', live: 'Live now', ended: 'This gathering has ended.', empty: 'Join our next gathering.'};
  function tick() {
    if (!event || !['countdown', 'waiting'].includes(event.state)) return;
    const seconds = Math.max(0, Math.ceil(event.start - (Date.now() / 1000 + offset)));
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor(seconds % 86400 / 3600);
    const minutes = Math.floor(seconds % 3600 / 60);
    counter.textContent = `${days ? String(days).padStart(2, '0') + 'd ' : ''}${[hours, minutes, seconds % 60].map(v => String(v).padStart(2, '0')).join(':')}`;
    if (seconds === 0) {
      status.textContent = labels.waiting;
      message.textContent = 'We’ll begin here as soon as the broadcast is live.';
    }
  }
  function render(next) {
    // Do not interrupt a confirmed live player during a temporary YouTube API failure.
    if (event?.state === 'live' && next.id === event.id && next.confirmed === false) next.state = 'live';
    event = next;
    offset = next.serverTime - Date.now() / 1000;
    const live = next.state === 'live';
    root.dataset.streamState = next.state;
    status.textContent = labels[next.state] || labels.waiting;
    message.hidden = ['countdown', 'waiting'].includes(next.state);
    counter.hidden = !['countdown', 'waiting'].includes(next.state);
    poster.hidden = live;
    refresh.hidden = live || next.state === 'ended';
    player.hidden = !live;
    if (next.thumbnail) { image.src = next.thumbnail; image.hidden = false; } else { image.hidden = true; image.removeAttribute('src'); }
    message.textContent = next.state === 'ended' ? 'Continue studying and reflecting through our messages and teaching notes.' : next.state === 'empty' ? 'See our meeting schedule below, or explore past messages.' : next.state === 'live' ? 'You’re joining live. Use the player controls to enable sound or enter fullscreen.' : 'Gather with us for worship, the Word, and prayer.';
    if (live && playing !== next.video) {
      const frame = document.createElement('iframe');
      frame.src = `https://www.youtube.com/embed/${encodeURIComponent(next.video)}?autoplay=1&mute=1&playsinline=1&controls=1&fs=1&rel=0`;
      frame.title = next.title || 'New Tribes Assembly live broadcast';
      frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      frame.allowFullscreen = true;
      frame.referrerPolicy = 'strict-origin-when-cross-origin';
      player.replaceChildren(frame);
      playing = next.video;
    } else if (!live && playing) { player.replaceChildren(); playing = ''; }
    tick();
  }
  async function poll(force = false) {
    if (inFlight) { if (force) feedback.textContent = 'A check is already running. Please try again in a moment.'; return; }
    inFlight = true;
    if (force) { refresh.disabled = true; refresh.textContent = 'Checking…'; feedback.textContent = 'Looking for the live broadcast…'; }
    try {
      const response = await fetch(force ? root.dataset.refreshEndpoint : root.dataset.endpoint, {method: force ? 'POST' : 'GET', cache: 'no-store', credentials: 'same-origin', headers: {Accept: 'application/json'}});
      if (response.ok) {
        const next = await response.json();
        if (['empty', 'countdown', 'waiting', 'live', 'ended'].includes(next.state)) render(next);
        if (force) feedback.textContent = next.refreshNotice || 'Check complete.';
      }
      else if (force) feedback.textContent = 'The check could not be completed. Please try again.';
    } catch (_) { if (force) feedback.textContent = 'Connection interrupted. Please try again.'; }
    if (force) { refresh.textContent = 'Check for live stream ↻'; setTimeout(() => { refresh.disabled = false; }, 60000); }
    inFlight = false;
    clearTimeout(timer);
    timer = setTimeout(() => poll(), document.hidden ? 60000 : 30000);
  }
  try { render(JSON.parse(root.dataset.initial)); } catch (_) { /* Poll supplies fresh state if initial markup is unavailable. */ }
  setInterval(tick, 1000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) { clearTimeout(timer); poll(); } });
  refresh.addEventListener('click', () => poll(true));
  poll();
})();
