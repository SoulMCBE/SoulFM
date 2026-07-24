<?php
/**
 * SoulFM - Authentication & Authorization
 * Uitgebreid rollensysteem met afdelingen en hoofd-rollen
 */

require_once __DIR__ . '/db.php';

// =====================================================
// ROLLEN DEFINITIE
// =====================================================

/**
 * Alle bekende rollen met hun afdeling-slug en of het een hoofd-rol is
 */
function getAllRoles(): array {
    return [
        'admin'                => ['label' => 'Admin',               'department' => null,            'is_head' => true,  'level' => 100],
        'dj_hoofd'             => ['label' => 'DJ Hoofd',            'department' => 'dj',            'is_head' => true,  'level' => 50],
        'dj'                   => ['label' => 'DJ',                  'department' => 'dj',            'is_head' => false, 'level' => 20],
        'administratie_hoofd'  => ['label' => 'Administratie Hoofd', 'department' => 'administratie', 'is_head' => true,  'level' => 50],
        'administratie'        => ['label' => 'Administratie',       'department' => 'administratie', 'is_head' => false, 'level' => 20],
        'evenementen_hoofd'    => ['label' => 'Evenementen Hoofd',   'department' => 'evenementen',   'is_head' => true,  'level' => 50],
        'evenementen'          => ['label' => 'Evenementen',         'department' => 'evenementen',   'is_head' => false, 'level' => 20],
        'redactie_hoofd'       => ['label' => 'Redactie Hoofd',      'department' => 'redactie',      'is_head' => true,  'level' => 50],
        'redactie'             => ['label' => 'Redactie',            'department' => 'redactie',      'is_head' => false, 'level' => 20],
        'content_hoofd'        => ['label' => 'Content Hoofd',       'department' => 'content',       'is_head' => true,  'level' => 50],
        'content'              => ['label' => 'Content',             'department' => 'content',       'is_head' => false, 'level' => 20],
        'marketing_hoofd'      => ['label' => 'Marketing Hoofd',     'department' => 'marketing',     'is_head' => true,  'level' => 50],
        'marketing'            => ['label' => 'Marketing',           'department' => 'marketing',     'is_head' => false, 'level' => 20],
        'moderator'            => ['label' => 'Moderator',           'department' => null,            'is_head' => false, 'level' => 30],
        'listener'             => ['label' => 'Luisteraar',          'department' => null,            'is_head' => false, 'level' => 1],
    ];
}

/**
 * Geef het label van een rol
 */
function getRoleLabel(string $role): string {
    return getAllRoles()[$role]['label'] ?? ucfirst($role);
}

/**
 * Geef de afdeling-slug van de huidige gebruiker (of null)
 */
function getUserDepartment(?string $role = null): ?string {
    $role = $role ?? ($_SESSION['user_role'] ?? 'listener');
    return getAllRoles()[$role]['department'] ?? null;
}

/**
 * Is de rol een hoofd-rol?
 */
function isHeadRole(?string $role = null): bool {
    $role = $role ?? ($_SESSION['user_role'] ?? 'listener');
    return getAllRoles()[$role]['is_head'] ?? false;
}

// =====================================================
// BASIS AUTH
// =====================================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (!hasRole($role)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:2rem;text-align:center;background:#0a1628;color:#e8f0fe;min-height:100vh">
            <h2 style="color:#f87171">Toegang geweigerd</h2>
            <p>Je hebt geen rechten om deze pagina te bekijken.</p>
            <a href="' . BASE_URL . '/admin/dashboard.php" style="color:#00b4d8">Terug naar dashboard</a>
        </div>');
    }
}

/**
 * Controleer of de gebruiker het vereiste niveau heeft
 */
function hasRole(string $role): bool {
    if (!isLoggedIn()) return false;
    $roles   = getAllRoles();
    $myLevel = $roles[$_SESSION['user_role'] ?? 'listener']['level'] ?? 0;
    $reqLevel = $roles[$role]['level'] ?? 999;
    return $myLevel >= $reqLevel;
}

// =====================================================
// PERMISSIES
// =====================================================

/**
 * Controleer een specifieke permissie op basis van rol
 */
function hasPermission(string $permission): bool {
    if (!isLoggedIn()) return false;

    $role = $_SESSION['user_role'] ?? 'listener';

    // Admin mag alles
    if ($role === 'admin') return true;

    $map = [
        // Dashboard toegang
        'view_dashboard'      => ['moderator','dj','dj_hoofd','administratie','administratie_hoofd',
                                   'evenementen','evenementen_hoofd','redactie','redactie_hoofd',
                                   'content','content_hoofd','marketing','marketing_hoofd'],

        // Gebruikersbeheer — alleen admin (al afgevangen hierboven)
        'manage_users'        => [],

        // Instellingen
        'manage_content'      => ['moderator'],

        // Planning
        'manage_schedule'     => ['dj','dj_hoofd','administratie','administratie_hoofd'],

        // Nieuws — redactie, content, evenementen (en hun hoofden)
        'manage_news'         => ['moderator','redactie','redactie_hoofd','content','content_hoofd',
                                   'evenementen','evenementen_hoofd'],

        // Verzoekjes bekijken
        'view_requests'       => ['moderator','dj','dj_hoofd'],

        // Verzoekjes beheren (spelen/afwijzen)
        'manage_requests'     => ['moderator','dj_hoofd'],

        // Stream/DJ-gegevens
        'view_stream_info'    => ['dj','dj_hoofd'],

        // Afdeling e-mails bekijken — eigen afdeling
        'view_dept_emails'    => ['dj','dj_hoofd','administratie','administratie_hoofd',
                                   'evenementen','evenementen_hoofd','redactie','redactie_hoofd',
                                   'content','content_hoofd','marketing','marketing_hoofd'],

        // Sollicitaties: admin ziet alles, hoofden zien eigen afdeling
        'view_applications'   => ['moderator','dj_hoofd','administratie_hoofd','evenementen_hoofd',
                                   'redactie_hoofd','content_hoofd','marketing_hoofd'],

        // Sollicitaties beheren (status wijzigen, notities)
        'manage_applications' => ['moderator','dj_hoofd','administratie_hoofd','evenementen_hoofd',
                                   'redactie_hoofd','content_hoofd','marketing_hoofd'],

        // Eigen mailcredentials bekijken — elke medewerker die niet listener is
        'view_own_mail'       => ['dj','dj_hoofd','administratie','administratie_hoofd',
                                   'evenementen','evenementen_hoofd','redactie','redactie_hoofd',
                                   'content','content_hoofd','marketing','marketing_hoofd',
                                   'moderator'],

        // Mailcredentials van anderen beheren (opslaan/verwijderen) — alleen admin
        'manage_mail_creds'   => [],  // admin vangt dit al op via de $role === 'admin' check hierboven
    ];

    return in_array($role, $map[$permission] ?? []);
}

/**
 * Welke afdelingen mag deze gebruiker sollicitaties van zien?
 * Admin → alle, hoofd → eigen afdeling
 */
function getVisibleApplicationDepartments(): array {
    if (!isLoggedIn()) return [];
    $role = $_SESSION['user_role'] ?? 'listener';
    if ($role === 'admin' || $role === 'moderator') return []; // leeg = alle
    $dept = getUserDepartment($role);
    return $dept ? [$dept] : [];
}

// =====================================================
// LOGIN / LOGOUT
// =====================================================

function login(string $username, string $password): array {
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, active FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user)          return ['success' => false, 'message' => 'Ongeldige gebruikersnaam of wachtwoord.'];
        if (!$user['active']) return ['success' => false, 'message' => 'Dit account is gedeactiveerd.'];
        if (!password_verify($password, $user['password_hash']))
                              return ['success' => false, 'message' => 'Ongeldige gebruikersnaam of wachtwoord.'];

        $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        return ['success' => true, 'user' => $user];
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Er is een fout opgetreden. Probeer het opnieuw.'];
    }
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, username, email, role, avatar, created_at FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
