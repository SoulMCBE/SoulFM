<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('manage_content')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle  = 'Instellingen';
$activePage = 'settings';
$csrf       = generateCsrfToken();
$msg        = '';
$pdo        = getPDO();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $allowedKeys = [
        'site_name','tagline','stream_url','primary_color','logo_text',
        'facebook_url','twitter_url','instagram_url',
        'contact_email','contact_phone','contact_address',
        'meta_description','about_text'
    ];

    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

    foreach ($allowedKeys as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);
            // Validate color
            if ($key === 'primary_color' && !preg_match('/^#[0-9a-fA-F]{3,6}$/', $value)) {
                $value = '#00b4d8'; // Default
            }
            $stmt->execute([$key, $value]);
        }
    }

    // Regenerate settings cache
    global $_settings_cache;
    $_settings_cache = null;

    $msg = 'Instellingen opgeslagen!';
}

$settings = getSettings();

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;flex-shrink:0"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Instellingen</h1>
    <p>Beheer de website-instellingen zonder code te bewerken.</p>
  </div>
</div>

<form method="POST" action="" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

    <!-- General settings -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          Algemeen
        </span>
      </div>
      <div class="section-card-body">
        <div class="form-group">
          <label class="form-label">Sitenaam</label>
          <input type="text" name="site_name" class="form-control"
            value="<?= htmlspecialchars($settings['site_name'] ?? 'SoulFM') ?>" maxlength="50">
        </div>
        <div class="form-group">
          <label class="form-label">Tagline</label>
          <input type="text" name="tagline" class="form-control"
            value="<?= htmlspecialchars($settings['tagline'] ?? '') ?>" maxlength="100">
        </div>
        <div class="form-group">
          <label class="form-label">Meta beschrijving</label>
          <textarea name="meta_description" class="form-control" rows="3"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
          <div class="form-hint">Wordt gebruikt voor zoekmachines. Max 160 tekens aanbevolen.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Over ons tekst</label>
          <textarea name="about_text" class="form-control" rows="4"><?= htmlspecialchars($settings['about_text'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Stream settings -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
          Stream & Uiterlijk
        </span>
      </div>
      <div class="section-card-body">
        <div class="form-group">
          <label class="form-label">Stream URL</label>
          <input type="url" name="stream_url" class="form-control"
            value="<?= htmlspecialchars($settings['stream_url'] ?? '') ?>"
            placeholder="https://stream.icecast.org/live">
          <div class="form-hint">De URL van je Icecast/Shoutcast/Azuracast stream.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Logo tekst</label>
          <input type="text" name="logo_text" class="form-control"
            value="<?= htmlspecialchars($settings['logo_text'] ?? 'SoulFM') ?>" maxlength="20">
        </div>

        <div class="form-group">
          <label class="form-label">Accentkleur</label>
          <div style="display:flex;gap:.75rem;align-items:center">
            <input type="color" name="primary_color"
              value="<?= htmlspecialchars($settings['primary_color'] ?? '#00b4d8') ?>"
              style="width:48px;height:38px;padding:2px;background:none;border:1px solid rgba(0,180,216,.2);border-radius:6px;cursor:pointer">
            <input type="text" class="form-control" style="flex:1"
              value="<?= htmlspecialchars($settings['primary_color'] ?? '#00b4d8') ?>"
              placeholder="#00b4d8" readonly
              id="color-hex-display">
          </div>
          <div class="form-hint">Gebruik deze kleur voor accenten op de website.</div>
        </div>
      </div>
    </div>

    <!-- Social media -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>
          Social Media
        </span>
      </div>
      <div class="section-card-body">
        <div class="form-group">
          <label class="form-label">Facebook URL</label>
          <input type="url" name="facebook_url" class="form-control"
            value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>"
            placeholder="https://facebook.com/soulfm">
        </div>
        <div class="form-group">
          <label class="form-label">Twitter/X URL</label>
          <input type="url" name="twitter_url" class="form-control"
            value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>"
            placeholder="https://twitter.com/soulfm">
        </div>
        <div class="form-group">
          <label class="form-label">Instagram URL</label>
          <input type="url" name="instagram_url" class="form-control"
            value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>"
            placeholder="https://instagram.com/soulfm">
        </div>
      </div>
    </div>

    <!-- Contact info -->
    <div class="section-card">
      <div class="section-card-header">
        <span class="section-card-title">
          <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
          Contactgegevens
        </span>
      </div>
      <div class="section-card-body">
        <div class="form-group">
          <label class="form-label">Contact e-mail</label>
          <input type="email" name="contact_email" class="form-control"
            value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>"
            placeholder="info@soulfm.nl">
        </div>
        <div class="form-group">
          <label class="form-label">Telefoonnummer</label>
          <input type="text" name="contact_phone" class="form-control"
            value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>"
            placeholder="+31 20 123 4567">
        </div>
        <div class="form-group">
          <label class="form-label">Adres</label>
          <textarea name="contact_address" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

  </div>

  <div style="margin-top:1.5rem;display:flex;gap:1rem">
    <button type="submit" class="btn btn-primary btn-sm" style="padding:.75rem 2rem">
      <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
      Instellingen opslaan
    </button>
    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-ghost btn-sm">Website bekijken →</a>
  </div>
</form>

<script>
// Sync color picker with text input
document.querySelector('[name="primary_color"]')?.addEventListener('input', function() {
  document.getElementById('color-hex-display').value = this.value;
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
