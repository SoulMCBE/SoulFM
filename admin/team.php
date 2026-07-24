<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasPermission('manage_content')) { http_response_code(403); die('Toegang geweigerd'); }

$pageTitle = 'Teampagina beheer';
$activePage = 'team';
$csrf = generateCsrfToken();
$msg = '';
$msgType = 'success';
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $roleTitle = trim($_POST['role_title'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $photoUrl = trim($_POST['photo_url'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!$name || !$roleTitle) {
            $msg = 'Naam en functietitel zijn verplicht.';
            $msgType = 'error';
        } else {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE team_members SET name = ?, role_title = ?, bio = ?, photo_url = ?, display_order = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$name, $roleTitle, $bio ?: null, $photoUrl ?: null, $displayOrder, $isActive, $id]);
                $msg = 'Teamlid bijgewerkt.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO team_members (name, role_title, bio, photo_url, display_order, is_active) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$name, $roleTitle, $bio ?: null, $photoUrl ?: null, $displayOrder, $isActive]);
                $msg = 'Teamlid toegevoegd.';
            }
        }
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM team_members WHERE id = ?')->execute([$id]);
        $msg = 'Teamlid verwijderd.';
    } elseif ($action === 'toggle_active' && $id) {
        $pdo->prepare('UPDATE team_members SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        $msg = 'Zichtbaarheid bijgewerkt.';
    }
}

$teamMembers = getAllTeamMembers();

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="page-header-admin">
  <div>
    <h1>Teampagina beheer</h1>
    <p>Beheer de teamleden die op de openbare teampagina zichtbaar zijn.</p>
  </div>
  <div style="display:flex;gap:.75rem">
    <a href="<?= BASE_URL ?>/team.php" target="_blank" class="btn btn-secondary">
      <svg viewBox="0 0 24 24"><path d="M14 3v2h3.59L7 15.59 8.41 17 19 6.41V10h2V3z"/><path d="M5 5h6V3H3v8h2z"/></svg>
      Openbare pagina
    </a>
    <button class="btn btn-primary" data-modal-open="team-member-modal">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      Teamlid toevoegen
    </button>
  </div>
</div>

<div class="table-container">
  <div class="table-header">
    <span class="table-title">Teamleden (<?= count($teamMembers) ?>)</span>
    <div class="search-input">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" placeholder="Zoeken..." data-search-table="team-table">
    </div>
  </div>
  <div class="table-wrapper">
    <table id="team-table" aria-label="Teamleden">
      <thead>
        <tr>
          <th>Naam</th>
          <th>Functie</th>
          <th>Volgorde</th>
          <th>Status</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($teamMembers): foreach ($teamMembers as $member): ?>
        <tr>
          <td data-label="Naam">
            <div class="td-primary"><?= htmlspecialchars($member['name']) ?></div>
            <?php if (!empty($member['photo_url'])): ?>
            <div class="td-muted"><?= htmlspecialchars($member['photo_url']) ?></div>
            <?php endif; ?>
          </td>
          <td data-label="Functie" class="td-muted"><?= htmlspecialchars($member['role_title']) ?></td>
          <td data-label="Volgorde" class="td-muted"><?= (int)$member['display_order'] ?></td>
          <td data-label="Status">
            <span class="badge badge-<?= $member['is_active'] ? 'active' : 'inactive' ?>">
              <?= $member['is_active'] ? 'Zichtbaar' : 'Verborgen' ?>
            </span>
          </td>
          <td data-label="Acties">
            <div class="action-btns">
              <button
                type="button"
                class="btn-icon edit"
                title="Bewerken"
                aria-label="Bewerk teamlid"
                data-edit-team='<?= htmlspecialchars(json_encode($member, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              </button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
                <button type="submit" class="btn-icon check" title="Zichtbaarheid wisselen" aria-label="Wissel zichtbaarheid">
                  <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
                <button type="submit" class="btn-icon del" title="Verwijderen"
                  data-confirm-delete="Weet je zeker dat je <?= htmlspecialchars(addslashes($member['name'])) ?> wilt verwijderen?"
                  aria-label="Verwijder teamlid">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text-dim);padding:3rem">Nog geen teamleden toegevoegd.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="team-member-modal" role="dialog" aria-modal="true" aria-labelledby="team-member-title">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="team-member-title">Teamlid toevoegen</span>
      <button class="modal-close" data-modal-close="team-member-modal" aria-label="Sluiten">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <form method="POST" action="">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="team-id">

        <div class="form-group">
          <label class="form-label">Naam <span class="req">*</span></label>
          <input type="text" name="name" id="team-name" class="form-control" required maxlength="120">
        </div>
        <div class="form-group">
          <label class="form-label">Functie / Rol <span class="req">*</span></label>
          <input type="text" name="role_title" id="team-role-title" class="form-control" required maxlength="120">
        </div>
        <div class="form-group">
          <label class="form-label">Foto URL</label>
          <input type="url" name="photo_url" id="team-photo-url" class="form-control" maxlength="255" placeholder="https://...">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Volgorde</label>
            <input type="number" name="display_order" id="team-display-order" class="form-control" value="0">
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end">
            <label class="toggle-switch">
              <input type="checkbox" name="is_active" id="team-is-active" checked>
              <span class="toggle-track"></span>
              <span class="toggle-label">Zichtbaar op website</span>
            </label>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Bio</label>
          <textarea name="bio" id="team-bio" class="form-control" rows="4"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="team-member-modal">Annuleren</button>
        <button type="submit" class="btn btn-primary">Opslaan</button>
      </div>
    </form>
  </div>
</div>

<script>
const teamModalTitle = document.getElementById('team-member-title');
const teamId = document.getElementById('team-id');
const teamName = document.getElementById('team-name');
const teamRole = document.getElementById('team-role-title');
const teamPhoto = document.getElementById('team-photo-url');
const teamOrder = document.getElementById('team-display-order');
const teamActive = document.getElementById('team-is-active');
const teamBio = document.getElementById('team-bio');

document.querySelector('[data-modal-open="team-member-modal"]')?.addEventListener('click', () => {
  teamModalTitle.textContent = 'Teamlid toevoegen';
  teamId.value = '';
  teamName.value = '';
  teamRole.value = '';
  teamPhoto.value = '';
  teamOrder.value = '0';
  teamActive.checked = true;
  teamBio.value = '';
});

document.querySelectorAll('[data-edit-team]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const data = JSON.parse(btn.dataset.editTeam);
    teamModalTitle.textContent = 'Teamlid bewerken';
    teamId.value = data.id || '';
    teamName.value = data.name || '';
    teamRole.value = data.role_title || '';
    teamPhoto.value = data.photo_url || '';
    teamOrder.value = String(data.display_order ?? 0);
    teamActive.checked = Number(data.is_active) === 1;
    teamBio.value = data.bio || '';
    openModal('team-member-modal');
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
