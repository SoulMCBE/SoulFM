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
$isAdmin    = ($userRole === 'admin' || $userRole === 'moderator');

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

// Admin: beheer e-mails toevoegen/verwijderen
$msg = '';
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
        }
        $emails = getDepartmentEmails($selectedDept);

    } elseif ($action === 'delete_email') {
        $eid = (int)($_POST['email_id'] ?? 0);
        if ($eid) {
            $pdo->prepare('DELETE FROM department_emails WHERE id=?')->execute([$eid]);
            $msg = 'E-mailadres verwijderd.';
        }
        $emails = getDepartmentEmails($selectedDept);
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
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
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:4rem;color:var(--text-dim)">Selecteer een afdeling in het menu.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
