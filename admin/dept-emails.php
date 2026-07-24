<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('view_dept_emails')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle  = 'Afdeling E-mailadressen';
$activePage = 'dept-emails';
$csrf       = generateCsrfToken();

$userRole   = $_SESSION['user_role'] ?? 'listener';
$userDept   = getUserDepartment($userRole);
$isAdmin    = ($userRole === 'admin');

// Admin kan alle afdelingen zien, medewerkers alleen eigen afdeling
if ($isAdmin) {
    $departments = getDepartments();
    $selectedDept = $_GET['dept'] ?? ($departments[0]['slug'] ?? '');
} else {
    $selectedDept = $userDept ?? '';
    $departments  = $userDept ? [getDepartment($userDept)] : [];
    $departments  = array_filter($departments);
}

$emails = $selectedDept ? getDepartmentEmails($selectedDept) : [];
$currentDeptInfo = $selectedDept ? getDepartment($selectedDept) : null;
$deptCreds = $selectedDept ? getDepartmentMailCredentials($selectedDept) : null;

// Admin: beheer e-mails toevoegen/verwijderen
$msg = '';
$msgType = 'success';
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $pdo    = getPDO();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_email') {
        $dept     = trim($_POST['dept_slug'] ?? '');
        $label    = sanitize($_POST['label'] ?? '');
        $emailVal = trim($_POST['email_address'] ?? '');
        $desc     = sanitize($_POST['description'] ?? '');

        if ($dept && $label && filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            $pdo->prepare('INSERT INTO department_emails (department_slug, label, email_address, description) VALUES (?,?,?,?)')
                ->execute([$dept, $label, $emailVal, $desc ?: null]);
            $msg = 'E-mailadres toegevoegd.';
        } else {
            $msg = 'Vul alle verplichte velden in met een geldig e-mailadres.';
            $msgType = 'error';
        }
        $emails = getDepartmentEmails($selectedDept);

    } elseif ($action === 'delete_email') {
        $eid = (int)($_POST['email_id'] ?? 0);
        if ($eid) {
            $pdo->prepare('DELETE FROM department_emails WHERE id=?')->execute([$eid]);
            $msg = 'E-mailadres verwijderd.';
        }
        $emails = getDepartmentEmails($selectedDept);
    } elseif ($action === 'save_login_creds') {
        $dept = trim($_POST['dept_slug'] ?? '');
        $mailAddress = trim($_POST['mail_address'] ?? '');
        $mailPassword = $_POST['mail_password'] ?? '';
        $imapServer = trim($_POST['imap_server'] ?? 'mail.soulfm.nl');
        $smtpServer = trim($_POST['smtp_server'] ?? 'mail.soulfm.nl');
        $imapPort = (int)($_POST['imap_port'] ?? 993);
        $smtpPort = (int)($_POST['smtp_port'] ?? 587);
        $extraNotes = trim($_POST['extra_notes'] ?? '');

        if (!$dept || !filter_var($mailAddress, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Vul een geldig e-mailadres in.';
            $msgType = 'error';
        } elseif ($imapPort < 1 || $imapPort > 65535 || $smtpPort < 1 || $smtpPort > 65535) {
            $msg = 'Gebruik geldige poorten (1-65535).';
            $msgType = 'error';
        } else {
            if ($mailPassword === '') {
                $existingCreds = getDepartmentMailCredentials($dept);
                $mailPassword = $existingCreds['mail_password_plain'] ?? '';
            }

            if ($mailPassword === '') {
                $msg = 'Vul een wachtwoord in voor de afdelingsmail.';
                $msgType = 'error';
            } elseif (saveDepartmentMailCredentials($dept, $mailAddress, $mailPassword, $imapServer, $smtpServer, $imapPort, $smtpPort, $extraNotes)) {
                $msg = 'Afdelingsmail inloggegevens opgeslagen.';
            } else {
                $msg = 'Opslaan van inloggegevens is mislukt.';
                $msgType = 'error';
            }
        }
    }

    $deptCreds = $selectedDept ? getDepartmentMailCredentials($selectedDept) : null;
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Afdeling E-mailadressen</h1>
    <p>
      <?php if ($isAdmin): ?>
        Beheer de e-mailadressen per afdeling.
      <?php else: ?>
        E-mailadressen van de afdeling <strong style="color:var(--accent)"><?= htmlspecialchars($currentDeptInfo['name'] ?? '') ?></strong>.
      <?php endif; ?>
    </p>
  </div>
</div>

<div style="display:grid;grid-template-columns:<?= $isAdmin ? '220px 1fr' : '1fr' ?>;gap:1.5rem;align-items:start">

  <?php if ($isAdmin && count($departments) > 1): ?>
  <!-- Afdeling keuze sidebar (alleen voor admin) -->
  <div class="section-card" style="overflow:hidden">
    <div class="section-card-header"><span class="section-card-title">Afdelingen</span></div>
    <nav style="padding:.5rem 0">
      <?php foreach ($departments as $dept): ?>
      <a href="?dept=<?= urlencode($dept['slug']) ?>"
        style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.1rem;color:<?= $selectedDept===$dept['slug']?'var(--accent)':'var(--text-dim)' ?>;font-size:.88rem;font-weight:<?= $selectedDept===$dept['slug']?'600':'400' ?>;background:<?= $selectedDept===$dept['slug']?'var(--accent-dim)':'transparent' ?>;border-left:3px solid <?= $selectedDept===$dept['slug']?'var(--accent)':'transparent' ?>;transition:all .2s">
        <?= htmlspecialchars($dept['name']) ?>
        <span style="margin-left:auto;font-size:.72rem;background:rgba(0,180,216,.12);color:var(--accent);padding:.1rem .45rem;border-radius:10px">
          <?= count(getDepartmentEmails($dept['slug'])) ?>
        </span>
      </a>
      <?php endforeach; ?>
    </nav>
  </div>
  <?php endif; ?>

  <div>
    <?php if ($currentDeptInfo): ?>
    <!-- Info banner -->
    <div style="background:rgba(0,180,216,0.07);border:1px solid rgba(0,180,216,0.15);border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem">
      <div style="width:42px;height:42px;background:rgba(0,180,216,0.12);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--accent)"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
      </div>
      <div>
        <div style="font-weight:700;color:var(--white)"><?= htmlspecialchars($currentDeptInfo['name']) ?> — Afdeling e-mail</div>
        <div style="font-size:.85rem;color:var(--text-dim)"><?= htmlspecialchars($currentDeptInfo['description'] ?? '') ?></div>
        <?php if ($currentDeptInfo['email']): ?>
        <div style="font-size:.85rem;margin-top:.25rem"><a href="mailto:<?= htmlspecialchars($currentDeptInfo['email']) ?>" style="color:var(--accent)"><?= htmlspecialchars($currentDeptInfo['email']) ?></a></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="section-card" style="margin-bottom:1.5rem">
      <div class="section-card-header">
        <span class="section-card-title">Afdelingsmail inloggegevens</span>
      </div>
      <div class="section-card-body">
        <?php if (!$deptCreds): ?>
          <div style="color:var(--text-dim)">Nog geen inloggegevens ingesteld voor deze afdeling.</div>
        <?php else: ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem 1rem">
            <div><div style="font-size:.75rem;color:var(--text-dim)">E-mailadres</div><div style="font-family:monospace;color:var(--accent)"><?= htmlspecialchars($deptCreds['mail_address']) ?></div></div>
            <div><div style="font-size:.75rem;color:var(--text-dim)">Laatste update</div><div><?= htmlspecialchars(formatRelativeDate($deptCreds['updated_at'])) ?></div></div>
            <div><div style="font-size:.75rem;color:var(--text-dim)">IMAP</div><div style="font-family:monospace"><?= htmlspecialchars($deptCreds['imap_server']) ?>:<?= (int)$deptCreds['imap_port'] ?></div></div>
            <div><div style="font-size:.75rem;color:var(--text-dim)">SMTP</div><div style="font-family:monospace"><?= htmlspecialchars($deptCreds['smtp_server']) ?>:<?= (int)$deptCreds['smtp_port'] ?></div></div>
            <div><div style="font-size:.75rem;color:var(--text-dim)">Wachtwoord</div><div id="dept-mail-password" style="font-family:monospace;filter:blur(6px);user-select:none"><?= htmlspecialchars($deptCreds['mail_password_plain'] ?? '—') ?></div></div>
            <div style="display:flex;align-items:end"><button type="button" class="btn btn-secondary btn-sm" onclick="toggleDeptMailPassword()">Toon / verberg</button></div>
          </div>
          <?php if (!empty($deptCreds['extra_notes'])): ?>
            <div style="margin-top:1rem;padding:.85rem 1rem;background:rgba(0,180,216,.06);border:1px solid rgba(0,180,216,.15);border-radius:10px;white-space:pre-wrap"><?= htmlspecialchars($deptCreds['extra_notes']) ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- E-mailadressen lijst -->
    <div class="section-card" style="margin-bottom:1.5rem">
      <div class="section-card-header">
        <span class="section-card-title">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
          E-mailadressen (<?= count($emails) ?>)
        </span>
      </div>
      <?php if ($emails): ?>
      <div style="padding:.5rem 0">
        <?php foreach ($emails as $em): ?>
        <div style="display:flex;align-items:center;gap:1rem;padding:.9rem 1.5rem;border-bottom:1px solid rgba(0,180,216,.06)">
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:.92rem;color:var(--white)"><?= htmlspecialchars($em['label']) ?></div>
            <a href="mailto:<?= htmlspecialchars($em['email_address']) ?>" style="color:var(--accent);font-size:.88rem"><?= htmlspecialchars($em['email_address']) ?></a>
            <?php if ($em['description']): ?>
            <div style="font-size:.8rem;color:var(--text-dim);margin-top:.15rem"><?= htmlspecialchars($em['description']) ?></div>
            <?php endif; ?>
          </div>
          <a href="mailto:<?= htmlspecialchars($em['email_address']) ?>" class="btn btn-secondary btn-sm">
            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            Mail sturen
          </a>
          <?php if ($isAdmin): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="delete_email">
            <input type="hidden" name="email_id" value="<?= $em['id'] ?>">
            <input type="hidden" name="dept" value="<?= htmlspecialchars($selectedDept) ?>">
            <button type="submit" class="btn-icon del" title="Verwijderen"
              data-confirm-delete="E-mailadres verwijderen?"
              aria-label="Verwijder e-mailadres">
              <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            </button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="padding:2.5rem;text-align:center;color:var(--text-dim)">Geen e-mailadressen gevonden voor deze afdeling.</div>
      <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Nieuw e-mailadres toevoegen (admin only) -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">E-mailadres toevoegen</span>
      </div>
      <div class="section-card-body">
        <form method="POST" action="?dept=<?= urlencode($selectedDept) ?>">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="add_email">
          <input type="hidden" name="dept_slug" value="<?= htmlspecialchars($selectedDept) ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group" style="margin:0">
              <label class="form-label">Label <span class="req">*</span></label>
              <input type="text" name="label" class="form-control" placeholder="bijv. Nieuwsbrief" required maxlength="100">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">E-mailadres <span class="req">*</span></label>
              <input type="email" name="email_address" class="form-control" placeholder="afdeling@soulfm.nl" required maxlength="150">
            </div>
            <div class="form-group" style="margin:0;grid-column:1/-1">
              <label class="form-label">Beschrijving</label>
              <input type="text" name="description" class="form-control" placeholder="Optionele omschrijving" maxlength="200">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-top:1rem">
            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Toevoegen
          </button>
        </form>
      </div>
    </div>

    <div class="section-card" style="margin-top:1.5rem">
      <div class="section-card-header">
        <span class="section-card-title">Afdelingsmail inloggegevens instellen</span>
      </div>
      <div class="section-card-body">
        <form method="POST" action="?dept=<?= urlencode($selectedDept) ?>">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="save_login_creds">
          <input type="hidden" name="dept_slug" value="<?= htmlspecialchars($selectedDept) ?>">

          <div class="form-group">
            <label class="form-label">E-mailadres <span class="req">*</span></label>
            <input type="email" name="mail_address" class="form-control" required maxlength="150"
              value="<?= htmlspecialchars($deptCreds['mail_address'] ?? ($selectedDept . '@soulfm.nl')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Wachtwoord <?= $deptCreds ? '<span style="font-weight:400;color:var(--text-dim)">(leeg laten = ongewijzigd)</span>' : '<span class="req">*</span>' ?></label>
            <input type="password" name="mail_password" class="form-control" <?= $deptCreds ? '' : 'required' ?> autocomplete="new-password">
          </div>
          <div style="display:grid;grid-template-columns:1fr 100px;gap:.75rem">
            <div class="form-group">
              <label class="form-label">IMAP server</label>
              <input type="text" name="imap_server" class="form-control" value="<?= htmlspecialchars($deptCreds['imap_server'] ?? 'mail.soulfm.nl') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Poort</label>
              <input type="number" name="imap_port" class="form-control" min="1" max="65535" value="<?= htmlspecialchars((string)($deptCreds['imap_port'] ?? 993)) ?>">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 100px;gap:.75rem">
            <div class="form-group">
              <label class="form-label">SMTP server</label>
              <input type="text" name="smtp_server" class="form-control" value="<?= htmlspecialchars($deptCreds['smtp_server'] ?? 'mail.soulfm.nl') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Poort</label>
              <input type="number" name="smtp_port" class="form-control" min="1" max="65535" value="<?= htmlspecialchars((string)($deptCreds['smtp_port'] ?? 587)) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Notities</label>
            <textarea name="extra_notes" class="form-control" rows="3"><?= htmlspecialchars($deptCreds['extra_notes'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
            Inloggegevens opslaan
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:4rem;color:var(--text-dim)">Selecteer een afdeling in het menu.</div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleDeptMailPassword() {
  const el = document.getElementById('dept-mail-password');
  if (!el) return;
  const hidden = el.style.filter !== 'none';
  el.style.filter = hidden ? 'none' : 'blur(6px)';
  el.style.userSelect = hidden ? 'text' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
