<?php
/**
 * SoulFM - Public Site Header
 * Variables expected: $pageTitle (string), $activePage (string)
 */
require_once __DIR__ . '/functions.php';
$settings    = getSettings();
$siteName    = $settings['site_name'] ?? 'SoulFM';
$tagline     = $settings['tagline'] ?? 'Your Soul Music Station';
$streamUrl   = $settings['stream_url'] ?? '';
$currentProg = getCurrentProgram();
$csrf        = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? '') ?>">
  <meta name="base-url" content="<?= BASE_URL ?>">
  <title><?= htmlspecialchars($pageTitle ?? $siteName) ?> | <?= htmlspecialchars($siteName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <!-- Stream URL for player JS -->
  <span id="stream-url" data-url="<?= htmlspecialchars($streamUrl) ?>" style="display:none"></span>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="site-header" role="banner">
  <div class="container">
    <div class="header-inner">
      <a href="<?= BASE_URL ?>/index.php" class="logo" aria-label="<?= htmlspecialchars($siteName) ?> home">
        <div class="logo-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 3C7.03 3 3 7.03 3 12s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm-1-11v8l6-4-6-4z"/>
          </svg>
        </div>
        <div>
          <div class="logo-text"><?= htmlspecialchars(substr($siteName, 0, -2)) ?><span><?= htmlspecialchars(substr($siteName, -2)) ?></span></div>
          <div class="logo-tagline"><?= htmlspecialchars($tagline) ?></div>
        </div>
      </a>

      <nav class="main-nav" id="main-nav" role="navigation" aria-label="Hoofdnavigatie">
        <a href="<?= BASE_URL ?>/index.php"    class="<?= ($activePage ?? '') === 'home'     ? 'active' : '' ?>">Home</a>
        <a href="<?= BASE_URL ?>/schedule.php" class="<?= ($activePage ?? '') === 'schedule' ? 'active' : '' ?>">Programma</a>
        <a href="<?= BASE_URL ?>/request.php"  class="<?= ($activePage ?? '') === 'request'  ? 'active' : '' ?>">Verzoekje</a>
        <a href="<?= BASE_URL ?>/news.php"     class="<?= ($activePage ?? '') === 'news'     ? 'active' : '' ?>">Nieuws</a>
        <a href="<?= BASE_URL ?>/contact.php"  class="<?= ($activePage ?? '') === 'contact'  ? 'active' : '' ?>">Contact</a>
        <a href="<?= BASE_URL ?>/solliciteer.php" class="<?= ($activePage ?? '') === 'solliciteer' ? 'active' : '' ?>">Werken bij</a>
        <a href="<?= BASE_URL ?>/request.php"  class="nav-cta">Verzoekje doen</a>
      </nav>

      <button class="hamburger" id="hamburger" aria-label="Menu openen" aria-expanded="false" aria-controls="main-nav">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
