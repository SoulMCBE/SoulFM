<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'Verzoekje doen';
$activePage  = 'request';
$playedRequests = getPlayedRequests(5);
$csrf = generateCsrfToken();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Verzoekje doen</span></div>
    <h1>Doe een Verzoekje</h1>
    <p>Wil jij een nummer horen? Stuur je verzoekje in en onze DJ's draaien het zo snel mogelijk!</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start">

      <!-- Form -->
      <div>
        <h2 style="font-size:1.6rem;margin-bottom:0.5rem">Jouw verzoekje</h2>
        <p style="color:var(--color-text-dim);margin-bottom:2rem">Vul het formulier in en we doen ons best om jouw nummer te draaien.</p>

        <form id="request-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <div class="form-group">
            <label class="form-label" for="song_title">Songtitel <span class="required">*</span></label>
            <input type="text" id="song_title" name="song_title" class="form-control" placeholder="bijv. I Will Always Love You" required maxlength="200" autocomplete="off">
          </div>

          <div class="form-group">
            <label class="form-label" for="artist_name">Artiest <span class="required">*</span></label>
            <input type="text" id="artist_name" name="artist_name" class="form-control" placeholder="bijv. Whitney Houston" required maxlength="200" autocomplete="off">
          </div>

          <div class="form-group">
            <label class="form-label" for="requester_name">Jouw naam <span class="required">*</span></label>
            <input type="text" id="requester_name" name="requester_name" class="form-control" placeholder="bijv. Jan uit Amsterdam" required maxlength="100" autocomplete="name">
          </div>

          <div class="form-group">
            <label class="form-label" for="message">Berichtje of opdracht <span style="color:var(--color-text-dim);font-weight:400">(optioneel)</span></label>
            <textarea id="message" name="message" class="form-control" placeholder="Voor mijn lieve vrouw op haar verjaardag..." maxlength="500"></textarea>
          </div>

          <div class="form-group">
            <label class="form-check">
              <input type="checkbox" name="agree_terms" required>
              <span class="form-check-label">Ik ga akkoord met de <a href="<?= BASE_URL ?>/contact.php">huisregels</a> van SoulFM.</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
            <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            Verzoekje insturen
          </button>

          <p style="color:var(--color-text-dim);font-size:0.82rem;margin-top:1rem;text-align:center">
            Je kunt elke <?= REQUEST_RATE_LIMIT ?> minuten één verzoekje insturen.
          </p>
        </form>
      </div>

      <!-- Recently played -->
      <div>
        <h2 style="font-size:1.6rem;margin-bottom:0.5rem">Recent gespeeld</h2>
        <p style="color:var(--color-text-dim);margin-bottom:1.5rem">Deze verzoekjes zijn onlangs gedraaid.</p>

        <?php if ($playedRequests): ?>
        <div class="requests-list">
          <?php foreach ($playedRequests as $req): ?>
          <div class="request-item">
            <div style="width:38px;height:38px;background:rgba(0,180,216,0.12);border:1px solid rgba(0,180,216,0.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0" aria-hidden="true">
              <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:var(--color-accent)"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
            </div>
            <div class="song-info">
              <div class="song-title"><?= htmlspecialchars($req['song_title']) ?></div>
              <div class="song-artist"><?= htmlspecialchars($req['artist_name']) ?></div>
              <div class="requester">aangevraagd door <?= htmlspecialchars($req['requester_name']) ?></div>
            </div>
            <span style="font-size:0.72rem;color:var(--color-text-dim);white-space:nowrap"><?= formatRelativeDate($req['played_at'] ?? $req['created_at']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:2rem;color:var(--color-text-dim);background:rgba(30,58,95,0.2);border-radius:12px;border:1px solid rgba(0,180,216,0.1)">
          <p>Nog geen verzoekjes gespeeld. Wees de eerste!</p>
        </div>
        <?php endif; ?>

        <div style="margin-top:2rem;padding:1.5rem;background:rgba(0,180,216,0.07);border:1px solid rgba(0,180,216,0.15);border-radius:12px">
          <h3 style="font-size:1rem;margin-bottom:0.5rem">💡 Tips</h3>
          <ul style="color:var(--color-text-dim);font-size:0.88rem;display:flex;flex-direction:column;gap:0.4rem">
            <li>• Vermeld de exacte songtitel voor de beste kans</li>
            <li>• Voeg een persoonlijk berichtje toe</li>
            <li>• Niet alle verzoekjes kunnen worden gespeeld</li>
            <li>• Je kunt elke <?= REQUEST_RATE_LIMIT ?> minuten een verzoekje doen</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
