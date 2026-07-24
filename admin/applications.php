<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('view_applications')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle  = 'Sollicitaties';
$activePage = 'applications';
$csrf       = generateCsrfToken();
$msg        = '';
$pdo        = getPDO();

// Welke afdelingen mag deze gebruiker zien?
$visibleDepts = getVisibleApplicationDepartments(); // leeg = alles

// Handle POST acties
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $appId  = (int)($_POST['app_id'] ?? 0);

    if ($appId && hasPermission('manage_applications')) {
        // Controleer dat hoofd alleen eigen afdeling mag beheren
        $app = getApplication($appId);
        $allowed = empty($visibleDepts) || in_array($app['department'] ?? '', $visibleDepts);

        if ($app && $allowed) {
            if ($action === 'update_status') {
                $newStatus = $_POST['status'] ?? '';
                if (in_array($newStatus, ['new','in_review','accepted','rejected'])) {
                    $pdo->prepare('UPDATE applications SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?')
                        ->execute([$newStatus, $_SESSION['user_id'], $appId]);
                    $msg = 'Status bijgewerkt.';
                }
            } elseif ($action === 'save_notes') {
                $notes = sanitize($_POST['notes'] ?? '');
                $pdo->prepare('UPDATE applications SET notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?')
                    ->execute([$notes, $_SESSION['user_id'], $appId]);
                $msg = 'Notitie opgeslagen.';
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM applications WHERE id=?')->execute([$appId]);
                $msg = 'Sollicitatie verwijderd.';
            }
        }
    }
}

// Filters
$filterStatus = $_GET['status']     ?? 'all';
$filterDept   = $_GET['department'] ?? 'all';

// Bouw afdeling-filter op
$deptFilter = $visibleDepts; // hoofd: geforceerd beperkt
if (empty($deptFilter) && $filterDept !== 'all') {
    $deptFilter = [$filterDept]; // admin kiest zelf
}

$applications = getApplications($deptFilter, $filterStatus);
$departments  = getDepartments();

// Statistieken per afdeling
$statsQuery = empty($visibleDepts)
    ? $pdo->query("SELECT department, status, COUNT(*) as c FROM applications GROUP BY department, status")
    : (function() use ($pdo, $visibleDepts) {
        $pl = implode(',', array_fill(0, count($visibleDepts), '?'));
        $s  = $pdo->prepare("SELECT department, status, COUNT(*) as c FROM applications WHERE department IN ($pl) GROUP BY department, status");
        $s->execute($visibleDepts);
        return $s;
      })();

$stats = [];
foreach ($statsQuery->fetchAll() as $row) {
    $stats[$row['department']][$row['status']] = $row['c'];
}

$totalNew = array_sum(array_column(array_map(fn($d) => ['c' => $d['new'] ?? 0], $stats), 'c'));

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Sollicitaties</h1>
    <p>
      <?php if (!empty($visibleDepts)): ?>
        Sollicitaties voor jouw afdeling: <strong style="color:var(--accent)"><?= htmlspecialchars(implode(', ', array_map('ucfirst', $visibleDepts))) ?></strong>
      <?php else: ?>
        Overzicht van alle inkomende sollicitaties.
      <?php endif; ?>
    </p>
  </div>
  <a href="<?= BASE_URL ?>/solliciteer.php" target="_blank" class="btn btn-secondary">
    <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
    Sollicitatiepagina bekijken
  </a>
</div>

<!-- Stats per afdeling -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:2rem">
  <?php
  $deptList = empty($visibleDepts) ? $departments : array_filter($departments, fn($d) => in_array($d['slug'], $visibleDepts));
  foreach ($deptList as $dept):
    $dStat   = $stats[$dept['slug']] ?? [];
    $dTotal  = array_sum($dStat);
    $dNew    = $dStat['new'] ?? 0;
  ?>
  <div class="stat-card" style="flex-direction:column;gap:.5rem;padding:1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-dim)"><?= htmlspecialchars($dept['name']) ?></div>
    <div style="font-size:1.6rem;font-weight:700;color:var(--white)"><?= $dTotal ?></div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <?php if ($dNew): ?><span class="badge badge-pending"><?= $dNew ?> nieuw</span><?php endif; ?>
      <?php if ($dStat['in_review'] ?? 0): ?><span class="badge badge-dj"><?= $dStat['in_review'] ?> actief</span><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="table-container">
  <div class="table-header" style="flex-wrap:wrap;gap:.75rem">
    <div class="filter-bar">
      <?php foreach(['all'=>'Alle','new'=>'Nieuw','in_review'=>'In behandeling','accepted'=>'Geaccepteerd','rejected'=>'Afgewezen'] as $val => $lbl): ?>
      <a href="?status=<?= $val ?>&department=<?= urlencode($filterDept) ?>" class="filter-btn <?= $filterStatus===$val?'active':'' ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($visibleDepts)): // Admin kan ook op afdeling filteren ?>
    <select onchange="location.href='?status=<?= urlencode($filterStatus) ?>&department='+this.value" class="form-control" style="width:auto;padding:.4rem .8rem;font-size:.83rem">
      <option value="all" <?= $filterDept==='all'?'selected':'' ?>>Alle afdelingen</option>
      <?php foreach ($departments as $dept): ?>
      <option value="<?= htmlspecialchars($dept['slug']) ?>" <?= $filterDept===$dept['slug']?'selected':'' ?>><?= htmlspecialchars($dept['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <div class="search-input">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" placeholder="Zoeken op naam..." data-search-table="applications-table">
    </div>
  </div>

  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div class="table-wrapper">
    <table id="applications-table" aria-label="Sollicitaties">
      <thead>
        <tr>
          <th>Naam</th>
          <th>Afdeling</th>
          <th>Beschikbaar</th>
          <th>Datum</th>
          <th>Status</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($applications): foreach ($applications as $app): ?>
        <tr data-status="<?= $app['status'] ?>">
          <td data-label="Naam">
            <div class="td-primary"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($app['email']) ?></div>
            <?php if ($app['city']): ?><div class="td-muted"><?= htmlspecialchars($app['city']) ?></div><?php endif; ?>
          </td>
          <td data-label="Afdeling">
            <?php $dept = getDepartment($app['department']); ?>
            <span class="badge badge-dj"><?= htmlspecialchars($dept['name'] ?? $app['department']) ?></span>
          </td>
          <td data-label="Beschikbaar" class="td-muted"><?= htmlspecialchars($app['availability'] ?? '—') ?></td>
          <td data-label="Datum" class="td-muted"><?= formatRelativeDate($app['created_at']) ?></td>
          <td data-label="Status">
            <span class="badge badge-app-<?= $app['status'] ?>" style="<?php
              $sc = ['new'=>'background:rgba(0,180,216,.15);color:#48cae4;border:1px solid rgba(0,180,216,.25)',
                     'in_review'=>'background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.25)',
                     'accepted'=>'background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.25)',
                     'rejected'=>'background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.25)'];
              echo $sc[$app['status']] ?? '';
            ?>">
              <?= applicationStatusLabel($app['status']) ?>
            </span>
          </td>
          <td data-label="Acties">
            <div class="action-btns">
              <button class="btn-icon view" title="Details bekijken" aria-label="Bekijk sollicitatie"
                data-modal-open="app-detail-<?= $app['id'] ?>">
                <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              </button>
              <?php if (hasPermission('manage_applications')): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                <button type="submit" class="btn-icon del" title="Verwijderen"
                  data-confirm-delete="Sollicitatie van <?= htmlspecialchars(addslashes($app['first_name'] . ' ' . $app['last_name'])) ?> verwijderen?"
                  aria-label="Verwijder sollicitatie">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>

        <!-- Detail Modal voor elke sollicitatie -->
        <div class="modal-overlay" id="app-detail-<?= $app['id'] ?>" role="dialog" aria-modal="true" aria-label="Sollicitatie details">
          <div class="modal" style="max-width:680px">
            <div class="modal-header">
              <span class="modal-title">
                <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                — <?= htmlspecialchars($dept['name'] ?? $app['department']) ?>
              </span>
              <button class="modal-close" data-modal-close="app-detail-<?= $app['id'] ?>" aria-label="Sluiten">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
            </div>
            <div class="modal-body" style="display:grid;gap:1.25rem">

              <!-- Contactgegevens -->
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;font-size:.88rem">
                <div><span style="color:var(--text-dim);display:block;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">E-mail</span>
                  <a href="mailto:<?= htmlspecialchars($app['email']) ?>" style="color:var(--accent)"><?= htmlspecialchars($app['email']) ?></a>
                </div>
                <?php if ($app['phone']): ?>
                <div><span style="color:var(--text-dim);display:block;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">Telefoon</span>
                  <?= htmlspecialchars($app['phone']) ?>
                </div>
                <?php endif; ?>
                <?php if ($app['city']): ?>
                <div><span style="color:var(--text-dim);display:block;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">Woonplaats</span><?= htmlspecialchars($app['city']) ?></div>
                <?php endif; ?>
                <?php if ($app['birth_date']): ?>
                <div><span style="color:var(--text-dim);display:block;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">Geboortedatum</span><?= htmlspecialchars(date('d-m-Y', strtotime($app['birth_date']))) ?></div>
                <?php endif; ?>
                <?php if ($app['availability']): ?>
                <div><span style="color:var(--text-dim);display:block;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">Beschikbaarheid</span><?= htmlspecialchars($app['availability']) ?></div>
                <?php endif; ?>
                <?php if ($app['portfolio_url']): ?>
                <div style="grid-column:1/-1"><span style="color:var(--text-dim);display:block;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem">Portfolio / LinkedIn</span>
                  <a href="<?= htmlspecialchars($app['portfolio_url']) ?>" target="_blank" rel="noopener" style="color:var(--accent);word-break:break-all"><?= htmlspecialchars($app['portfolio_url']) ?></a>
                </div>
                <?php endif; ?>
              </div>

              <hr style="border:none;border-top:1px solid rgba(0,180,216,.1)">

              <div>
                <div style="font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);margin-bottom:.5rem">Motivatiebrief</div>
                <div style="font-size:.9rem;line-height:1.7;color:var(--text);background:rgba(0,0,0,.2);padding:1rem;border-radius:8px;white-space:pre-wrap"><?= htmlspecialchars($app['motivation']) ?></div>
              </div>

              <?php if ($app['experience']): ?>
              <div>
                <div style="font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);margin-bottom:.5rem">Ervaring</div>
                <div style="font-size:.9rem;line-height:1.7;color:var(--text);background:rgba(0,0,0,.2);padding:1rem;border-radius:8px;white-space:pre-wrap"><?= htmlspecialchars($app['experience']) ?></div>
              </div>
              <?php endif; ?>

              <hr style="border:none;border-top:1px solid rgba(0,180,216,.1)">

              <!-- Status & notities -->
              <?php if (hasPermission('manage_applications')): ?>
              <form method="POST" action="" style="display:grid;gap:1rem">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                  <div>
                    <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);display:block;margin-bottom:.4rem">Status wijzigen</label>
                    <div style="display:flex;gap:.5rem">
                      <select name="status" class="form-control" style="padding:.5rem .75rem;font-size:.85rem">
                        <option value="new"       <?= $app['status']==='new'?'selected':'' ?>>Nieuw</option>
                        <option value="in_review" <?= $app['status']==='in_review'?'selected':'' ?>>In behandeling</option>
                        <option value="accepted"  <?= $app['status']==='accepted'?'selected':'' ?>>Geaccepteerd</option>
                        <option value="rejected"  <?= $app['status']==='rejected'?'selected':'' ?>>Afgewezen</option>
                      </select>
                      <button type="submit" name="action" value="update_status" class="btn btn-primary btn-sm" style="white-space:nowrap">Opslaan</button>
                    </div>
                  </div>
                  <div>
                    <?php if ($app['reviewed_by']): ?>
                    <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);display:block;margin-bottom:.4rem">Beoordeeld door</label>
                    <div style="font-size:.85rem;color:var(--text)"><?= htmlspecialchars($app['reviewer_name'] ?? '—') ?></div>
                    <div style="font-size:.75rem;color:var(--text-dim)"><?= $app['reviewed_at'] ? formatRelativeDate($app['reviewed_at']) : '' ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div>
                  <label style="font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);display:block;margin-bottom:.4rem">Interne notities</label>
                  <textarea name="notes" class="form-control" rows="3" placeholder="Notities zichtbaar voor het team..."><?= htmlspecialchars($app['notes'] ?? '') ?></textarea>
                  <button type="submit" name="action" value="save_notes" class="btn btn-secondary btn-sm" style="margin-top:.5rem">Notitie opslaan</button>
                </div>
              </form>
              <?php else: ?>
              <?php if ($app['notes']): ?>
              <div>
                <div style="font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-dim);margin-bottom:.5rem">Notities</div>
                <div style="font-size:.88rem;color:var(--text);background:rgba(0,0,0,.2);padding:.75rem;border-radius:8px"><?= htmlspecialchars($app['notes']) ?></div>
              </div>
              <?php endif; ?>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <span style="font-size:.8rem;color:var(--text-dim)">Ontvangen: <?= formatDate($app['created_at']) ?></span>
              <button type="button" class="btn btn-ghost" data-modal-close="app-detail-<?= $app['id'] ?>">Sluiten</button>
            </div>
          </div>
        </div>

        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-dim);padding:3rem">
          Geen sollicitaties gevonden voor de huidige filters.
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
