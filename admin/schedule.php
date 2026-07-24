<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('manage_schedule')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle  = 'Planningsbeheer';
$activePage = 'schedule';
$csrf       = generateCsrfToken();
$msg        = '';
$pdo        = getPDO();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $day         = $_POST['day_of_week'] ?? '';
        $startTime   = $_POST['start_time'] ?? '';
        $endTime     = $_POST['end_time'] ?? '';
        $programName = trim($_POST['program_name'] ?? '');
        $djName      = trim($_POST['dj_name'] ?? '');
        $djBio       = trim($_POST['dj_bio'] ?? '');
        $genre       = trim($_POST['genre'] ?? 'Soul');

        $validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        if ($programName && $djName && in_array($day, $validDays) && $startTime && $endTime) {
            if ($id) {
                $pdo->prepare('UPDATE schedule SET day_of_week=?,start_time=?,end_time=?,program_name=?,dj_name=?,dj_bio=?,genre=? WHERE id=?')
                    ->execute([$day,$startTime,$endTime,$programName,$djName,$djBio,$genre,$id]);
                $msg = 'Programma bijgewerkt.';
            } else {
                $pdo->prepare('INSERT INTO schedule (day_of_week,start_time,end_time,program_name,dj_name,dj_bio,genre) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$day,$startTime,$endTime,$programName,$djName,$djBio,$genre]);
                $msg = 'Programma toegevoegd.';
            }
        } else {
            $msg = 'Vul alle verplichte velden in.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM schedule WHERE id = ?')->execute([$id]);
            $msg = 'Programma verwijderd.';
        }
    }
}

$allSchedule = getAllSchedule();
$dayOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$today = getTodayDayName();
$activeDay = $_GET['day'] ?? $today;
if (!in_array($activeDay, $dayOrder)) $activeDay = $today;

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Planningsbeheer</h1>
    <p>Beheer het wekelijkse programmaschema.</p>
  </div>
  <button class="btn btn-primary" data-modal-open="schedule-modal"
    data-edit-schedule='{"id":"","day_of_week":"<?= $activeDay ?>","start_time":"","end_time":"","program_name":"","dj_name":"","dj_bio":"","genre":"Soul"}'>
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    Programma toevoegen
  </button>
</div>

<!-- Day tabs -->
<div class="schedule-tabs" role="tablist" style="margin-bottom:1.5rem">
  <?php foreach ($dayOrder as $day): ?>
  <a href="?day=<?= $day ?>" role="tab"
    class="tab-btn <?= $day === $activeDay ? 'active' : '' ?> <?= $day === $today ? 'today' : '' ?>"
    aria-selected="<?= $day === $activeDay ? 'true' : 'false' ?>">
    <?= dutchDayName($day) ?>
    <span style="font-size:.7rem;opacity:.7">(<?= count($allSchedule[$day] ?? []) ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<div class="table-container">
  <div class="table-header">
    <span class="table-title"><?= dutchDayName($activeDay) ?> — <?= count($allSchedule[$activeDay] ?? []) ?> programma's</span>
  </div>
  <div class="table-wrapper">
    <table aria-label="Schema <?= dutchDayName($activeDay) ?>">
      <thead>
        <tr>
          <th>Tijd</th>
          <th>Programma</th>
          <th>DJ</th>
          <th>Genre</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
        <?php $slots = $allSchedule[$activeDay] ?? []; ?>
        <?php if ($slots): foreach ($slots as $slot): ?>
        <tr>
          <td data-label="Tijd">
            <span style="font-weight:700;color:var(--accent)"><?= formatTime($slot['start_time']) ?></span>
            <span style="color:var(--text-dim)"> – <?= formatTime($slot['end_time']) ?></span>
          </td>
          <td data-label="Programma" class="td-primary"><?= htmlspecialchars($slot['program_name']) ?></td>
          <td data-label="DJ">
            <div class="td-primary"><?= htmlspecialchars($slot['dj_name']) ?></div>
            <?php if ($slot['dj_bio']): ?>
            <div class="td-muted" style="max-width:200px"><?= htmlspecialchars(truncate($slot['dj_bio'], 60)) ?></div>
            <?php endif; ?>
          </td>
          <td data-label="Genre"><span class="badge badge-dj"><?= htmlspecialchars($slot['genre']) ?></span></td>
          <td data-label="Acties">
            <div class="action-btns">
              <button class="btn-icon edit" title="Bewerken" aria-label="Bewerk programma"
                data-edit-schedule='<?= htmlspecialchars(json_encode($slot)) ?>'>
                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              </button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $slot['id'] ?>">
                <button type="submit" class="btn-icon del" title="Verwijderen"
                  data-confirm-delete="Weet je zeker dat je '<?= htmlspecialchars(addslashes($slot['program_name'])) ?>' wilt verwijderen?"
                  aria-label="Verwijder programma">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text-dim);padding:3rem">Geen programma's voor <?= dutchDayName($activeDay) ?>. <button class="btn btn-secondary btn-sm" data-modal-open="schedule-modal" style="margin-left:.5rem">Toevoegen</button></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Schedule Modal -->
<div class="modal-overlay" id="schedule-modal" role="dialog" aria-modal="true" aria-labelledby="schedule-modal-title">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="schedule-modal-title">Programma toevoegen</span>
      <button class="modal-close" data-modal-close="schedule-modal" aria-label="Sluiten">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <form method="POST" action="">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="schedule-id">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sched-day">Dag <span class="req">*</span></label>
            <select name="day_of_week" id="sched-day" class="form-control" required>
              <?php foreach ($dayOrder as $d): ?>
              <option value="<?= $d ?>"><?= dutchDayName($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Genre</label>
            <input type="text" name="genre" class="form-control" placeholder="Soul" maxlength="50">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sched-start">Begintijd <span class="req">*</span></label>
            <input type="time" name="start_time" id="sched-start" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="sched-end">Eindtijd <span class="req">*</span></label>
            <input type="time" name="end_time" id="sched-end" class="form-control" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="sched-program">Programmanaam <span class="req">*</span></label>
          <input type="text" name="program_name" id="sched-program" class="form-control" placeholder="Morning Soul" required maxlength="100">
        </div>

        <div class="form-group">
          <label class="form-label" for="sched-dj">DJ naam <span class="req">*</span></label>
          <input type="text" name="dj_name" id="sched-dj" class="form-control" placeholder="DJ Marcus" required maxlength="100">
        </div>

        <div class="form-group">
          <label class="form-label" for="sched-bio">DJ bio (optioneel)</label>
          <textarea name="dj_bio" id="sched-bio" class="form-control" rows="3" placeholder="Korte omschrijving..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="schedule-modal">Annuleren</button>
        <button type="submit" class="btn btn-primary">Opslaan</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
