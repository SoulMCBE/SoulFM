<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: ' . BASE_URL . '/news.php');
    exit;
}

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('
        SELECT n.*, u.username as author_name 
        FROM news n 
        LEFT JOIN users u ON n.author_id = u.id 
        WHERE n.slug = ? AND n.published = 1 
        LIMIT 1
    ');
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle  = 'Artikel niet gevonden';
    $activePage = 'news';
    require_once __DIR__ . '/includes/header.php';
    echo '<main><section class="section"><div class="container" style="text-align:center;padding:4rem 0"><h1>Artikel niet gevonden</h1><p style="color:var(--color-text-dim)">Dit artikel bestaat niet of is niet gepubliceerd.</p><a href="' . BASE_URL . '/news.php" class="btn btn-outline" style="margin-top:1.5rem">Terug naar nieuws</a></div></section></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle  = $article['title'];
$activePage = 'news';

// Related articles
try {
    $stmt2 = $pdo->prepare('SELECT id, title, slug, created_at FROM news WHERE published=1 AND id != ? ORDER BY created_at DESC LIMIT 3');
    $stmt2->execute([$article['id']]);
    $related = $stmt2->fetchAll();
} catch (PDOException $e) { $related = []; }

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= BASE_URL ?>/index.php">Home</a> <span>›</span>
      <a href="<?= BASE_URL ?>/news.php">Nieuws</a> <span>›</span>
      <span><?= htmlspecialchars(truncate($article['title'], 50)) ?></span>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 300px;gap:4rem;align-items:start">

      <article class="news-article">
        <div class="article-meta">
          <span><?= formatDate($article['created_at']) ?></span>
          &bull;
          <span>door <?= htmlspecialchars($article['author_name'] ?? 'Redactie') ?></span>
        </div>
        <h1><?= htmlspecialchars($article['title']) ?></h1>

        <?php if (!empty($article['image'])): ?>
        <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" loading="lazy">
        <?php endif; ?>

        <div class="article-content">
          <?= $article['content'] /* Stored as HTML in DB - sanitize when saving */ ?>
        </div>

        <div style="margin-top:2.5rem;padding-top:2rem;border-top:1px solid rgba(0,180,216,0.1)">
          <a href="<?= BASE_URL ?>/news.php" class="btn btn-outline btn-sm">
            ← Terug naar nieuws
          </a>
        </div>
      </article>

      <!-- Sidebar -->
      <aside>
        <?php if ($related): ?>
        <div class="card" style="padding:1.5rem">
          <h3 style="font-size:1rem;margin-bottom:1.25rem;font-family:var(--font-body)">Meer nieuws</h3>
          <div style="display:flex;flex-direction:column;gap:1rem">
            <?php foreach ($related as $rel): ?>
            <div style="border-bottom:1px solid rgba(0,180,216,0.08);padding-bottom:1rem">
              <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($rel['slug']) ?>" style="color:var(--color-white);font-size:0.9rem;font-weight:500;display:block;margin-bottom:0.2rem"><?= htmlspecialchars($rel['title']) ?></a>
              <span style="font-size:0.78rem;color:var(--color-text-dim)"><?= formatDate($rel['created_at']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="card" style="padding:1.5rem;margin-top:1.5rem">
          <h3 style="font-size:1rem;margin-bottom:1rem;font-family:var(--font-body)">Luister nu live</h3>
          <p style="color:var(--color-text-dim);font-size:0.88rem;margin-bottom:1.25rem">Mis niets en luister 24/7 naar SoulFM!</p>
          <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary" style="width:100%;justify-content:center">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M8 5v14l11-7z"/></svg>
            Luister Live
          </a>
        </div>
      </aside>
    </div>
  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
