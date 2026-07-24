<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'Home';
$activePage  = 'home';
$settings    = getSettings();
$currentProg = getCurrentProgram();
$latestNews  = getLatestNews(3);
$listeners   = getListenerCount();

$days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
$today = $days[date('w')];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO ===== -->
<main id="main-content">
<section class="hero" aria-label="Welkom bij SoulFM">
  <div class="container">
    <div class="hero-content">
      <div class="hero-text">
        <div class="hero-badge">
          <span class="live-dot" aria-hidden="true"></span>
          Nu live op de lucht
        </div>
        <h1 class="hero-title">
          Jouw <span class="accent">Soul Music</span><br>
          Radiozender
        </h1>
        <p class="hero-desc">
          Luister 24/7 naar de beste soul, R&amp;B, jazz en blues. Doe een verzoekje en maak verbinding met de muziek die je raakt.
        </p>
        <div class="hero-actions">
          <a href="#" id="hero-play-trigger" class="btn btn-primary btn-lg" onclick="document.getElementById('player-play-btn').click(); return false;">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor"><path d="M8 5v14l11-7z"/></svg>
            Luister Live
          </a>
          <a href="<?= BASE_URL ?>/schedule.php" class="btn btn-outline btn-lg">Programmaschema</a>
        </div>
      </div>

      <!-- Now Playing Widget -->
      <div class="now-playing-card" aria-label="Nu aan het spelen">
        <div class="np-label">
          <span class="live-dot" aria-hidden="true"></span>
          Nu op de lucht
        </div>
        <div class="np-album" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
        </div>
        <div class="np-program" id="hero-program"><?= htmlspecialchars($currentProg['program_name'] ?? 'SoulFM Live') ?></div>
        <div class="np-dj" id="hero-dj">met <?= htmlspecialchars($currentProg['dj_name'] ?? 'Autopilot') ?></div>
        <div class="np-time" id="hero-time">
          <?php if ($currentProg): ?>
            <?= formatTime($currentProg['start_time']) ?> – <?= formatTime($currentProg['end_time']) ?>
          <?php else: ?>
            24/7 Soul Muziek
          <?php endif; ?>
        </div>
        <?php if ($currentProg && !empty($currentProg['genre'])): ?>
          <span class="genre-badge"><?= htmlspecialchars($currentProg['genre']) ?></span>
        <?php endif; ?>
        <div class="equalizer paused" id="hero-eq" aria-hidden="true" style="margin-top:1.25rem">
          <div class="equalizer-bar"></div>
          <div class="equalizer-bar"></div>
          <div class="equalizer-bar"></div>
          <div class="equalizer-bar"></div>
          <div class="equalizer-bar"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="hero-waves" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" preserveAspectRatio="none">
      <path d="M0,64L60,58.7C120,53,240,43,360,48C480,53,600,75,720,80C840,85,960,75,1080,64C1200,53,1320,43,1380,37.3L1440,32L1440,120L1380,120C1320,120,1200,120,1080,120C960,120,840,120,720,120C600,120,480,120,360,120C240,120,120,120,60,120L0,120Z" fill="#0a1628" opacity="0.6"/>
    </svg>
  </div>
</section>

<!-- ===== STATS ===== -->
<section class="section-sm" style="background:rgba(30,58,95,0.15);border-top:1px solid rgba(0,180,216,0.08);border-bottom:1px solid rgba(0,180,216,0.08)">
  <div class="container">
    <div class="stats-bar" role="list">
      <div class="stat-item" role="listitem">
        <div class="stat-value" id="listener-count"><?= $listeners ?></div>
        <div class="stat-label">Luisteraars nu</div>
      </div>
      <div class="stat-item" role="listitem">
        <div class="stat-value">24/7</div>
        <div class="stat-label">Non-stop muziek</div>
      </div>
      <div class="stat-item" role="listitem">
        <div class="stat-value"><?= getScheduleCount() ?></div>
        <div class="stat-label">Programma's</div>
      </div>
      <div class="stat-item" role="listitem">
        <div class="stat-value"><?= getPendingRequestsCount() ?></div>
        <div class="stat-label">Openstaande verzoekjes</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== TODAY'S SCHEDULE PREVIEW ===== -->
<section class="section">
  <div class="container">
    <span class="section-subtitle">Vandaag</span>
    <h2 class="section-title">Vandaag op <?= htmlspecialchars($settings['site_name'] ?? 'SoulFM') ?></h2>
    <p style="color:var(--color-text-dim);margin-bottom:2.5rem;max-width:520px">Bekijk wat er vandaag op het programma staat en mis geen enkel moment.</p>

    <?php
    $todaySchedule = getScheduleForDay($today);
    if ($todaySchedule):
    ?>
    <div class="schedule-list" style="max-width:700px">
      <?php foreach (array_slice($todaySchedule, 0, 5) as $slot):
        $nowH = (int)date('H');
        $nowM = (int)date('i');
        $nowSec = $nowH*3600 + $nowM*60 + (int)date('s');
        $startParts = explode(':', $slot['start_time']);
        $endParts   = explode(':', $slot['end_time']);
        $startSec = (int)$startParts[0]*3600 + (int)$startParts[1]*60;
        $endSec   = (int)$endParts[0]*3600   + (int)$endParts[1]*60;
        $isCurrent = ($startSec <= $nowSec && ($endSec > $nowSec || $endSec < $startSec));
      ?>
      <div class="schedule-item <?= $isCurrent ? 'now-playing' : '' ?>">
        <div class="schedule-time"><?= formatTime($slot['start_time']) ?></div>
        <div>
          <div class="schedule-program"><?= htmlspecialchars($slot['program_name']) ?></div>
          <div class="schedule-dj"><?= htmlspecialchars($slot['dj_name']) ?></div>
        </div>
        <span class="genre-badge"><?= htmlspecialchars($slot['genre']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:var(--color-text-dim)">Geen programma beschikbaar voor vandaag.</p>
    <?php endif; ?>

    <div style="margin-top:2rem">
      <a href="<?= BASE_URL ?>/schedule.php" class="btn btn-outline">Volledig programmaschema</a>
    </div>
  </div>
</section>

<!-- ===== LATEST NEWS ===== -->
<?php if ($latestNews): ?>
<section class="section" style="background:rgba(30,58,95,0.1);border-top:1px solid rgba(0,180,216,0.07)">
  <div class="container">
    <span class="section-subtitle">Nieuws</span>
    <h2 class="section-title">Laatste Nieuws</h2>
    <p style="color:var(--color-text-dim);margin-bottom:2.5rem;max-width:520px">Blijf op de hoogte van het laatste nieuws, nieuwe programma's en aankondigingen.</p>

    <div class="news-grid">
      <?php foreach ($latestNews as $article): ?>
      <article class="card">
        <div class="card-img">
          <?php if (!empty($article['image'])): ?>
            <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="card-img-placeholder">
              <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="card-meta">
            <span><?= formatDate($article['created_at']) ?></span>
            <span>door <?= htmlspecialchars($article['author_name'] ?? 'Redactie') ?></span>
          </div>
          <h3 class="card-title"><a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
          <p class="card-excerpt"><?= htmlspecialchars(truncate($article['excerpt'] ?? $article['content'], 130)) ?></p>
          <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($article['slug']) ?>" class="btn btn-outline btn-sm">Lees meer</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:3rem">
      <a href="<?= BASE_URL ?>/news.php" class="btn btn-outline">Alle nieuws bekijken</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== REQUEST CTA ===== -->
<section class="section">
  <div class="container" style="text-align:center">
    <span class="section-subtitle">Verzoekjes</span>
    <h2 class="section-title">Jouw nummer op de radio?</h2>
    <p style="color:var(--color-text-dim);max-width:480px;margin:0 auto 2.5rem">Stuur ons jouw verzoekje en we doen ons best om het zo snel mogelijk te draaien!</p>
    <a href="<?= BASE_URL ?>/request.php" class="btn btn-primary btn-lg">
      <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
      Doe een verzoekje
    </a>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
