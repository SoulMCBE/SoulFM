<?php
/**
 * SoulFM - Public Site Footer + Audio Player
 */
$settings     = getSettings();
$siteName     = $settings['site_name']    ?? 'SoulFM';
$streamUrl    = $settings['stream_url']   ?? '';
$fbUrl        = $settings['facebook_url'] ?? '#';
$twUrl        = $settings['twitter_url']  ?? '#';
$igUrl        = $settings['instagram_url']?? '#';
$contactEmail = $settings['contact_email']?? '';
$currentProg  = getCurrentProgram();
?>

<!-- ===== FOOTER ===== -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
          <div class="logo-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 3C7.03 3 3 7.03 3 12s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm-1-11v8l6-4-6-4z"/></svg>
          </div>
          <div>
            <div class="logo-text"><?= htmlspecialchars(substr($siteName,0,-2)) ?><span><?= htmlspecialchars(substr($siteName,-2)) ?></span></div>
            <div class="logo-tagline"><?= htmlspecialchars($settings['tagline'] ?? '') ?></div>
          </div>
        </a>
        <p style="margin-top:1rem"><?= htmlspecialchars($settings['about_text'] ?? '') ?></p>
        <div class="social-links" style="margin-top:1.25rem">
          <?php if ($fbUrl && $fbUrl !== '#'): ?>
          <a href="<?= htmlspecialchars($fbUrl) ?>" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
            <svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($twUrl && $twUrl !== '#'): ?>
          <a href="<?= htmlspecialchars($twUrl) ?>" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Twitter/X">
            <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($igUrl && $igUrl !== '#'): ?>
          <a href="<?= htmlspecialchars($igUrl) ?>" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" fill="none" stroke="currentColor" stroke-width="2"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="currentColor" stroke-width="2"/></svg>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <h3 class="footer-heading">Navigatie</h3>
        <nav class="footer-links">
          <a href="<?= BASE_URL ?>/index.php">Home</a>
          <a href="<?= BASE_URL ?>/schedule.php">Programmaschema</a>
          <a href="<?= BASE_URL ?>/request.php">Verzoekje doen</a>
          <a href="<?= BASE_URL ?>/news.php">Nieuws</a>
          <a href="<?= BASE_URL ?>/contact.php">Contact</a>
          <a href="<?= BASE_URL ?>/solliciteer.php">Werken bij SoulFM</a>
        </nav>
      </div>

      <div>
        <h3 class="footer-heading">Muziek</h3>
        <nav class="footer-links">
          <a href="<?= BASE_URL ?>/schedule.php">Uitzendingen</a>
          <a href="<?= BASE_URL ?>/request.php">Nummer aanvragen</a>
          <a href="<?= BASE_URL ?>/news.php">Nieuws & Blog</a>
        </nav>
      </div>

      <div>
        <h3 class="footer-heading">Contact</h3>
        <div class="footer-links">
          <?php if ($contactEmail): ?>
          <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
          <?php endif; ?>
          <?php if (!empty($settings['contact_phone'])): ?>
          <a href="tel:<?= htmlspecialchars(preg_replace('/[^+\d]/', '', $settings['contact_phone'])) ?>"><?= htmlspecialchars($settings['contact_phone']) ?></a>
          <?php endif; ?>
          <?php if (!empty($settings['contact_address'])): ?>
          <span style="color:var(--color-text-dim);font-size:.88rem"><?= htmlspecialchars($settings['contact_address']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. Alle rechten voorbehouden.</p>
      <p>Gemaakt met ♫ voor soul muziek liefhebbers</p>
    </div>
  </div>
</footer>

<!-- ===== AUDIO PLAYER ===== -->
<div class="audio-player" role="region" aria-label="Audiospeler">
  <div class="player-info">
    <div class="player-album" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
    </div>
    <div class="player-meta">
      <div class="player-title" id="player-title"><?= htmlspecialchars($currentProg['program_name'] ?? 'SoulFM Live') ?></div>
      <div class="player-dj" id="player-dj"><?= htmlspecialchars($currentProg['dj_name'] ?? '') ?></div>
      
      <?php 
        // Haal direct bij het laden het huidige nummer op via PHP (als fallback/start)
        $currentSongInit = function_exists('getCurrentSong') ? getCurrentSong() : 'SoulFM - The Best Soul & Motown'; 
      ?>
      <div class="player-song" id="player-song" style="font-size:0.8rem; opacity:0.85; margin-top:2px; font-weight:normal;">
        <?= htmlspecialchars($currentSongInit) ?>
      </div>
    </div>
  </div>

  <div class="player-controls">
    <div class="player-equalizer paused" id="player-eq" aria-hidden="true">
      <div class="equalizer-bar"></div>
      <div class="equalizer-bar"></div>
      <div class="equalizer-bar"></div>
      <div class="equalizer-bar"></div>
      <div class="equalizer-bar"></div>
    </div>

    <button class="player-btn play-btn" id="player-play-btn" aria-label="Afspelen / Pauzeren">
      <svg id="play-icon" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
      <svg id="pause-icon" viewBox="0 0 24 24" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
    </button>
  </div>

  <div class="player-volume">
    <button id="mute-btn" aria-label="Geluid dempen" style="background:none;border:none;cursor:pointer;display:flex;align-items:center">
      <svg id="vol-icon" viewBox="0 0 24 24" style="width:18px;height:18px;fill:var(--color-text-dim)"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0014 7.97v8.05A4.5 4.5 0 0016.5 12z"/></svg>
      <svg id="mute-icon" viewBox="0 0 24 24" style="width:18px;height:18px;fill:var(--color-text-dim);display:none"><path d="M16.5 12A4.5 4.5 0 0014 7.97v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51A8.796 8.796 0 0021 12c0-4.28-3-7.86-7-8.77v2.06A7 7 0 0119 12zm-9.5 0c0-.25.07-.48.14-.71L7 7.66V7L5.59 5.59 4.18 7l4 4H4v4h3l5 5v-7.18l-2.46-2.46c-.02.04-.04.09-.04.14zm5.04 3.86l-1.5-1.5c-.4.23-.85.39-1.35.47V16.5c1.07-.26 2.03-.77 2.85-1.47l-.49-.49.49.32z"/></svg>
    </button>
    <input type="range" id="volume-slider" class="volume-slider" min="0" max="1" step="0.02" value="0.8" aria-label="Volume">
  </div>

  <div class="player-live-badge">
    <span class="dot" aria-hidden="true"></span>
    LIVE
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/player.js"></script>
</body>
</html>
