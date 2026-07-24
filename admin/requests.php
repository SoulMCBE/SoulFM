<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('view_requests')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle  = 'Verzoekjes';
$activePage = 'requests';
$csrf       = generateCsrfToken();

// Handle AJAX status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF ongeldig']); exit;
    }
    if (!hasPermission('manage_requests')) {
        echo json_encode(['success' => false, 'message' => 'Geen rechten']); exit;
    }
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['played','rejected'])) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('UPDATE requests SET status = ?, played_at = IF(? = "played", NOW(), played_at) WHERE id = ?');
        $stmt->execute([$status, $status, $id]);
        echo json_encode(['success' => true, 'message' => 'Status bijgewerkt.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ongeldige invoer.']);
    }
    exit;
}

// Handle form deletes / status updates (non-AJAX fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    if (hasPermission('manage_requests')) {
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo = getPDO();
            if ($action === 'delete') {
                $pdo->prepare('DELETE FROM requests WHERE id = ?')->execute([$id]);
                $msg = 'Verzoekje verwijderd.';
            } elseif (in_array($action, ['played','rejected','pending'])) {
                $pdo->prepare('UPDATE requests SET status = ?, played_at = IF(? = "played", NOW(), played_at) WHERE id = ?')->execute([$action, $action, $id]);
                $msg = 'Status bijgewerkt.';
            }
        }
    }
}

// Fetch with filter
$filterStatus = $_GET['status'] ?? 'all';
$pdo   = getPDO();
$where = $filterStatus !== 'all' ? 'WHERE status = ?' : 'WHERE 1=1';
$params = $filterStatus !== 'all' ? [$filterStatus] : [];
$stmt  = $pdo->prepare("SELECT * FROM requests $where ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$requests = $stmt->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) as c FROM requests GROUP BY status")->fetchAll();
$countMap = ['all' => 0, 'pending' => 0, 'played' => 0, 'rejected' => 0];
foreach ($counts as $c) { $countMap[$c['status']] = $c['c']; $countMap['all'] += $c['c']; }

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if (!empty($msg)): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Verzoekjes</h1>
    <p>Bekijk en beheer inkomende nummersverzoekjes van luisteraars.</p>
  </div>
</div>

<div class="table-container">
  <div class="table-header">
    <div class="filter-bar" role="group" aria-label="Filter verzoekjes">
      <button class="filter-btn <?= $filterStatus==='all'?'active':'' ?>" onclick="location.href='?status=all'">Alle (<?= $countMap['all'] ?>)</button>
      <button class="filter-btn <?= $filterStatus==='pending'?'active':'' ?>" onclick="location.href='?status=pending'">Wacht (<?= $countMap['pending'] ?>)</button>
      <button class="filter-btn <?= $filterStatus==='played'?'active':'' ?>" onclick="location.href='?status=played'">Gespeeld (<?= $countMap['played'] ?>)</button>
      <button class="filter-btn <?= $filterStatus==='rejected'?'active':'' ?>" onclick="location.href='?status=rejected'">Afgewezen (<?= $countMap['rejected'] ?>)</button>
    </div>
    <div class="search-input">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" placeholder="Zoeken..." data-search-table="requests-table">
    </div>
  </div>

  <!-- Hidden CSRF for AJAX calls -->
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div class="table-wrapper">
    <table id="requests-table" aria-label="Verzoekjes">
      <thead>
        <tr>
          <th>#</th>
          <th>Nummer</th>
          <th>Van</th>
          <th>Bericht</th>
          <th>Datum</th>
          <th>Status</th>
          <?php if (hasPermission('manage_requests')): ?><th>Acties</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if ($requests): foreach ($requests as $req): ?>
        <tr id="request-row-<?= $req['id'] ?>" data-status="<?= $req['status'] ?>">
          <td data-label="#" class="td-muted"><?= $req['id'] ?></td>
          <td data-label="Nummer">
            <div class="td-primary"><?= htmlspecialchars($req['song_title']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($req['artist_name']) ?></div>
          </td>
          <td data-label="Van" class="td-muted"><?= htmlspecialchars($req['requester_name']) ?></td>
          <td data-label="Bericht" class="td-muted" style="max-width:200px">
            <?= $req['message'] ? htmlspecialchars(truncate($req['message'], 80)) : '<em>—</em>' ?>
          </td>
          <td data-label="Datum" class="td-muted"><?= formatRelativeDate($req['created_at']) ?></td>
          <td data-label="Status">
            <span class="badge badge-<?= $req['status'] ?>">
              <?php $labels = ['pending'=>'Wacht','played'=>'Gespeeld','rejected'=>'Afgewezen']; ?>
              <?= $labels[$req['status']] ?? $req['status'] ?>
            </span>
          </td>
          <?php if (hasPermission('manage_requests')): ?>
          <td data-label="Acties">
            <div class="action-btns">
              <?php if ($req['status'] === 'pending'): ?>
              <button class="btn-icon check" title="Markeer als gespeeld"
                data-update-status="played" data-id="<?= $req['id'] ?>"
                aria-label="Markeer als gespeeld">
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
              </button>
              <button class="btn-icon del" title="Afwijzen"
                data-update-status="rejected" data-id="<?= $req['id'] ?>"
                aria-label="Verzoekje afwijzen">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
              </button>
              <?php endif; ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $req['id'] ?>">
                <button type="submit" class="btn-icon del" title="Verwijderen"
                  data-confirm-delete="Weet je zeker dat je dit verzoekje wilt verwijderen?"
                  aria-label="Verwijder verzoekje">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </button>
              </form>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-dim);padding:3rem">Geen verzoekjes gevonden.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
