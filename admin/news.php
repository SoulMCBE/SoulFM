<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('manage_news')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle  = 'Nieuwsbeheer';
$activePage = 'news';
$csrf       = generateCsrfToken();
$msg        = '';
$msgType    = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $pdo    = getPDO();

    if ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);
        $msg = 'Artikel verwijderd.';
    } elseif ($action === 'toggle_publish' && $id) {
        $pdo->prepare('UPDATE news SET published = NOT published WHERE id = ?')->execute([$id]);
        $msg = 'Status bijgewerkt.';
    }
}

$pdo = getPDO();
$stmt = $pdo->query('SELECT n.*, u.username as author_name FROM news n LEFT JOIN users u ON n.author_id = u.id ORDER BY n.created_at DESC');
$articles = $stmt->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Nieuwsbeheer</h1>
    <p>Beheer nieuwsberichten en aankondigingen.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/news-edit.php" class="btn btn-primary">
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    Nieuw artikel
  </a>
</div>

<div class="table-container">
  <div class="table-header">
    <span class="table-title">Alle artikelen (<?= count($articles) ?>)</span>
    <div class="search-input">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" placeholder="Zoeken..." data-search-table="news-table">
    </div>
  </div>
  <div class="table-wrapper">
    <table id="news-table" aria-label="Nieuwsartikelen">
      <thead>
        <tr>
          <th>Titel</th>
          <th>Auteur</th>
          <th>Datum</th>
          <th>Status</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($articles): foreach ($articles as $a): ?>
        <tr>
          <td data-label="Titel">
            <div class="td-primary"><?= htmlspecialchars($a['title']) ?></div>
            <div class="td-muted">/<?= htmlspecialchars($a['slug']) ?></div>
          </td>
          <td data-label="Auteur" class="td-muted"><?= htmlspecialchars($a['author_name'] ?? '—') ?></td>
          <td data-label="Datum" class="td-muted"><?= formatDate($a['created_at']) ?></td>
          <td data-label="Status">
            <span class="badge badge-<?= $a['published'] ? 'published' : 'draft' ?>">
              <?= $a['published'] ? 'Gepubliceerd' : 'Concept' ?>
            </span>
          </td>
          <td data-label="Acties">
            <div class="action-btns">
              <a href="<?= BASE_URL ?>/news-detail.php?slug=<?= urlencode($a['slug']) ?>" target="_blank" class="btn-icon view" title="Bekijken" aria-label="Bekijk artikel">
                <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
              </a>
              <a href="<?= BASE_URL ?>/admin/news-edit.php?id=<?= $a['id'] ?>" class="btn-icon edit" title="Bewerken" aria-label="Bewerk artikel">
                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              </a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="toggle_publish">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn-icon check" title="<?= $a['published'] ? 'Depubliceren' : 'Publiceren' ?>" aria-label="<?= $a['published'] ? 'Depubliceer' : 'Publiceer' ?> artikel">
                  <svg viewBox="0 0 24 24"><path d="<?= $a['published'] ? 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z' : 'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z' ?>"/></svg>
                </button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn-icon del" title="Verwijderen"
                  data-confirm-delete="Weet je zeker dat je '<?= htmlspecialchars(addslashes($a['title'])) ?>' wilt verwijderen?"
                  aria-label="Verwijder artikel">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text-dim);padding:3rem">Geen artikelen gevonden. <a href="<?= BASE_URL ?>/admin/news-edit.php">Maak er een aan!</a></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
