<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$role = $_SESSION['user_role'] ?? 'listener';
$isAdmin = $role === 'admin';

if (!$isAdmin && !hasPermission('view_stream_info')) {
    http_response_code(403);
    die('Toegang geweigerd');
}

$pageTitle = $isAdmin ? 'DJ Live-credentials beheer' : 'Mijn Live Radio Inlog';
$activePage = 'live-credentials';
$csrf = generateCsrfToken();
$msg = '';
$msgType = 'success';

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $setAsDj = isset($_POST['set_as_dj']) ? 1 : 0;
        $streamType = trim($_POST['stream_type'] ?? 'Icecast');
        $host = trim($_POST['host'] ?? '');
        $mountPoint = trim($_POST['mount_point'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $port = (int)($_POST['port'] ?? 8000);
        $extraNotes = trim($_POST['extra_notes'] ?? '');

        $pdo = getPDO();
        $userStmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
        $userStmt->execute([$userId]);
        $targetUser = $userStmt->fetch();

        if (!$targetUser) {
            $msg = 'Kies een geldige gebruiker.';
            $msgType = 'error';
        } elseif (!$host || !$username || $port < 1 || $port > 65535) {
            $msg = 'Vul host, gebruikersnaam en een geldige poort in.';
            $msgType = 'error';
        } else {
            if ($password === '') {
                $existing = getDjLiveCredentials($userId);
                $password = $existing['password_plain'] ?? '';
            }

            if ($password === '') {
                $msg = 'Vul een wachtwoord in voor deze DJ.';
                $msgType = 'error';
            } elseif (saveDjLiveCredentials($userId, $streamType, $host, $mountPoint, $username, $password, $port, $extraNotes)) {
                if ($setAsDj && !in_array($targetUser['role'], ['dj', 'dj_hoofd'], true)) {
                    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(['dj', $userId]);
                    $msg = 'Live-credentials opgeslagen en gebruiker is als DJ ingesteld.';
                } else {
                    $msg = 'Live-credentials opgeslagen.';
                }
            } else {
                $msg = 'Opslaan mislukt.';
                $msgType = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && deleteDjLiveCredentials($userId)) {
            $msg = 'Live-credentials verwijderd.';
        } else {
            $msg = 'Verwijderen mislukt.';
            $msgType = 'error';
        }
    } elseif ($action === 'bulk_save') {
        $streamType = trim($_POST['stream_type'] ?? 'Icecast');
        $host = trim($_POST['host'] ?? '');
        $mountPoint = trim($_POST['mount_point'] ?? '');
        $sharedUsername = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $port = (int)($_POST['port'] ?? 8000);
        $extraNotes = trim($_POST['extra_notes'] ?? '');
        $usernameMode = $_POST['username_mode'] ?? 'account';
        $skipExisting = isset($_POST['skip_existing']);
        $setAsDj = isset($_POST['set_as_dj']);
        $activeOnly = isset($_POST['active_only']);

        if (!$host || !$password || $port < 1 || $port > 65535) {
            $msg = 'Vul host, wachtwoord en een geldige poort in.';
            $msgType = 'error';
        } elseif ($usernameMode !== 'account' && $sharedUsername === '') {
            $msg = 'Vul een gedeelde gebruikersnaam in of kies account-gebruikersnamen.';
            $msgType = 'error';
        } else {
            $bulkResult = bulkSaveDjLiveCredentials(
                $streamType,
                $host,
                $mountPoint,
                $sharedUsername,
                $password,
                $port,
                $extraNotes,
                $usernameMode === 'account',
                $skipExisting,
                $setAsDj,
                $activeOnly
            );

            if ($bulkResult['success'] > 0) {
                $msg = sprintf(
                    'Live-credentials toegewezen aan %d gebruiker(s).',
                    $bulkResult['success']
                );
                if ($bulkResult['skipped'] > 0) {
                    $msg .= sprintf(' %d overgeslagen (hadden al credentials).', $bulkResult['skipped']);
                }
                if ($bulkResult['failed'] > 0) {
                    $msg .= sprintf(' %d mislukt.', $bulkResult['failed']);
                }
            } elseif ($bulkResult['skipped'] > 0) {
                $msg = 'Alle gebruikers hadden al credentials — niets gewijzigd.';
                $msgType = 'warning';
            } else {
                $msg = 'Bulk-toewijzing mislukt. Controleer de invoer.';
                $msgType = 'error';
            }
        }
    }
}

$djs = getDjsWithLiveStatus();

if ($isAdmin) {
    $editUserId = (int)($_GET['user'] ?? 0);
    if (!$editUserId && !empty($djs)) {
        $editUserId = (int)$djs[0]['id'];
    }

    $editUser = null;
    foreach ($djs as $djUser) {
        if ((int)$djUser['id'] === $editUserId) {
            $editUser = $djUser;
            break;
        }
    }
    $editCreds = $editUser ? getDjLiveCredentials($editUserId) : null;
} else {
    $editUserId = (int)$_SESSION['user_id'];
    $editUser = getCurrentUser();
    $editCreds = getDjLiveCredentials($editUserId);
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1><?= $isAdmin ? 'DJ Live-credentials beheer' : 'Mijn Live Radio Inlog' ?></h1>
    <p>
      <?= $isAdmin
        ? 'Beheer per DJ de inloggegevens voor live uitzenden.'
        : 'Jouw live-uitzendgegevens voor de radio stream.'
      ?>
    </p>
  </div>
</div>

<?php if ($isAdmin):
  $missingCreds = count(array_filter($djs, fn($d) => !$d['has_live_creds']));
?>
<details class="section-card" style="margin-bottom:1.5rem" <?= $missingCreds > 0 ? 'open' : '' ?>>
  <summary class="section-card-header" style="cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:1rem">
    <span class="section-card-title">Bulk toewijzen aan alle gebruikers</span>
    <span class="badge badge-inactive"><?= $missingCreds ?> zonder credentials</span>
  </summary>
  <div class="section-card-body">
    <p style="color:var(--text-dim);font-size:.9rem;margin-bottom:1.25rem">
      Wijs dezelfde stream-instellingen in één keer toe aan alle gebruikers. Gebruikersnamen kunnen per account of gedeeld zijn.
    </p>
    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="bulk_save">

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem">
        <div class="form-group">
          <label class="form-label">Type <span class="req">*</span></label>
          <input type="text" name="stream_type" class="form-control" value="Icecast" required maxlength="50">
        </div>
        <div class="form-group">
          <label class="form-label">Host <span class="req">*</span></label>
          <input type="text" name="host" class="form-control" required maxlength="150" placeholder="stream.soulfm.nl">
        </div>
        <div class="form-group">
          <label class="form-label">Mount</label>
          <input type="text" name="mount_point" class="form-control" maxlength="100" placeholder="/live">
        </div>
        <div class="form-group">
          <label class="form-label">Poort <span class="req">*</span></label>
          <input type="number" name="port" class="form-control" value="8000" min="1" max="65535" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Gebruikersnaam-modus</label>
        <select name="username_mode" class="form-control" id="bulk-username-mode">
          <option value="account">Per account (elke gebruiker krijgt hun eigen gebruikersnaam)</option>
          <option value="shared">Gedeeld (zelfde gebruikersnaam voor iedereen)</option>
        </select>
      </div>
      <div class="form-group" id="bulk-shared-username" style="display:none">
        <label class="form-label">Gedeelde gebruikersnaam <span class="req">*</span></label>
        <input type="text" name="username" class="form-control" maxlength="100" placeholder="dj">
      </div>
      <div class="form-group">
        <label class="form-label">Wachtwoord <span class="req">*</span></label>
        <input type="password" name="password" class="form-control" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label class="form-label">Notities</label>
        <textarea name="extra_notes" class="form-control" rows="2" placeholder="Optionele notities voor alle gebruikers"></textarea>
      </div>

      <div style="display:flex;flex-direction:column;gap:.65rem;margin-bottom:1.25rem">
        <label class="toggle-switch">
          <input type="checkbox" name="active_only" checked>
          <span class="toggle-track"></span>
          <span class="toggle-label">Alleen actieve gebruikers</span>
        </label>
        <label class="toggle-switch">
          <input type="checkbox" name="skip_existing">
          <span class="toggle-track"></span>
          <span class="toggle-label">Gebruikers met bestaande credentials overslaan</span>
        </label>
        <label class="toggle-switch">
          <input type="checkbox" name="set_as_dj">
          <span class="toggle-track"></span>
          <span class="toggle-label">Niet-DJ gebruikers als DJ instellen</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary" data-confirm-delete="Live-credentials toewijzen aan alle geselecteerde gebruikers? Bestaande instellingen worden overschreven (tenzij overslaan is aangevinkt).">
        <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Toewijzen aan alle gebruikers
      </button>
    </form>
  </div>
</details>

<script>
document.getElementById('bulk-username-mode')?.addEventListener('change', function () {
  const shared = document.getElementById('bulk-shared-username');
  if (shared) shared.style.display = this.value === 'shared' ? 'block' : 'none';
});
</script>

<div style="display:grid;grid-template-columns:1fr 420px;gap:1.5rem;align-items:start">
  <div class="table-container">
    <div class="table-header">
      <span class="table-title">Gebruikers (<?= count($djs) ?>)</span>
      <div class="search-input">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" placeholder="Zoeken..." data-search-table="dj-live-table">
      </div>
    </div>
    <div class="table-wrapper">
      <table id="dj-live-table" aria-label="Live credentials">
        <thead>
          <tr>
            <th>Gebruiker</th>
            <th>Type</th>
            <th>Host</th>
            <th>Status</th>
            <th>Acties</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($djs as $djUser): ?>
          <tr style="<?= (int)$djUser['id'] === $editUserId ? 'background:rgba(0,180,216,.07)' : '' ?>">
            <td data-label="Gebruiker">
              <div class="td-primary"><?= htmlspecialchars($djUser['username']) ?></div>
              <div class="td-muted"><?= getRoleLabel($djUser['role']) ?></div>
            </td>
            <td data-label="Type" class="td-muted"><?= htmlspecialchars($djUser['stream_type'] ?: '—') ?></td>
            <td data-label="Host" class="td-muted"><?= htmlspecialchars($djUser['host'] ?: '—') ?></td>
            <td data-label="Status">
              <?php if ($djUser['has_live_creds']): ?>
              <span class="badge badge-active">Ingesteld</span>
              <?php else: ?>
              <span class="badge badge-inactive">Ontbreekt</span>
              <?php endif; ?>
            </td>
            <td data-label="Acties">
              <div class="action-btns">
                <a href="?user=<?= (int)$djUser['id'] ?>#edit-form" class="btn-icon edit" title="Bewerken">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                </a>
                <?php if ($djUser['has_live_creds']): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= (int)$djUser['id'] ?>">
                  <button type="submit" class="btn-icon del" data-confirm-delete="Live-credentials van <?= htmlspecialchars(addslashes($djUser['username'])) ?> verwijderen?">
                    <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="edit-form" style="position:sticky;top:80px">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <?= $editUser ? 'Live-inlog instellen: ' . htmlspecialchars($editUser['username']) : 'Selecteer een gebruiker' ?>
        </span>
      </div>
      <?php if ($editUser): ?>
      <div class="section-card-body">
        <form method="POST" action="?user=<?= $editUserId ?>#edit-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="user_id" value="<?= $editUserId ?>">

          <div class="form-group">
            <label class="form-label">Type <span class="req">*</span></label>
            <input type="text" name="stream_type" class="form-control" value="<?= htmlspecialchars($editCreds['stream_type'] ?? 'Icecast') ?>" required maxlength="50" placeholder="Icecast, Shoutcast, AzuraCast...">
          </div>
          <div class="form-group">
            <label class="form-label">Host <span class="req">*</span></label>
            <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($editCreds['host'] ?? '') ?>" required maxlength="150" placeholder="stream.soulfm.nl">
          </div>
          <div style="display:grid;grid-template-columns:1fr 110px;gap:.75rem">
            <div class="form-group">
              <label class="form-label">Mount</label>
              <input type="text" name="mount_point" class="form-control" value="<?= htmlspecialchars($editCreds['mount_point'] ?? '') ?>" maxlength="100" placeholder="/live">
            </div>
            <div class="form-group">
              <label class="form-label">Poort <span class="req">*</span></label>
              <input type="number" name="port" class="form-control" value="<?= htmlspecialchars((string)($editCreds['port'] ?? 8000)) ?>" min="1" max="65535" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Username <span class="req">*</span></label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($editCreds['username'] ?? '') ?>" required maxlength="100">
          </div>
          <div class="form-group">
            <label class="form-label">Password <?= $editCreds ? '<span style="font-weight:400;color:var(--text-dim)">(leeg laten = ongewijzigd)</span>' : '<span class="req">*</span>' ?></label>
            <input type="password" name="password" class="form-control" <?= $editCreds ? '' : 'required' ?> autocomplete="new-password">
          </div>
          <?php if (!in_array($editUser['role'], ['dj', 'dj_hoofd'], true)): ?>
          <div class="form-group">
            <label class="toggle-switch">
              <input type="checkbox" name="set_as_dj" checked>
              <span class="toggle-track"></span>
              <span class="toggle-label">Gebruiker als DJ instellen (rol = DJ)</span>
            </label>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Notities</label>
            <textarea name="extra_notes" class="form-control" rows="3"><?= htmlspecialchars($editCreds['extra_notes'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
            Opslaan
          </button>
        </form>
      </div>
      <?php else: ?>
      <div style="padding:2rem;color:var(--text-dim);text-align:center">Geen gebruikers gevonden.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php else: ?>
<?php if (!$editCreds): ?>
<div class="section-card" style="max-width:700px">
  <div class="section-card-body">
    <div style="color:var(--text-dim)">Je live-radio inloggegevens zijn nog niet ingesteld. Neem contact op met een admin.</div>
  </div>
</div>
<?php else: ?>
<div style="max-width:760px;display:flex;flex-direction:column;gap:1.25rem">
  <div class="section-card">
    <div class="section-card-header">
      <span class="section-card-title">Live radio login</span>
      <span style="font-size:.75rem;color:var(--text-dim)">Bijgewerkt: <?= formatRelativeDate($editCreds['updated_at']) ?></span>
    </div>
    <div class="section-card-body">
      <?php
      $rows = [
          'Type' => $editCreds['stream_type'],
          'Host' => $editCreds['host'],
          'Mount' => $editCreds['mount_point'] ?: '—',
          'Username' => $editCreds['username'],
          'Poort' => (string)$editCreds['port'],
      ];
      foreach ($rows as $label => $value): ?>
      <div style="display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-bottom:1px solid rgba(255,255,255,.05)">
        <span style="color:var(--text-dim)"><?= $label ?></span>
        <span style="font-family:monospace;color:var(--white)"><?= htmlspecialchars($value) ?></span>
      </div>
      <?php endforeach; ?>

      <div style="margin-top:1rem">
        <div style="font-size:.8rem;color:var(--text-dim);margin-bottom:.4rem">Password</div>
        <div style="display:flex;gap:.75rem;align-items:center">
          <span id="dj-pw-display" style="font-family:monospace;background:rgba(0,0,0,.25);border:1px solid rgba(0,180,216,.15);border-radius:8px;padding:.6rem .9rem;filter:blur(6px);user-select:none;flex:1"><?= htmlspecialchars($editCreds['password_plain'] ?? '—') ?></span>
          <button type="button" class="btn btn-secondary btn-sm" onclick="toggleDjPassword()">Toon</button>
        </div>
      </div>
    </div>
  </div>
  <?php if (!empty($editCreds['extra_notes'])): ?>
  <div class="section-card">
    <div class="section-card-header"><span class="section-card-title">Notities</span></div>
    <div class="section-card-body" style="white-space:pre-wrap"><?= htmlspecialchars($editCreds['extra_notes']) ?></div>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleDjPassword() {
  const pw = document.getElementById('dj-pw-display');
  const hidden = pw.style.filter !== 'none';
  pw.style.filter = hidden ? 'none' : 'blur(6px)';
  pw.style.userSelect = hidden ? 'text' : 'none';
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
