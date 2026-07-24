<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Ons Team';
$activePage = 'team';
$teamMembers = getPublicTeamMembers();

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Team</span></div>
    <h1>Ontmoet ons team</h1>
    <p>Dit zijn de mensen achter SoulFM die dagelijks zorgen voor muziek, content en uitzendingen.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if ($teamMembers): ?>
    <div class="team-grid">
      <?php foreach ($teamMembers as $member): ?>
      <article class="card team-card">
        <div class="card-img team-card-img">
          <?php if (!empty($member['photo_url'])): ?>
            <img src="<?= htmlspecialchars($member['photo_url']) ?>" alt="<?= htmlspecialchars($member['name']) ?>" loading="lazy">
          <?php else: ?>
            <div class="card-img-placeholder">
              <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <h2 class="card-title" style="margin-bottom:.35rem"><?= htmlspecialchars($member['name']) ?></h2>
          <div class="genre-badge" style="margin-bottom:1rem"><?= htmlspecialchars($member['role_title']) ?></div>
          <?php if (!empty($member['bio'])): ?>
          <p class="card-excerpt" style="margin-bottom:0"><?= nl2br(htmlspecialchars($member['bio'])) ?></p>
          <?php else: ?>
          <p class="card-excerpt" style="margin-bottom:0">Meer informatie over dit teamlid volgt binnenkort.</p>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem;color:var(--color-text-dim)">
      <svg viewBox="0 0 24 24" style="width:64px;height:64px;fill:currentColor;margin:0 auto 1rem;opacity:.3"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      <p>Er zijn nog geen teamleden gepubliceerd.</p>
    </div>
    <?php endif; ?>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
