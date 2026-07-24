<?php
/**
 * SoulFM - First-time Setup Helper
 * Run this once to set up the database, then DELETE this file!
 * 
 * Access: http://your-domain/setup.php
 */

// Prevent running in production if already set up
$setupDone = file_exists(__DIR__ . '/.setup_complete');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$messages = [];
$errors   = [];
$success  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupDone) {
    try {
        $pdo = getPDO();

        // Read and execute schema
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');

        // Generate bcrypt hashes for default passwords
        $adminPass = trim($_POST['admin_password'] ?? 'admin123');
        if (strlen($adminPass) < 6) $adminPass = 'admin123';
        $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
        $djHash    = password_hash('dj123456', PASSWORD_DEFAULT);
        $modHash   = password_hash('mod12345', PASSWORD_DEFAULT);

        // Split into individual statements (handle multi-line)
        $statements = [];
        $current = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            // Skip comment lines
            if (str_starts_with($trimmed, '--')) continue;
            $current .= $line . "\n";
            if (str_ends_with(rtrim($line), ';')) {
                $stmt = trim($current);
                if ($stmt) $statements[] = $stmt;
                $current = '';
            }
        }

        foreach ($statements as $statement) {
            if (trim($statement)) {
                try { $pdo->exec($statement); } catch(PDOException $e) {
                    // Skip duplicate entry errors during re-run
                    if ($e->errorInfo[1] != 1062) throw $e;
                }
            }
        }

        // Update user passwords with real bcrypt hashes
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'")->execute([$adminHash]);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'dj_marcus'")->execute([$djHash]);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'dj_sarah'")->execute([$djHash]);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'moderator1'")->execute([$modHash]);

        // Genereer en sla mail-encryptiesleutel op (buiten webroot indien mogelijk)
        $keyFile = __DIR__ . '/.mail_key';
        if (!file_exists($keyFile)) {
            $mailKey = bin2hex(random_bytes(32));
            file_put_contents($keyFile, $mailKey);
            chmod($keyFile, 0600);
        }

        // Create setup complete marker
        file_put_contents(__DIR__ . '/.setup_complete', date('Y-m-d H:i:s'));
        $success = true;
        $messages[] = '✅ Database aangemaakt!';
        $messages[] = '✅ Tabellen aangemaakt!';
        $messages[] = '✅ Standaard instellingen ingevuld!';
        $messages[] = '✅ Admin account aangemaakt (gebruiker: admin, wachtwoord: ' . htmlspecialchars($adminPass) . ')';
        $messages[] = '✅ DJ accounts: dj_marcus / dj_sarah (wachtwoord: dj123456)';
        $messages[] = '✅ Moderator account: moderator1 (wachtwoord: mod12345)';
        $messages[] = '⚠️ Verwijder dit setup.php bestand na installatie!';

    } catch (PDOException $e) {
        $errors[] = 'Database fout: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoulFM Setup</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #0a1628; color: #e8f0fe; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
    .card { background: #0f2040; border: 1px solid rgba(0,180,216,.15); border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 520px; }
    h1 { font-size: 1.8rem; margin-bottom: .5rem; color: #fff; }
    h1 span { color: #00b4d8; }
    p { color: #829ab1; margin-bottom: 1.5rem; font-size: .9rem; }
    .form-group { margin-bottom: 1.25rem; }
    label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .4rem; color: #829ab1; text-transform: uppercase; letter-spacing: .5px; }
    input { width: 100%; padding: .75rem 1rem; background: rgba(10,22,40,.6); border: 1px solid rgba(0,180,216,.15); border-radius: 8px; color: #fff; font-size: .9rem; outline: none; }
    input:focus { border-color: #00b4d8; }
    button { width: 100%; padding: .8rem; background: #00b4d8; color: #0a1628; border: none; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: .5rem; }
    button:hover { background: #48cae4; }
    .msg { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .88rem; }
    .msg-ok  { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.25); color: #34d399; }
    .msg-err { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.25);  color: #f87171; }
    .msg ul { padding-left: 1.2rem; }
    .done-links { display: flex; gap: 1rem; margin-top: 1rem; }
    .done-links a { display: inline-block; padding: .6rem 1.2rem; background: rgba(0,180,216,.1); border: 1px solid rgba(0,180,216,.25); color: #00b4d8; border-radius: 8px; font-weight: 600; font-size: .88rem; }
    .done-links a:hover { background: rgba(0,180,216,.2); }
    .warning { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.25); color: #fbbf24; padding: .75rem 1rem; border-radius: 8px; font-size: .85rem; margin-bottom: 1.5rem; }
  </style>
</head>
<body>
<div class="card">
  <h1>Soul<span>FM</span> Setup</h1>
  <p>Voer de eerste installatie uit. Dit script maakt de database aan en vult deze met standaardgegevens.</p>

  <?php if ($setupDone && !$success): ?>
  <div class="warning">⚠️ Setup is al uitgevoerd. Verwijder setup.php uit je webroot!</div>
  <?php endif; ?>

  <?php if ($errors): ?>
  <div class="msg msg-err"><ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="msg msg-ok"><ul><?php foreach($messages as $m): ?><li><?= $m ?></li><?php endforeach; ?></ul></div>
  <div class="done-links">
    <a href="<?= BASE_URL ?>/">Website bekijken</a>
    <a href="<?= BASE_URL ?>/admin/login.php">Admin inloggen</a>
  </div>
  <?php else: ?>
  <form method="POST">
    <div class="warning">⚠️ Zorg dat de database '<strong><?= DB_NAME ?></strong>' bestaat op '<strong><?= DB_HOST ?></strong>' met gebruiker '<strong><?= DB_USER ?></strong>'.</div>

    <div class="form-group">
      <label>Admin wachtwoord (min. 6 tekens)</label>
      <input type="password" name="admin_password" placeholder="Kies een sterk wachtwoord" minlength="6">
    </div>

    <button type="submit" <?= $setupDone ? 'disabled title="Setup al uitgevoerd"' : '' ?>>
      Database instellen &amp; starten
    </button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
