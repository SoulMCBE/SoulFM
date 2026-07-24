<?php
/**
 * SoulFM - Mijn Bedrijfsmail
 * Elke ingelogde medewerker (niet-listener) ziet hier zijn eigen
 * @soulfm.nl e-mailcredentials zoals ingesteld door de admin.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('view_own_mail')) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:2rem;text-align:center;background:#0a1628;color:#e8f0fe;min-height:100vh"><h2 style="color:#f87171">Geen toegang</h2><p>Jouw rol heeft geen bedrijfsmail.</p><a href="' . BASE_URL . '/admin/dashboard.php" style="color:#00b4d8">Terug</a></div>');
}

$pageTitle   = 'Mijn Bedrijfsmail';
$activePage  = 'mijn-email';
$currentUser = getCurrentUser();
$creds       = getUserMailCredentials((int)$_SESSION['user_id']);

// Bijhouden of wachtwoord zichtbaar is (via POST toggle, geen DB-actie)
$showPassword = isset($_POST['show_password']) && $_POST['show_password'] === '1';
$csrf         = generateCsrfToken();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-header-admin">
  <div>
    <h1>Mijn Bedrijfsmail</h1>
    <p>Jouw persoonlijke <strong style="color:var(--accent)">@soulfm.nl</strong> e-mailgegevens. Bewaar deze op een veilige plek.</p>
  </div>
</div>

<?php if (!$creds): ?>
<!-- Nog geen credentials ingesteld -->
<div style="max-width:560px">
  <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:14px;padding:2rem;text-align:center">
    <div style="width:56px;height:56px;background:rgba(245,158,11,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
      <svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:#fbbf24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    </div>
    <h3 style="color:#fbbf24;margin-bottom:.5rem;font-size:1.1rem">Nog niet ingesteld</h3>
    <p style="color:var(--text-dim);font-size:.9rem;margin-bottom:1.5rem">
      Je bedrijfsmail-credentials zijn nog niet ingesteld door de admin.<br>
      Neem contact op met de systeembeheerder.
    </p>
    <a href="mailto:<?= htmlspecialchars(getSetting('contact_email','admin@soulfm.nl')) ?>" class="btn btn-secondary btn-sm">
      <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
      Contact admin
    </a>
  </div>
</div>

<?php else: ?>
<div style="max-width:660px;display:flex;flex-direction:column;gap:1.5rem">

  <!-- Hoofd credentials kaart -->
  <div class="section-card" style="overflow:visible">
    <div class="section-card-header">
      <span class="section-card-title">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        Jouw e-mailaccount
      </span>
      <span style="font-size:.75rem;color:var(--text-dim)">
        Bijgewerkt: <?= formatRelativeDate($creds['updated_at']) ?>
      </span>
    </div>
    <div class="section-card-body">

      <!-- E-mailadres -->
      <div style="margin-bottom:1.5rem">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-dim);margin-bottom:.4rem">E-mailadres</div>
        <div style="display:flex;align-items:center;gap:.75rem">
          <div style="flex:1;background:rgba(0,0,0,.25);border:1px solid rgba(0,180,216,.15);border-radius:8px;padding:.75rem 1rem;font-size:1rem;font-weight:600;color:var(--accent)">
            <?= htmlspecialchars($creds['mail_address']) ?>
          </div>
          <button onclick="copyToClipboard('<?= htmlspecialchars(addslashes($creds['mail_address'])) ?>', this)"
            class="btn btn-secondary btn-sm" title="Kopiëren">
            <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
            Kopieer
          </button>
        </div>
      </div>

      <!-- Wachtwoord -->
      <div style="margin-bottom:1.5rem">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-dim);margin-bottom:.4rem">Wachtwoord</div>
        <div style="display:flex;align-items:center;gap:.75rem">
          <div style="flex:1;background:rgba(0,0,0,.25);border:1px solid rgba(0,180,216,.15);border-radius:8px;padding:.75rem 1rem;font-size:1rem;font-family:monospace;letter-spacing:.1em;color:var(--white);position:relative">
            <span id="pw-display" style="filter:<?= $showPassword ? 'none' : 'blur(6px)' ?>;user-select:<?= $showPassword ? 'text' : 'none' ?>;transition:filter .2s">
              <?= htmlspecialchars($creds['mail_password_plain'] ?? '—') ?>
            </span>
          </div>
          <div style="display:flex;flex-direction:column;gap:.4rem">
            <button id="toggle-pw-btn" onclick="togglePassword(this)" class="btn btn-secondary btn-sm" title="Toon/verberg wachtwoord">
              <svg id="eye-icon" viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              Toon
            </button>
            <button onclick="copyToClipboard('<?= htmlspecialchars(addslashes($creds['mail_password_plain'] ?? '')) ?>', this)"
              class="btn btn-secondary btn-sm" title="Kopieer wachtwoord">
              <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
              Kopieer
            </button>
          </div>
        </div>
        <div style="font-size:.75rem;color:var(--text-dim);margin-top:.4rem">
          <svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor;display:inline;vertical-align:middle;margin-right:3px"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
          Klik op "Toon" om het wachtwoord zichtbaar te maken. Deel dit nooit met anderen.
        </div>
      </div>

    </div>
  </div>

  <!-- Serverinstellingen -->
  <div class="section-card">
    <div class="section-card-header">
      <span class="section-card-title">
        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
        Serverinstellingen
      </span>
    </div>
    <div class="section-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

        <!-- IMAP -->
        <div style="background:rgba(0,0,0,.2);border:1px solid rgba(0,180,216,.1);border-radius:10px;padding:1.25rem">
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
            <div style="width:8px;height:8px;background:#10b981;border-radius:50%"></div>
            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#34d399">IMAP — Ontvangen</span>
          </div>
          <?php
          $imapRows = [
              'Server'    => $creds['imap_server'],
              'Poort'     => (string)$creds['imap_port'],
              'Beveiliging' => $creds['imap_port'] == 993 ? 'SSL/TLS' : 'STARTTLS',
              'Gebruiker' => $creds['mail_address'],
          ];
          foreach ($imapRows as $label => $value): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
            <span style="font-size:.8rem;color:var(--text-dim)"><?= $label ?></span>
            <span style="font-size:.85rem;font-weight:600;color:var(--white);font-family:monospace"><?= htmlspecialchars($value) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- SMTP -->
        <div style="background:rgba(0,0,0,.2);border:1px solid rgba(0,180,216,.1);border-radius:10px;padding:1.25rem">
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
            <div style="width:8px;height:8px;background:#3b82f6;border-radius:50%"></div>
            <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#60a5fa">SMTP — Verzenden</span>
          </div>
          <?php
          $smtpRows = [
              'Server'      => $creds['smtp_server'],
              'Poort'       => (string)$creds['smtp_port'],
              'Beveiliging' => $creds['smtp_port'] == 465 ? 'SSL/TLS' : 'STARTTLS',
              'Gebruiker'   => $creds['mail_address'],
          ];
          foreach ($smtpRows as $label => $value): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
            <span style="font-size:.8rem;color:var(--text-dim)"><?= $label ?></span>
            <span style="font-size:.85rem;font-weight:600;color:var(--white);font-family:monospace"><?= htmlspecialchars($value) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

      </div>

      <!-- Snelkoppelings-instructies -->
      <div style="margin-top:1.5rem;background:rgba(0,180,216,.05);border:1px solid rgba(0,180,216,.12);border-radius:10px;padding:1.25rem">
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--accent);margin-bottom:.75rem">Instellen in een e-mailprogramma</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;font-size:.83rem;color:var(--text-dim)">
          <div>📱 <strong style="color:var(--white)">iPhone/iPad:</strong> Instellingen → Mail → Account toevoegen → Overig</div>
          <div>🤖 <strong style="color:var(--white)">Android:</strong> Gmail-app → Account toevoegen → Overig (IMAP)</div>
          <div>🖥 <strong style="color:var(--white)">Outlook:</strong> Bestand → Account toevoegen → Handmatige instelling</div>
          <div>📧 <strong style="color:var(--white)">Thunderbird:</strong> Account instellingen → Nieuw account → E-mail</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Extra notities van admin (indien aanwezig) -->
  <?php if (!empty($creds['extra_notes'])): ?>
  <div class="section-card">
    <div class="section-card-header">
      <span class="section-card-title">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        Notities van de beheerder
      </span>
    </div>
    <div class="section-card-body">
      <div style="font-size:.9rem;color:var(--text);line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars($creds['extra_notes']) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Beveiligingstips -->
  <div style="background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.15);border-radius:12px;padding:1.25rem">
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#f87171;margin-bottom:.75rem">
      🔒 Beveiligingstips
    </div>
    <ul style="font-size:.83rem;color:var(--text-dim);display:flex;flex-direction:column;gap:.4rem">
      <li>• Deel je wachtwoord <strong style="color:var(--white)">nooit</strong> met collega's of via chat/e-mail</li>
      <li>• Gebruik dit wachtwoord <strong style="color:var(--white)">alleen</strong> voor je SoulFM bedrijfsmail</li>
      <li>• Log uit op gedeelde apparaten na gebruik</li>
      <li>• Meld een verdacht inlogpoging direct aan de admin</li>
    </ul>
  </div>

</div><!-- /max-width wrapper -->
<?php endif; ?>

<script>
function togglePassword(btn) {
    const display = document.getElementById('pw-display');
    const isHidden = display.style.filter !== 'none';
    display.style.filter      = isHidden ? 'none' : 'blur(6px)';
    display.style.userSelect  = isHidden ? 'text' : 'none';
    btn.innerHTML = isHidden
        ? '<svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg> Verberg'
        : '<svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg> Toon';
}

function copyToClipboard(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Gekopieerd!';
        btn.style.color = '#34d399';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
