<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('manage_news')) { http_response_code(403); die('Toegang geweigerd'); }

$id      = (int)($_GET['id'] ?? 0);
$csrf    = generateCsrfToken();
$errors  = [];
$success = false;
$article = null;

$pdo = getPDO();

// Load existing article for editing
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    if (!$article) { header('Location: ' . BASE_URL . '/admin/news.php'); exit; }
}

$pageTitle  = $id ? 'Artikel bewerken' : 'Nieuw artikel';
$activePage = 'news';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $title     = trim($_POST['title'] ?? '');
    $slug      = trim($_POST['slug'] ?? '') ?: generateSlug($title);
    $excerpt   = trim($_POST['excerpt'] ?? '');
    $content   = $_POST['content'] ?? '';
    $image     = trim($_POST['image'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;
    $authorId  = $_SESSION['user_id'];

    // Validate
    if (!$title) $errors['title'] = 'Vul een titel in.';
    if (!$content) $errors['content'] = 'Vul de inhoud in.';

    // Ensure slug is unique (excluding current article)
    if ($slug) {
        $slugCheck = $pdo->prepare('SELECT id FROM news WHERE slug = ? AND id != ?');
        $slugCheck->execute([$slug, $id]);
        if ($slugCheck->fetch()) {
            $slug .= '-' . time();
        }
    }

    // Basic content sanitization - allow basic HTML tags
    $allowedTags = '<p><br><strong><em><h2><h3><ul><ol><li><a><blockquote>';
    $content = strip_tags($content, $allowedTags);

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE news SET title=?, slug=?, excerpt=?, content=?, image=?, published=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$title, $slug, $excerpt, $content, $image, $published, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO news (title, slug, excerpt, content, image, author_id, published) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$title, $slug, $excerpt, $content, $image, $authorId, $published]);
            $id = (int)$pdo->lastInsertId();
        }
        $success = true;
        // Reload article
        $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
        $stmt->execute([$id]);
        $article = $stmt->fetch();
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success">
  Artikel opgeslagen!
  <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($article['slug']) ?>" target="_blank" style="color:inherit;text-decoration:underline">Bekijk artikel →</a>
</div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1><?= $id ? 'Artikel bewerken' : 'Nieuw artikel' ?></h1>
    <p><?= $id ? 'Pas het artikel aan.' : 'Maak een nieuw nieuwsartikel aan.' ?></p>
  </div>
  <a href="<?= BASE_URL ?>/admin/news.php" class="btn btn-ghost">← Terug</a>
</div>

<form method="POST" action="" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start">

    <!-- Main content -->
    <div>
      <div class="admin-form-card" style="margin-bottom:1.25rem">
        <?php if ($errors): ?>
        <div class="alert alert-error">Controleer de ingevulde gegevens.</div>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="news-title">Titel <span class="req">*</span></label>
          <input type="text" id="news-title" name="title" class="form-control"
            value="<?= htmlspecialchars($article['title'] ?? $_POST['title'] ?? '') ?>"
            placeholder="Artikel titel..." required>
          <?php if (!empty($errors['title'])): ?>
          <div class="form-hint" style="color:var(--danger)"><?= htmlspecialchars($errors['title']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="news-slug">Slug (URL)</label>
          <input type="text" id="news-slug" name="slug" class="form-control"
            value="<?= htmlspecialchars($article['slug'] ?? $_POST['slug'] ?? '') ?>"
            placeholder="artikel-url-slug">
          <div class="form-hint">Wordt automatisch gegenereerd op basis van de titel.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="news-excerpt">Samenvatting / Excerpt</label>
          <textarea id="news-excerpt" name="excerpt" class="form-control" rows="3"
            placeholder="Korte beschrijving voor overzichtspagina..."><?= htmlspecialchars($article['excerpt'] ?? $_POST['excerpt'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="news-content">Inhoud <span class="req">*</span></label>
          <textarea id="news-content" name="content" class="form-control" rows="18"
            placeholder="Schrijf hier de inhoud van het artikel... (HTML is toegestaan)"><?= htmlspecialchars($article['content'] ?? $_POST['content'] ?? '') ?></textarea>
          <div class="form-hint">Basale HTML tags zijn toegestaan: &lt;p&gt; &lt;strong&gt; &lt;em&gt; &lt;h2&gt; &lt;h3&gt; &lt;ul&gt; &lt;li&gt; &lt;a&gt;</div>
          <?php if (!empty($errors['content'])): ?>
          <div class="form-hint" style="color:var(--danger)"><?= htmlspecialchars($errors['content']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">
      <div class="admin-form-card">
        <h3 style="font-size:.9rem;font-weight:600;margin-bottom:1.25rem;color:var(--white)">Publiceren</h3>

        <label class="toggle-switch" style="margin-bottom:1.25rem">
          <input type="checkbox" name="published" <?= !empty($article['published']) || !empty($_POST['published']) ? 'checked' : '' ?>>
          <span class="toggle-track"></span>
          <span class="toggle-label">Gepubliceerd</span>
        </label>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          Opslaan
        </button>

        <?php if ($id): ?>
        <div style="margin-top:.75rem;text-align:center">
          <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($article['slug'] ?? '') ?>" target="_blank" style="font-size:.82rem;color:var(--text-dim)">Bekijk op website →</a>
        </div>
        <?php endif; ?>
      </div>

      <div class="admin-form-card">
        <h3 style="font-size:.9rem;font-weight:600;margin-bottom:1.25rem;color:var(--white)">Afbeelding</h3>
        <div class="form-group">
          <label class="form-label" for="news-image">Afbeelding URL</label>
          <input type="url" id="news-image" name="image" class="form-control"
            value="<?= htmlspecialchars($article['image'] ?? $_POST['image'] ?? '') ?>"
            placeholder="https://...">
          <div class="form-hint">Externe URL of pad naar afbeelding.</div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
