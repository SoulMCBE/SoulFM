<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$listeners     = getListenerCount();
$pendingCount  = getPendingRequestsCount();
$newsCount     = getNewsCount();
$scheduleCount = getScheduleCount();
$userCount     = getUserCount();
$recentReqs    = getRecentRequests(10);
$currentProg   = getCurrentProgram();

// Sollicitaties teller (gefilterd op wat deze gebruiker mag zien)
$visibleDepts  = getVisibleApplicationDepartments();
$newAppsCount  = (hasPermission('view_applications') || $_SESSION['user_role'] === 'admin')
                 ? getNewApplicationsCount($visibleDepts)
                 : 0;

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $listeners ?></div>
      <div class="stat-label">Luisteraars nu</div>
      <div class="stat-change">↑ Live stream</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon orange" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $pendingCount ?></div>
      <div class="stat-label">Openstaande verzoekjes</div>
      <div class="stat-change" style="color:<?= $pendingCount > 0 ? '#f59e0b' : 'var(--success)' ?>">
        <?= $pendingCount > 0 ? '↑ Wacht op actie' : '✓ Niets te doen' ?>
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $newsCount ?></div>
      <div class="stat-label">Gepubliceerde artikelen</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= $scheduleCount ?></div>
      <div class="stat-label">Planningslots</div>
    </div>
  </div>
</div>

<?php if ($newAppsCount > 0): ?>
<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem">
  <span>
    <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;display:inline;vertical-align:middle;margin-right:.4rem"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
    <strong><?= $newAppsCount ?></strong> nieuwe sollicitatie<?= $newAppsCount !== 1 ? 's' : '' ?> wacht<?= $newAppsCount === 1 ? '' : 'en' ?> op beoordeling.
  </span>
  <a href="<?= BASE_URL ?>/admin/applications.php?status=new" class="btn btn-primary btn-sm">Bekijken</a>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">

  <!-- Recent requests -->
  <div class="table-container">
    <div class="table-header">
      <span class="table-title">Recente Verzoekjes</span>
      <a href="<?= BASE_URL ?>/admin/requests.php" class="btn btn-secondary btn-sm">Alle verzoekjes</a>
    </div>
    <div class="table-wrapper">
      <table aria-label="Recente verzoekjes">
        <thead>
          <tr>
            <th>Nummer</th>
            <th>Van</th>
            <th>Tijd</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($recentReqs): foreach ($recentReqs as $req): ?>
          <tr>
            <td data-label="Nummer">
              <div class="td-primary"><?= htmlspecialchars($req['song_title']) ?></div>
              <div class="td-muted"><?= htmlspecialchars($req['artist_name']) ?></div>
            </td>
            <td data-label="Van" class="td-muted"><?= htmlspecialchars($req['requester_name']) ?></td>
            <td data-label="Tijd" class="td-muted"><?= formatRelativeDate($req['created_at']) ?></td>
            <td data-label="Status">
              <span class="badge badge-<?= $req['status'] ?>">
                <?php $labels = ['pending'=>'Wacht','played'=>'Gespeeld','rejected'=>'Afgewezen']; ?>
                <?= $labels[$req['status']] ?? $req['status'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="4" style="text-align:center;color:var(--text-dim);padding:2rem">Geen verzoekjes gevonden.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right panel -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">

    <!-- Now on air -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
          Nu op de lucht
        </span>
        <span style="width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;animation:blink 1.2s infinite" aria-hidden="true"></span>
      </div>
      <div class="section-card-body" style="text-align:center">
        <?php if ($currentProg): ?>
        <div style="font-size:1.25rem;font-weight:700;color:var(--white);margin-bottom:0.25rem"><?= htmlspecialchars($currentProg['program_name']) ?></div>
        <div style="color:var(--accent);font-size:.88rem;margin-bottom:.5rem"><?= htmlspecialchars($currentProg['dj_name']) ?></div>
        <div style="font-size:.8rem;color:var(--text-dim)"><?= formatTime($currentProg['start_time']) ?> – <?= formatTime($currentProg['end_time']) ?></div>
        <span class="badge badge-played" style="margin-top:.75rem">LIVE</span>
        <?php else: ?>
        <div style="color:var(--text-dim)">Geen actief programma</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">Snelle acties</span>
      </div>
      <div class="section-card-body" style="display:flex;flex-direction:column;gap:.65rem">
        <?php if (hasPermission('manage_news')): ?>
        <a href="<?= BASE_URL ?>/admin/news-edit.php" class="btn btn-secondary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          Nieuw artikel
        </a>
        <?php endif; ?>
        <?php if (hasPermission('manage_schedule')): ?>
        <a href="<?= BASE_URL ?>/admin/schedule.php" class="btn btn-secondary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
          Planning beheren
        </a>
        <?php endif; ?>
        <?php if (hasPermission('view_requests')): ?>
        <a href="<?= BASE_URL ?>/admin/requests.php?status=pending" class="btn btn-secondary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
          Verzoekjes bekijken
          <?php if ($pendingCount > 0): ?>
          <span style="background:var(--danger);color:white;border-radius:10px;padding:.1rem .45rem;font-size:.7rem;font-weight:700"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('manage_content')): ?>
        <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-secondary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
          Instellingen
        </a>
        <?php endif; ?>
        <?php if (hasPermission('view_applications')): ?>
        <a href="<?= BASE_URL ?>/admin/applications.php" class="btn btn-secondary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15h8v2H8zm0-4h8v2H8zm0-4h5v2H8z"/></svg>
          Sollicitaties
          <?php if ($newAppsCount > 0): ?>
          <span style="background:var(--danger);color:white;border-radius:10px;padding:.1rem .45rem;font-size:.7rem;font-weight:700"><?= $newAppsCount ?></span>
          <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('view_own_mail')): ?>
        <a href="<?= BASE_URL ?>/admin/mijn-email.php" class="btn btn-secondary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
          Mijn Bedrijfsmail
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }
</style>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
