<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('manage_users')) { http_response_code(403); die('Toegang geweigerd - alleen admins'); }

$pageTitle  = 'Gebruikersbeheer';
$activePage = 'users';
$csrf       = generateCsrfToken();
$msg        = '';
$msgType    = 'success';
$pdo        = getPDO();

// Handle AJAX role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success'=>false,'message'=>'CSRF ongeldig']); exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'update_role') {
        $userId    = (int)($_POST['user_id'] ?? 0);
        $newRole   = $_POST['role'] ?? '';
        $validRoles = array_keys(getAllRoles());
        if ($userId && in_array($newRole, $validRoles)) {
            if ($userId === (int)$_SESSION['user_id'] && $newRole !== 'admin') {
                echo json_encode(['success'=>false,'message'=>'Je kunt je eigen admin-rol niet verwijderen.']); exit;
            }
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $userId]);
            echo json_encode(['success'=>true,'message'=>'Rol bijgewerkt naar ' . getRoleLabel($newRole)]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Ongeldige invoer']);
        }
    }
    exit;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_active' && $userId && $userId !== (int)$_SESSION['user_id']) {
        $pdo->prepare('UPDATE users SET active = NOT active WHERE id = ?')->execute([$userId]);
        $msg = 'Gebruikersstatus gewijzigd.';
    } elseif ($action === 'add_user') {
        $username   = trim($_POST['new_username'] ?? '');
        $email      = trim($_POST['new_email']    ?? '');
        $password   = $_POST['new_password']      ?? '';
        $role       = $_POST['new_role']          ?? 'listener';
        $validRoles = array_keys(getAllRoles());

        if (!$username || !$email || !$password || !in_array($role, $validRoles)) {
            $msg = 'Vul alle velden in.'; $msgType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Ongeldig e-mailadres.'; $msgType = 'error';
        } elseif (strlen($password) < 8) {
            $msg = 'Wachtwoord moet minimaal 8 tekens zijn.'; $msgType = 'error';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?,?,?,?)')
                    ->execute([$username, $email, $hash, $role]);
                $msg = "Gebruiker '$username' aangemaakt.";
            } catch (PDOException $e) {
                $msg = 'Gebruikersnaam of e-mail bestaat al.'; $msgType = 'error';
            }
        }
    }
}

$users = $pdo->query('SELECT id, username, email, role, active, created_at, last_login FROM users ORDER BY role, username')->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Gebruikersbeheer</h1>
    <p>Beheer gebruikersaccounts en roltoewijzingen.</p>
  </div>
  <button class="btn btn-primary" data-modal-open="add-user-modal">
    <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
    Gebruiker toevoegen
  </button>
</div>

<!-- Role legend -->
<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem">
  <?php foreach (getAllRoles() as $roleKey => $roleInfo): ?>
  <?php if ($roleKey === 'listener') continue; ?>
  <span class="badge badge-role-<?= $roleKey ?>" style="<?php
    $colors = [
      'admin'               => 'background:rgba(139,92,246,.15);color:#a78bfa;border:1px solid rgba(139,92,246,.25)',
      'dj_hoofd'            => 'background:rgba(0,180,216,.2);color:#00b4d8;border:1px solid rgba(0,180,216,.35)',
      'dj'                  => 'background:rgba(0,180,216,.1);color:#48cae4;border:1px solid rgba(0,180,216,.2)',
      'administratie_hoofd' => 'background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.35)',
      'administratie'       => 'background:rgba(245,158,11,.1);color:#fcd34d;border:1px solid rgba(245,158,11,.2)',
      'evenementen_hoofd'   => 'background:rgba(236,72,153,.2);color:#f472b6;border:1px solid rgba(236,72,153,.35)',
      'evenementen'         => 'background:rgba(236,72,153,.1);color:#f9a8d4;border:1px solid rgba(236,72,153,.2)',
      'redactie_hoofd'      => 'background:rgba(16,185,129,.2);color:#34d399;border:1px solid rgba(16,185,129,.35)',
      'redactie'            => 'background:rgba(16,185,129,.1);color:#6ee7b7;border:1px solid rgba(16,185,129,.2)',
      'content_hoofd'       => 'background:rgba(59,130,246,.2);color:#60a5fa;border:1px solid rgba(59,130,246,.35)',
      'content'             => 'background:rgba(59,130,246,.1);color:#93c5fd;border:1px solid rgba(59,130,246,.2)',
      'marketing_hoofd'     => 'background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.35)',
      'marketing'           => 'background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2)',
      'moderator'           => 'background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.25)',
    ];
    echo $colors[$roleKey] ?? '';
  ?>">
    <?= htmlspecialchars($roleInfo['label']) ?>
    <?php if ($roleInfo['is_head']): ?> ★<?php endif; ?>
  </span>
  <?php endforeach; ?>
</div>

<div class="table-container">
  <div class="table-header">
    <span class="table-title">Alle gebruikers (<?= count($users) ?>)</span>
    <div class="search-input">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" placeholder="Zoeken..." data-search-table="users-table">
    </div>
  </div>

  <!-- Hidden CSRF for AJAX -->
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div class="table-wrapper">
    <table id="users-table" aria-label="Gebruikers">
      <thead>
        <tr>
          <th>Gebruiker</th>
          <th>E-mail</th>
          <th>Rol</th>
          <th>Status</th>
          <th>Aangemeld</th>
          <th>Laatste login</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td data-label="Gebruiker">
            <div style="display:flex;align-items:center;gap:.65rem">
              <div style="width:30px;height:30px;background:linear-gradient(135deg,var(--accent),#2d6a9f);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;color:white;flex-shrink:0">
                <?= strtoupper(substr($u['username'], 0, 1)) ?>
              </div>
              <span class="td-primary"><?= htmlspecialchars($u['username']) ?></span>
              <?php if ($u['id'] == $_SESSION['user_id']): ?>
              <span style="font-size:.7rem;color:var(--accent)">(jij)</span>
              <?php endif; ?>
            </div>
          </td>
          <td data-label="E-mail" class="td-muted"><?= htmlspecialchars($u['email']) ?></td>
          <td data-label="Rol">
            <?php if ($u['id'] == $_SESSION['user_id']): ?>
              <span class="badge badge-<?= $u['role'] ?>"><?= getRoleLabel($u['role']) ?></span>
            <?php else: ?>
              <select class="form-control" style="padding:.3rem .6rem;font-size:.82rem;width:auto"
                data-role-update="<?= $u['id'] ?>" aria-label="Rol voor <?= htmlspecialchars($u['username']) ?>">
                <?php foreach (getAllRoles() as $roleKey => $roleInfo): ?>
                <option value="<?= $roleKey ?>" <?= $u['role']===$roleKey?'selected':'' ?>><?= htmlspecialchars($roleInfo['label']) ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </td>
          <td data-label="Status">
            <span class="badge badge-<?= $u['active'] ? 'active' : 'inactive' ?>">
              <?= $u['active'] ? 'Actief' : 'Inactief' ?>
            </span>
          </td>
          <td data-label="Aangemeld" class="td-muted"><?= formatDate($u['created_at']) ?></td>
          <td data-label="Laatste login" class="td-muted">
            <?= $u['last_login'] ? formatRelativeDate($u['last_login']) : '<em>Nooit</em>' ?>
          </td>
          <td data-label="Acties">
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn-icon <?= $u['active'] ? 'del' : 'check' ?>"
                title="<?= $u['active'] ? 'Deactiveren' : 'Activeren' ?>"
                data-confirm-delete="<?= $u['active'] ? 'Gebruiker deactiveren?' : 'Gebruiker activeren?' ?>"
                aria-label="<?= $u['active'] ? 'Deactiveer' : 'Activeer' ?> gebruiker <?= htmlspecialchars($u['username']) ?>">
                <svg viewBox="0 0 24 24"><path d="<?= $u['active'] ? 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z' : 'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z' ?>"/></svg>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="add-user-modal" role="dialog" aria-modal="true" aria-labelledby="add-user-title">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="add-user-title">Gebruiker toevoegen</span>
      <button class="modal-close" data-modal-close="add-user-modal" aria-label="Sluiten">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <form method="POST" action="">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="add_user">
        <div class="form-group">
          <label class="form-label">Gebruikersnaam <span class="req">*</span></label>
          <input type="text" name="new_username" class="form-control" placeholder="gebruikersnaam" required maxlength="50" autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">E-mailadres <span class="req">*</span></label>
          <input type="email" name="new_email" class="form-control" placeholder="email@soulfm.nl" required maxlength="100" autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">Wachtwoord <span class="req">*</span></label>
          <input type="password" name="new_password" class="form-control" placeholder="Minimaal 8 tekens" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">Rol</label>
          <select name="new_role" class="form-control">
            <?php foreach (getAllRoles() as $roleKey => $roleInfo): ?>
            <option value="<?= $roleKey ?>"><?= htmlspecialchars($roleInfo['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="add-user-modal">Annuleren</button>
        <button type="submit" class="btn btn-primary">Aanmaken</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
