<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in - redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error   = '';
$csrf    = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Beveiligingstoken ongeldig. Herlaad de pagina.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = 'Vul je gebruikersnaam en wachtwoord in.';
        } else {
            $result = login($username, $password);
            if ($result['success']) {
                $redirect = $_GET['redirect'] ?? BASE_URL . '/admin/dashboard.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Inloggen | SoulFM Beheer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>

<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
      </div>
      <h1>Soul<span>FM</span></h1>
      <p>Beheerpaneel — Log in om door te gaan</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" role="alert">
      <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;flex-shrink:0"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div class="form-group">
        <label class="form-label" for="username">Gebruikersnaam of e-mail</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control"
          placeholder="gebruikersnaam"
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          required
          autocomplete="username"
          autofocus>
      </div>

      <div class="form-group" style="margin-top:1.25rem">
        <label class="form-label" for="password">Wachtwoord</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control"
          placeholder="••••••••"
          required
          autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1.75rem;padding:.85rem;font-size:.95rem;justify-content:center">
        Inloggen
        <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor"><path d="M8 5v14l11-7z"/></svg>
      </button>
    </form>

    <div style="text-align:center;margin-top:1.5rem">
      <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-dim);font-size:.83rem">← Terug naar de website</a>
    </div>
  </div>
</div>

</body>
</html>
