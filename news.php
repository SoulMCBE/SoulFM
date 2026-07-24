<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Nieuws';
$activePage = 'news';

$perPage    = 6;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

try {
    $pdo = getPDO();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM news WHERE published = 1')->fetchColumn();
    $stmt  = $pdo->prepare('
        SELECT n.*, u.username as author_name 
        FROM news n 
        LEFT JOIN users u ON n.author_id = u.id 
        WHERE n.published = 1 
        ORDER BY n.created_at DESC 
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$perPage, $offset]);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
    $total    = 0;
}

$totalPages = ceil($total / $perPage);

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span> <span>Nieuws</span></div>
    <h1>Nieuws &amp; Blog</h1>
    <p>Blijf op de hoogte van het laatste nieuws, aankondigingen en verhalen van SoulFM.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if ($articles): ?>
    <div class="news-grid news-page">
      <?php foreach ($articles as $article): ?>
      <article class="card">
        <div class="card-img">
          <?php if (!empty($article['image'])): ?>
            <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="card-img-placeholder">
              <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="card-meta">
            <span><?= formatDate($article['created_at']) ?></span>
            <span>door <?= htmlspecialchars($article['author_name'] ?? 'Redactie') ?></span>
          </div>
          <h2 class="card-title">
            <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a>
          </h2>
          <p class="card-excerpt"><?= htmlspecialchars(truncate($article['excerpt'] ?? $article['content'], 140)) ?></p>
          <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($article['slug']) ?>" class="btn btn-outline btn-sm">Lees meer</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagina navigatie">
      <?php if ($currentPage > 1): ?>
        <a href="?page=<?= $currentPage - 1 ?>" aria-label="Vorige pagina">&laquo;</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $currentPage): ?>
          <span class="current" aria-current="page"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($currentPage < $totalPages): ?>
        <a href="?page=<?= $currentPage + 1 ?>" aria-label="Volgende pagina">&raquo;</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:4rem;color:var(--color-text-dim)">
      <svg viewBox="0 0 24 24" style="width:64px;height:64px;fill:currentColor;margin:0 auto 1rem;opacity:.3"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
      <p>Geen nieuwsartikelen beschikbaar.</p>
    </div>
    <?php endif; ?>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
