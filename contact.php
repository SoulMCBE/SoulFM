<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Contact';
$activePage = 'contact';
$settings   = getSettings();
$csrf       = generateCsrfToken();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Contact</span></div>
    <h1>Neem Contact Op</h1>
    <p>Heb je een vraag, opmerking of wil je samenwerken? We horen graag van je!</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="contact-grid">

      <!-- Contact form -->
      <div>
        <h2 style="font-size:1.6rem;margin-bottom:0.5rem">Stuur ons een bericht</h2>
        <p style="color:var(--color-text-dim);margin-bottom:2rem">We proberen altijd binnen 24 uur te reageren.</p>

        <form id="contact-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">
            <div class="form-group">
              <label class="form-label" for="contact-name">Naam <span class="required">*</span></label>
              <input type="text" id="contact-name" name="name" class="form-control" placeholder="Jouw naam" required maxlength="100" autocomplete="name">
            </div>
            <div class="form-group">
              <label class="form-label" for="contact-email">E-mailadres <span class="required">*</span></label>
              <input type="email" id="contact-email" name="email" class="form-control" placeholder="jouw@email.nl" required maxlength="100" autocomplete="email">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="contact-subject">Onderwerp <span class="required">*</span></label>
            <select id="contact-subject" name="subject" class="form-control" required>
              <option value="" disabled selected>Selecteer een onderwerp</option>
              <option value="Algemene vraag">Algemene vraag</option>
              <option value="Adverteren">Adverteren</option>
              <option value="Technische problemen">Technische problemen</option>
              <option value="DJ aanmelding">DJ aanmelding</option>
              <option value="Samenwerking">Samenwerking</option>
              <option value="Anders">Anders</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="contact-message">Bericht <span class="required">*</span></label>
            <textarea id="contact-message" name="message" class="form-control" placeholder="Schrijf hier jouw bericht..." required maxlength="2000" style="min-height:160px"></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
            <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            Bericht versturen
          </button>
        </form>
      </div>

      <!-- Contact info -->
      <div>
        <h2 style="font-size:1.6rem;margin-bottom:0.5rem">Contactgegevens</h2>
        <p style="color:var(--color-text-dim);margin-bottom:2rem">Je kunt ons ook direct bereiken via onderstaande gegevens.</p>

        <div class="contact-info">
          <?php if (!empty($settings['contact_address'])): ?>
          <div class="contact-item">
            <div class="contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </div>
            <div>
              <h4>Adres</h4>
              <p><?= nl2br(htmlspecialchars($settings['contact_address'])) ?></p>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($settings['contact_email'])): ?>
          <div class="contact-item">
            <div class="contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>
            <div>
              <h4>E-mail</h4>
              <p><a href="mailto:<?= htmlspecialchars($settings['contact_email']) ?>"><?= htmlspecialchars($settings['contact_email']) ?></a></p>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($settings['contact_phone'])): ?>
          <div class="contact-item">
            <div class="contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
            </div>
            <div>
              <h4>Telefoon</h4>
              <p><a href="tel:<?= htmlspecialchars(preg_replace('/[^+\d]/', '', $settings['contact_phone'])) ?>"><?= htmlspecialchars($settings['contact_phone']) ?></a></p>
            </div>
          </div>
          <?php endif; ?>

          <!-- Social media -->
          <div class="contact-item">
            <div class="contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>
            </div>
            <div>
              <h4>Social Media</h4>
              <div style="display:flex;gap:0.75rem;margin-top:0.5rem">
                <?php if (!empty($settings['facebook_url'])): ?>
                <a href="<?= htmlspecialchars($settings['facebook_url']) ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook">
                  <svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['twitter_url'])): ?>
                <a href="<?= htmlspecialchars($settings['twitter_url']) ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Twitter">
                  <svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['instagram_url'])): ?>
                <a href="<?= htmlspecialchars($settings['instagram_url']) ?>" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Instagram">
                  <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
