/**
 * SoulFM Audio Player
 */
(function () {
  'use strict';

  const audio = new Audio();
  audio.preload = 'none';

  let isPlaying = false;
  let isMuted = false;
  let retryCount = 0;
  const MAX_RETRIES = 5;
  const RETRY_DELAY = 3000;

  // DOM refs
  const playBtn     = document.getElementById('player-play-btn');
  const playIcon    = document.getElementById('play-icon');
  const pauseIcon   = document.getElementById('pause-icon');
  const volSlider   = document.getElementById('volume-slider');
  const muteBtn     = document.getElementById('mute-btn');
  const volIcon     = document.getElementById('vol-icon');
  const muteIcon    = document.getElementById('mute-icon');
  const playerTitle = document.getElementById('player-title');
  const playerDj    = document.getElementById('player-dj');
  const playerSong  = document.getElementById('player-song'); // -> NIEUW: Referentie voor het liedje in de speler
  const playerEq    = document.getElementById('player-eq');
  const heroEq      = document.getElementById('hero-eq');
  const streamUrl   = document.getElementById('stream-url');

  if (!playBtn) return; // Player not on this page

  // Load stream URL from data attribute
  const url = streamUrl ? streamUrl.dataset.url : null;
  if (url) audio.src = url;

  // Restore saved volume
  const savedVol = parseFloat(localStorage.getItem('soulfm_volume') || '0.8');
  audio.volume = savedVol;
  if (volSlider) volSlider.value = savedVol;

  /** Play/Pause toggle */
  function togglePlay() {
    if (isPlaying) {
      pauseStream();
    } else {
      playStream();
    }
  }

  function playStream() {
    if (!audio.src && url) audio.src = url;
    audio.play().then(() => {
      setPlaying(true);
      retryCount = 0;
    }).catch(err => {
      console.warn('Playback error:', err);
      setPlaying(false);
    });
  }

  function pauseStream() {
    audio.pause();
    setPlaying(false);
  }

  function setPlaying(playing) {
    isPlaying = playing;
    if (playIcon)  playIcon.style.display  = playing ? 'none' : 'block';
    if (pauseIcon) pauseIcon.style.display = playing ? 'block' : 'none';
    if (playerEq) playerEq.classList.toggle('paused', !playing);
    if (heroEq)   heroEq.classList.toggle('paused', !playing);
  }

  /** Volume control */
  function setVolume(val) {
    const v = Math.max(0, Math.min(1, parseFloat(val)));
    audio.volume = v;
    audio.muted = false;
    isMuted = false;
    updateVolIcon(v);
    localStorage.setItem('soulfm_volume', v);
    if (volSlider) volSlider.value = v;
  }

  function toggleMute() {
    isMuted = !isMuted;
    audio.muted = isMuted;
    updateVolIcon(isMuted ? 0 : audio.volume);
  }

  function updateVolIcon(vol) {
    if (!volIcon || !muteIcon) return;
    if (vol === 0 || isMuted) {
      volIcon.style.display  = 'none';
      muteIcon.style.display = 'block';
    } else {
      volIcon.style.display  = 'block';
      muteIcon.style.display = 'none';
    }
  }

  // Auto-retry on error
  audio.addEventListener('error', () => {
    if (isPlaying && retryCount < MAX_RETRIES) {
      retryCount++;
      console.log(`Stream error. Retrying (${retryCount}/${MAX_RETRIES})...`);
      setTimeout(() => {
        audio.src = url + '?t=' + Date.now();
        audio.play().catch(() => {});
      }, RETRY_DELAY);
    } else {
      setPlaying(false);
    }
  });

  audio.addEventListener('ended', () => {
    // Live stream shouldn't end, retry if it does
    if (isPlaying) {
      setTimeout(playStream, RETRY_DELAY);
    }
  });

  // Event listeners
  if (playBtn) playBtn.addEventListener('click', togglePlay);
  if (volSlider) volSlider.addEventListener('input', e => setVolume(e.target.value));
  if (muteBtn) muteBtn.addEventListener('click', toggleMute);

  // Init volume icon
  updateVolIcon(savedVol);

  /** Fetch now-playing info every 30 seconds */
  function fetchNowPlaying() {
    const base = document.querySelector('meta[name="base-url"]')?.content || '';
    fetch(base + '/api/now-playing.php')
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        if (!data) return;

        // Bepaal de tekst voor het liedje op basis van veelvoorkomende API-velden
        const songText = data.song || data.track || data.now_playing || 
                         (data.artist && data.title ? `${data.artist} - ${data.title}` : data.title) || '';

        // Update speler
        if (playerTitle && data.program_name) playerTitle.textContent = data.program_name;
        if (playerDj && data.dj_name) playerDj.textContent = data.dj_name;
        if (playerSong && songText) playerSong.textContent = songText; // -> NIEUW: Update liedje in speler

        // Update hero widget
        const heroProgram = document.getElementById('hero-program');
        const heroDj      = document.getElementById('hero-dj');
        const heroTime    = document.getElementById('hero-time');
        const heroSong    = document.getElementById('hero-song'); // -> NIEUW: Referentie voor liedje in hero

        if (heroProgram && data.program_name) heroProgram.textContent = data.program_name;
        if (heroDj      && data.dj_name)      heroDj.textContent = 'met ' + data.dj_name;
        if (heroSong    && songText)          heroSong.textContent = songText; // -> NIEUW: Update liedje in hero
        if (heroTime    && data.start_time && data.end_time) {
          heroTime.textContent = data.start_time.substring(0,5) + ' - ' + data.end_time.substring(0,5);
        }
      })
      .catch(() => {});
  }

  fetchNowPlaying();
  setInterval(fetchNowPlaying, 30000);

})();