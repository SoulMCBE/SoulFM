<?php
/**
 * SoulFM - Admin Panel Header
 * Variables expected: $pageTitle, $activePage
 */
$currentUser = getCurrentUser();
$pendingCount = getPendingRequestsCount();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> | SoulFM Beheer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>

<div class="admin-layout">

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="admin-sidebar" role="navigation" aria-label="Admin navigatie">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
      </div>
      <div>
        <div class="sidebar-logo-text">Soul<span>FM</span></div>
        <span class="sidebar-logo-sub">Beheerpaneel</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-label">Overzicht</span>

      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard
      </a>

      <a href="<?= BASE_URL ?>/index.php" target="_blank" class="sidebar-link">
        <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        Website bekijken
      </a>

      <span class="nav-section-label">Inhoud</span>

      <?php if (hasPermission('view_requests')): ?>
      <a href="<?= BASE_URL ?>/admin/requests.php" class="sidebar-link <?= ($activePage ?? '') === 'requests' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
        Verzoekjes
        <?php if ($pendingCount > 0): ?>
        <span class="sidebar-badge danger"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if (hasPermission('manage_news')): ?>
      <a href="<?= BASE_URL ?>/admin/news.php" class="sidebar-link <?= ($activePage ?? '') === 'news' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        Nieuws
      </a>
      <?php endif; ?>

      <?php if (hasPermission('manage_schedule')): ?>
      <a href="<?= BASE_URL ?>/admin/schedule.php" class="sidebar-link <?= ($activePage ?? '') === 'schedule' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
        Planning
      </a>
      <?php endif; ?>

      <?php if (hasPermission('manage_content')): ?>
      <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-link <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
        Instellingen
      </a>
      <?php endif; ?>

      <?php
        $newApps = getNewApplicationsCount(getVisibleApplicationDepartments());
      ?>
      <?php if (hasPermission('view_applications')): ?>
      <a href="<?= BASE_URL ?>/admin/applications.php" class="sidebar-link <?= ($activePage ?? '') === 'applications' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15h8v2H8zm0-4h8v2H8zm0-4h5v2H8z"/></svg>
        Sollicitaties
        <?php if ($newApps > 0): ?>
        <span class="sidebar-badge danger"><?= $newApps ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if (hasPermission('view_dept_emails')): ?>
      <a href="<?= BASE_URL ?>/admin/dept-emails.php" class="sidebar-link <?= ($activePage ?? '') === 'dept-emails' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        Afdeling E-mails
      </a>
      <?php endif; ?>

      <?php if (hasPermission('view_own_mail')): ?>
      <a href="<?= BASE_URL ?>/admin/mijn-email.php" class="sidebar-link <?= ($activePage ?? '') === 'mijn-email' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        Mijn Bedrijfsmail
      </a>
      <?php endif; ?>

      <?php if (hasPermission('view_stream_info') || ($_SESSION['user_role'] ?? '') === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/live-credentials.php" class="sidebar-link <?= ($activePage ?? '') === 'live-credentials' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M3 10v4h4l5 5V5L7 10H3zm13.5 2c0-1.77-1-3.29-2.5-4.03v8.05c1.5-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
        <?= ($_SESSION['user_role'] ?? '') === 'admin' ? 'DJ Live-credentials' : 'Mijn Live Radio Inlog' ?>
      </a>
      <?php endif; ?>

      <?php if (hasPermission('manage_users')): ?>
      <span class="nav-section-label">Beheer</span>
      <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Gebruikers
        <span class="sidebar-badge"><?= getUserCount() ?></span>
      </a>
      <a href="<?= BASE_URL ?>/admin/mail-beheer.php" class="sidebar-link <?= ($activePage ?? '') === 'mail-beheer' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        Mailcredentials
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)) ?></div>
        <div>
          <div class="sidebar-username"><?= htmlspecialchars($currentUser['username'] ?? '') ?></div>
          <div class="sidebar-role"><?= htmlspecialchars($currentUser['role'] ?? '') ?></div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/admin/wachtwoord.php" class="sidebar-logout" style="margin-bottom:.5rem;background:rgba(0,180,216,.08);color:var(--accent)">
        <svg viewBox="0 0 24 24"><path d="M12 1a5 5 0 00-5 5v3H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V11a2 2 0 00-2-2h-1V6a5 5 0 00-5-5zm-3 8V6a3 3 0 116 0v3H9z"/></svg>
        Wachtwoord wijzigen
      </a>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="sidebar-logout">
        <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Uitloggen
      </a>
    </div>
  </aside>

  <!-- Main content area -->
  <div class="admin-content">
    <header class="admin-topbar">
      <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Menu openen">
        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
      </button>
      <div>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
        <div class="topbar-subtitle"><?= date('l j F Y') ?></div>
      </div>
      <div class="topbar-actions">
        <?php if ($pendingCount > 0): ?>
        <a href="<?= BASE_URL ?>/admin/requests.php" class="topbar-btn" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.2);color:#f87171">
          <svg viewBox="0 0 24 24"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
          <?= $pendingCount ?> nieuw
        </a>
        <?php endif; ?>
      </div>
    </header>

    <main class="admin-main">
