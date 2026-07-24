<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pageTitle = 'Wachtwoord wijzigen';
$activePage = 'password';
$csrf = generateCsrfToken();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Ongeldig beveiligingstoken.';
        $msgType = 'error';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordRepeat = $_POST['new_password_repeat'] ?? '';

        if (!$currentPassword || !$newPassword || !$newPasswordRepeat) {
            $msg = 'Vul alle velden in.';
            $msgType = 'error';
        } elseif (strlen($newPassword) < 8) {
            $msg = 'Nieuw wachtwoord moet minimaal 8 tekens zijn.';
            $msgType = 'error';
        } elseif ($newPassword !== $newPasswordRepeat) {
            $msg = 'Nieuwe wachtwoorden komen niet overeen.';
            $msgType = 'error';
        } else {
            $stmt = getPDO()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$_SESSION['user_id']]);
            $hash = (string)$stmt->fetchColumn();

            if (!$hash || !password_verify($currentPassword, $hash)) {
                $msg = 'Huidig wachtwoord is onjuist.';
                $msgType = 'error';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                getPDO()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, (int)$_SESSION['user_id']]);
                $msg = 'Je wachtwoord is gewijzigd.';
            }
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Wachtwoord wijzigen</h1>
    <p>Wijzig je eigen inlogwachtwoord voor het beheerpaneel.</p>
  </div>
</div>

<div style="max-width:640px">
  <div class="section-card">
    <div class="section-card-body">
      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-group">
          <label class="form-label">Huidig wachtwoord <span class="req">*</span></label>
          <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-group">
          <label class="form-label">Nieuw wachtwoord <span class="req">*</span></label>
          <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">Herhaal nieuw wachtwoord <span class="req">*</span></label>
          <input type="password" name="new_password_repeat" class="form-control" required minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          Wachtwoord opslaan
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
