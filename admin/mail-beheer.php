<?php
/**
 * SoulFM - Mailcredentials Beheer (admin only)
 * Admin kan per gebruiker een @soulfm.nl account instellen.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
// Alleen admin mag credentials van anderen beheren
if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Toegang geweigerd');
}

$pageTitle  = 'Mailcredentials beheer';
$activePage = 'mail-beheer';
$csrf       = generateCsrfToken();
$msg        = '';
$msgType    = 'success';
$pdo        = getPDO();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $userId      = (int)($_POST['user_id'] ?? 0);
        $mailAddress = trim($_POST['mail_address'] ?? '');
        $plainPw     = $_POST['mail_password'] ?? '';
        $imapServer  = trim($_POST['imap_server'] ?? 'mail.soulfm.nl');
        $smtpServer  = trim($_POST['smtp_server'] ?? 'mail.soulfm.nl');
        $imapPort    = (int)($_POST['imap_port'] ?? 993);
        $smtpPort    = (int)($_POST['smtp_port'] ?? 587);
        $extraNotes  = trim($_POST['extra_notes'] ?? '');

        if (!$userId || !filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Selecteer een gebruiker en vul een geldig e-mailadres in.';
            $msgType = 'error';
        } elseif (empty($plainPw) && !getUserMailCredentials($userId)) {
            // Nieuw account vereist wachtwoord
            $msg = 'Vul een wachtwoord in voor dit nieuwe account.';
            $msgType = 'error';
        } else {
            // Als wachtwoord leeg gelaten bij update → behoudt het bestaande wachtwoord
            if (empty($plainPw)) {
                $existing = getUserMailCredentials($userId);
                $plainPw  = $existing['mail_password_plain'] ?? '';
            }

            if (saveUserMailCredentials($userId, $mailAddress, $plainPw, $imapServer, $smtpServer, $imapPort, $smtpPort, $extraNotes)) {
                $msg = 'Mailcredentials opgeslagen voor gebruiker.';
            } else {
                $msg = 'Er is een fout opgetreden bij het opslaan.';
                $msgType = 'error';
            }
        }

    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && deleteUserMailCredentials($userId)) {
            $msg = 'Mailcredentials verwijderd.';
        } else {
            $msg = 'Verwijderen mislukt.';
            $msgType = 'error';
        }
    }
}

// Haal gebruikers op met hun mailstatus
$users = getAllUsersWithMailStatus();

// Geselecteerde gebruiker (voor edit-form)
$editUserId = (int)($_GET['user'] ?? 0);
$editCreds  = null;
$editUser   = null;
if ($editUserId) {
    foreach ($users as $u) {
        if ((int)$u['id'] === $editUserId) { $editUser = $u; break; }
    }
    $editCreds = getUserMailCredentials($editUserId);
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Mailcredentials beheer</h1>
    <p>Stel <strong style="color:var(--accent)">@soulfm.nl</strong> bedrijfsmail-accounts in per medewerker. Medewerkers zien hun eigen gegevens via "Mijn Bedrijfsmail".</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 400px;gap:1.5rem;align-items:start">

  <!-- Gebruikersoverzicht -->
  <div class="table-container">
    <div class="table-header">
      <span class="table-title">Medewerkers (<?= count($users) ?>)</span>
      <div class="search-input">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" placeholder="Zoeken..." data-search-table="mail-users-table">
      </div>
    </div>
    <div class="table-wrapper">
      <table id="mail-users-table" aria-label="Medewerkers mailcredentials">
        <thead>
          <tr>
            <th>Medewerker</th>
            <th>Rol</th>
            <th>Bedrijfsmail</th>
            <th>Status</th>
            <th>Acties</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr style="<?= (int)$u['id'] === $editUserId ? 'background:rgba(0,180,216,.07)' : '' ?>">
            <td data-label="Medewerker">
              <div style="display:flex;align-items:center;gap:.6rem">
                <div style="width:28px;height:28px;background:linear-gradient(135deg,var(--accent),#2d6a9f);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.72rem;color:white;flex-shrink:0">
                  <?= strtoupper(substr($u['username'], 0, 1)) ?>
                </div>
                <div>
                  <div class="td-primary"><?= htmlspecialchars($u['username']) ?></div>
                  <div class="td-muted"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td data-label="Rol">
              <span class="badge badge-<?= $u['role'] ?>"><?= getRoleLabel($u['role']) ?></span>
            </td>
            <td data-label="Bedrijfsmail" class="td-muted">
              <?= $u['mail_address'] ? htmlspecialchars($u['mail_address']) : '<em style="opacity:.4">—</em>' ?>
            </td>
            <td data-label="Status">
              <?php if ($u['has_mail_creds']): ?>
                <span class="badge badge-active">Ingesteld</span>
              <?php else: ?>
                <span class="badge badge-inactive">Geen account</span>
              <?php endif; ?>
            </td>
            <td data-label="Acties">
              <div class="action-btns">
                <a href="?user=<?= $u['id'] ?>#edit-form" class="btn-icon edit" title="<?= $u['has_mail_creds'] ? 'Bewerken' : 'Instellen' ?>"
                  aria-label="<?= $u['has_mail_creds'] ? 'Bewerk' : 'Stel in' ?> voor <?= htmlspecialchars($u['username']) ?>">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                </a>
                <?php if ($u['has_mail_creds']): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-icon del" title="Credentials verwijderen"
                    data-confirm-delete="Mailcredentials van <?= htmlspecialchars(addslashes($u['username'])) ?> verwijderen?"
                    aria-label="Verwijder mailcredentials">
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

  <!-- Edit formulier -->
  <div id="edit-form" style="position:sticky;top:80px">
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <?php if ($editUser): ?>
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <?= $editCreds ? 'Bewerken' : 'Instellen' ?>: <?= htmlspecialchars($editUser['username']) ?>
          <?php else: ?>
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            Selecteer een medewerker
          <?php endif; ?>
        </span>
      </div>

      <?php if ($editUser): ?>
      <div class="section-card-body">
        <!-- Medewerkerinfo -->
        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem;background:rgba(0,0,0,.2);border-radius:8px;margin-bottom:1.5rem">
          <div style="width:38px;height:38px;background:linear-gradient(135deg,var(--accent),#2d6a9f);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:white">
            <?= strtoupper(substr($editUser['username'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-weight:600;color:var(--white)"><?= htmlspecialchars($editUser['username']) ?></div>
            <span class="badge badge-<?= $editUser['role'] ?>" style="font-size:.68rem"><?= getRoleLabel($editUser['role']) ?></span>
          </div>
        </div>

        <form method="POST" action="?user=<?= $editUserId ?>#edit-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="user_id" value="<?= $editUserId ?>">

          <div class="form-group">
            <label class="form-label">Bedrijfsmail adres <span class="req">*</span></label>
            <input type="email" name="mail_address" class="form-control"
              value="<?= htmlspecialchars($editCreds['mail_address'] ?? strtolower(preg_replace('/[^a-z0-9]/i', '.', $editUser['username'])) . '@soulfm.nl') ?>"
              placeholder="naam@soulfm.nl" required maxlength="150">
          </div>

          <div class="form-group">
            <label class="form-label">
              Wachtwoord
              <?php if ($editCreds): ?>
                <span style="font-weight:400;color:var(--text-dim)">(leeg laten = ongewijzigd)</span>
              <?php else: ?>
                <span class="req">*</span>
              <?php endif; ?>
            </label>
            <div style="position:relative">
              <input type="password" name="mail_password" id="admin-pw-input" class="form-control"
                placeholder="<?= $editCreds ? '••••••••••' : 'Nieuw wachtwoord' ?>"
                <?= $editCreds ? '' : 'required' ?> autocomplete="new-password"
                style="padding-right:3rem">
              <button type="button" onclick="toggleAdminPw()" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-dim);padding:0">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              </button>
            </div>
          </div>

          <!-- Serverinstellingen (inklapbaar) -->
          <details style="margin-bottom:1.25rem">
            <summary style="cursor:pointer;font-size:.8rem;font-weight:600;color:var(--text-dim);text-transform:uppercase;letter-spacing:.5px;padding:.4rem 0;list-style:none;display:flex;align-items:center;gap:.4rem">
              <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M7 10l5 5 5-5z"/></svg>
              Serverinstellingen
            </summary>
            <div style="margin-top:1rem;display:grid;gap:.75rem">
              <div style="display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:end">
                <div class="form-group" style="margin:0">
                  <label class="form-label">IMAP-server</label>
                  <input type="text" name="imap_server" class="form-control"
                    value="<?= htmlspecialchars($editCreds['imap_server'] ?? 'mail.soulfm.nl') ?>" placeholder="mail.soulfm.nl">
                </div>
                <div class="form-group" style="margin:0;width:80px">
                  <label class="form-label">Poort</label>
                  <input type="number" name="imap_port" class="form-control"
                    value="<?= htmlspecialchars((string)($editCreds['imap_port'] ?? 993)) ?>" min="1" max="65535">
                </div>
              </div>
              <div style="display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:end">
                <div class="form-group" style="margin:0">
                  <label class="form-label">SMTP-server</label>
                  <input type="text" name="smtp_server" class="form-control"
                    value="<?= htmlspecialchars($editCreds['smtp_server'] ?? 'mail.soulfm.nl') ?>" placeholder="mail.soulfm.nl">
                </div>
                <div class="form-group" style="margin:0;width:80px">
                  <label class="form-label">Poort</label>
                  <input type="number" name="smtp_port" class="form-control"
                    value="<?= htmlspecialchars((string)($editCreds['smtp_port'] ?? 587)) ?>" min="1" max="65535">
                </div>
              </div>
            </div>
          </details>

          <div class="form-group">
            <label class="form-label">Extra notities voor medewerker</label>
            <textarea name="extra_notes" class="form-control" rows="3"
              placeholder="Bijv. contactpersoon bij problemen, webmail URL..."><?= htmlspecialchars($editCreds['extra_notes'] ?? '') ?></textarea>
            <div class="form-hint">Zichtbaar voor de medewerker op zijn eigen pagina.</div>
          </div>

          <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
              <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
              Opslaan
            </button>
            <a href="mail-beheer.php" class="btn btn-ghost">Annuleren</a>
          </div>
        </form>
      </div>
      <?php else: ?>
      <div style="padding:2.5rem;text-align:center;color:var(--text-dim)">
        <svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:currentColor;margin:0 auto 1rem;opacity:.3"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        <p style="font-size:.88rem">Klik op het potlood-icoon naast een medewerker om diens credentials in te stellen.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Beveiligingsnotitie -->
    <div style="margin-top:1rem;padding:1rem 1.25rem;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.15);border-radius:10px;font-size:.78rem;color:var(--text-dim)">
      <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#fbbf24;display:inline;vertical-align:middle;margin-right:4px"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
      <strong style="color:#fbbf24">Beveiliging:</strong> Wachtwoorden worden versleuteld opgeslagen (AES-256). Medewerkers kunnen hun eigen accountwachtwoord wijzigen via "Wachtwoord wijzigen".
    </div>
  </div>

</div>

<script>
function toggleAdminPw() {
    const input = document.getElementById('admin-pw-input');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
