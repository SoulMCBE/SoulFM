<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Ons Team';
$activePage = 'team';
$teamMembers = getPublicTeamMembers();
$memberCount = count($teamMembers);

require_once __DIR__ . '/includes/header.php';

function teamInitials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials ?: '?';
}
?>

<main id="main-content">
<div class="page-header page-header-team">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Team</span></div>
    <span class="section-subtitle">Achter de microfoon</span>
    <h1>Ontmoet ons team</h1>
    <p>Passievolle DJ's, redacteuren en makers die SoulFM tot leven brengen — elke dag opnieuw.</p>
    <?php if ($memberCount > 0): ?>
    <div class="team-hero-stats">
      <div class="team-stat">
        <span class="team-stat-value"><?= $memberCount ?></span>
        <span class="team-stat-label">Teamleden</span>
      </div>
      <div class="team-stat">
        <span class="team-stat-value">24/7</span>
        <span class="team-stat-label">Op de lucht</span>
      </div>
      <div class="team-stat">
        <span class="team-stat-value">♪</span>
        <span class="team-stat-label">Soul &amp; R&amp;B</span>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="team-header-glow" aria-hidden="true"></div>
</div>

<section class="section team-section">
  <div class="container">
    <?php if ($teamMembers): ?>
    <div class="team-grid">
      <?php foreach ($teamMembers as $i => $member): ?>
      <article class="team-card" style="--team-delay: <?= $i * 0.08 ?>s">
        <div class="team-card-photo">
          <?php if (!empty($member['photo_url'])): ?>
            <img src="<?= htmlspecialchars($member['photo_url']) ?>" alt="<?= htmlspecialchars($member['name']) ?>" loading="lazy">
          <?php else: ?>
            <div class="team-card-avatar" aria-hidden="true"><?= htmlspecialchars(teamInitials($member['name'])) ?></div>
          <?php endif; ?>
          <div class="team-card-overlay" aria-hidden="true"></div>
        </div>
        <div class="team-card-content">
          <div class="team-card-role"><?= htmlspecialchars($member['role_title']) ?></div>
          <h2 class="team-card-name"><?= htmlspecialchars($member['name']) ?></h2>
          <div class="team-card-divider" aria-hidden="true"></div>
          <?php if (!empty($member['bio'])): ?>
          <p class="team-card-bio"><?= nl2br(htmlspecialchars($member['bio'])) ?></p>
          <?php else: ?>
          <p class="team-card-bio team-card-bio--placeholder">Meer informatie over dit teamlid volgt binnenkort.</p>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="team-empty">
      <div class="team-empty-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      </div>
      <h2>Nog geen teamleden</h2>
      <p>Er zijn nog geen teamleden gepubliceerd. Kom binnenkort terug!</p>
    </div>
    <?php endif; ?>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
