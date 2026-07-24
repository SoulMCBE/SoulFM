<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Programmaschema';
$activePage = 'schedule';
$allSchedule = getAllSchedule();
$today = getTodayDayName();

$dayOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Programmaschema</span></div>
    <h1>Programmaschema</h1>
    <p>Bekijk wanneer jouw favoriete programma's en DJ's op de lucht zijn. Mis nooit meer een uitzending!</p>
  </div>
</div>

<section class="section">
  <div class="container">

    <!-- Day tabs -->
    <div class="schedule-tabs" role="tablist" aria-label="Dagen van de week">
      <?php foreach ($dayOrder as $day): ?>
      <button
        class="tab-btn <?= $day === $today ? 'today active' : '' ?> <?= $day !== $today && array_search($day, $dayOrder) < array_search($today, $dayOrder) ? '' : '' ?>"
        data-tab="<?= $day ?>"
        role="tab"
        aria-selected="<?= $day === $today ? 'true' : 'false' ?>"
        aria-controls="tab-<?= $day ?>">
        <?= dutchDayName($day) ?>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Tab contents -->
    <?php foreach ($dayOrder as $day):
      $slots = $allSchedule[$day] ?? [];
      $nowH = (int)date('H');
      $nowM = (int)date('i');
      $nowSec = $nowH*3600 + $nowM*60 + (int)date('s');
    ?>
    <div id="tab-<?= $day ?>" class="tab-content <?= $day === $today ? 'active' : '' ?>" role="tabpanel" aria-label="<?= dutchDayName($day) ?>">
      <?php if ($slots): ?>
      <div class="schedule-list">
        <?php foreach ($slots as $slot):
          $startParts = explode(':', $slot['start_time']);
          $endParts   = explode(':', $slot['end_time']);
          $startSec = (int)$startParts[0]*3600 + (int)$startParts[1]*60;
          $endSec   = (int)$endParts[0]*3600   + (int)$endParts[1]*60;
          $isCurrent = ($day === $today) && ($startSec <= $nowSec && ($endSec > $nowSec || $endSec < $startSec));
        ?>
        <div class="schedule-item <?= $isCurrent ? 'now-playing' : '' ?>">
          <div class="schedule-time">
            <?= formatTime($slot['start_time']) ?>
            <?php if ($isCurrent): ?>
            <div style="font-size:0.68rem;font-weight:700;color:#ef4444;letter-spacing:1px;margin-top:2px">NU LIVE</div>
            <?php endif; ?>
          </div>
          <div>
            <div class="schedule-program"><?= htmlspecialchars($slot['program_name']) ?></div>
            <div class="schedule-dj">
              <?= htmlspecialchars($slot['dj_name']) ?>
              <?php if (!empty($slot['dj_bio'])): ?>
              <span style="display:block;font-size:0.8rem;margin-top:0.2rem;color:var(--color-text-dim)"><?= htmlspecialchars($slot['dj_bio']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem">
            <span class="genre-badge"><?= htmlspecialchars($slot['genre']) ?></span>
            <span style="font-size:0.75rem;color:var(--color-text-dim)"><?= formatTime($slot['start_time']) ?> – <?= formatTime($slot['end_time']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:3rem;color:var(--color-text-dim)">
        <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:currentColor;margin:0 auto 1rem;opacity:.4"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <p>Geen programma beschikbaar voor <?= dutchDayName($day) ?>.</p>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
