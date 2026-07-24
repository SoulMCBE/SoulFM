<?php
/**
 * SoulFM - Helper Functions
 */

require_once __DIR__ . '/db.php';

/**
 * Get all settings as key => value array
 */
function getSettings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    
    try {
        $pdo = getPDO();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $cache = array_column($rows, 'setting_value', 'setting_key');
        return $cache;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get a single setting value
 */
function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

/**
 * Get schedule for a specific day
 */
function getScheduleForDay(string $day): array {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM schedule WHERE day_of_week = ? ORDER BY start_time ASC');
        $stmt->execute([strtolower($day)]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get the current program based on day and time
 */
function getCurrentProgram(): ?array {
    $days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    $today = $days[date('w')];
    $now = date('H:i:s');
    
    try {
        $pdo = getPDO();
        
        // Handle overnight slots (end_time < start_time means it crosses midnight)
        $stmt = $pdo->prepare('
            SELECT * FROM schedule 
            WHERE day_of_week = ?
            AND (
                (start_time <= end_time AND ? BETWEEN start_time AND end_time)
                OR
                (start_time > end_time AND (? >= start_time OR ? <= end_time))
            )
            ORDER BY start_time ASC
            LIMIT 1
        ');
        $stmt->execute([$today, $now, $now, $now]);
        $program = $stmt->fetch();
        
        if (!$program) {
            // Return next upcoming program
            $stmt = $pdo->prepare('SELECT * FROM schedule WHERE day_of_week = ? AND start_time > ? ORDER BY start_time ASC LIMIT 1');
            $stmt->execute([$today, $now]);
            $program = $stmt->fetch();
        }
        
        return $program ?: null;
    } catch (PDOException $e) {
        return null;
    }
}
/**
 * Haal live de nu-afspelen data op uit AzuraCast (zonder file-cache)
 */
function getLiveAzuraCastData(): ?array {
    static $liveData = null; // Voorkomt dubbele API-calls tijdens één enkele paginalaadactie
    if ($liveData !== null) return$liveData;

    $azuraUrl = 'https://beheer.soulfm.nl/api/nowplaying/1';$context  = stream_context_create([
        'http' => [
            'timeout' => 2.5, // Korte timeout zodat de site nooit vastloopt als AzuraCast traag is
            'header'  => "User-Agent: SoulFM-Website/1.0\r\n"
        ]
    ]);

    $response = @file_get_contents($azuraUrl, false,$context);
    if ($response) {
        $liveData = json_decode($response, true);
        return $liveData;
    }
    return null;
}

/**
 * Get current song direct en live uit AzuraCast
 */
/**
 * Get current song direct en live uit AzuraCast
 */
function getCurrentSong(): string {
    $data = getLiveAzuraCastData();
    
    if ($data && !empty($data['now_playing']['song']['text'])) {
        $songText = $data['now_playing']['song']['text'];
        // Direct filteren voor lege of non-stop teksten met de juiste logical OR (||)
        if (stripos($songText, 'Non-Stop') !== false || $songText === 'SoulFM') {
            return 'SoulFM Non-Stop';
        }
        return $songText;
    }
    
    return 'SoulFM Non-Stop';
}
/**
 * Get all schedule slots for all days
 */
function getAllSchedule(): array {
    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $schedule = [];
    foreach ($days as $day) {
        $schedule[$day] = getScheduleForDay($day);
    }
    return $schedule;
}

/**
 * Get latest published news articles
 */
function getLatestNews(int $limit = 3): array {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT n.*, u.username as author_name 
            FROM news n 
            LEFT JOIN users u ON n.author_id = u.id 
            WHERE n.published = 1 
            ORDER BY n.created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get recent song requests
 */
function getRecentRequests(int $limit = 10): array {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM requests ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get recently played requests
 */
function getPlayedRequests(int $limit = 5): array {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM requests WHERE status = "played" ORDER BY played_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Format time for display (e.g., "14:00" or "2:00 PM")
 */
function formatTime(string $time): string {
    return date('H:i', strtotime($time));
}

/**
 * Format date for display
 */
function formatDate(string $date): string {
    $d = new DateTime($date);
    $months = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
    return $d->format('j') . ' ' . $months[$d->format('n') - 1] . ' ' . $d->format('Y');
}

/**
 * Format relative date ("2 uur geleden", "gisteren", etc.)
 */
function formatRelativeDate(string $date): string {
    $diff = time() - strtotime($date);
    
    if ($diff < 60) return 'zojuist';
    if ($diff < 3600) return floor($diff/60) . ' minuten geleden';
    if ($diff < 86400) return floor($diff/3600) . ' uur geleden';
    if ($diff < 172800) return 'gisteren';
    if ($diff < 604800) return floor($diff/86400) . ' dagen geleden';
    
    return formatDate($date);
}

/**
 * Sanitize user input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a URL-friendly slug from a title
 */
function generateSlug(string $title): string {
    $slug = strtolower($title);
    $slug = preg_replace('/[àáâãäå]/u', 'a', $slug);
    $slug = preg_replace('/[èéêë]/u', 'e', $slug);
    $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
    $slug = preg_replace('/[òóôõö]/u', 'o', $slug);
    $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Get count of pending requests
 */
function getPendingRequestsCount(): int {
    try {
        $pdo = getPDO();
        return (int) $pdo->query('SELECT COUNT(*) FROM requests WHERE status = "pending"')->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get mock listener count (for demo; replace with real stream API)
 */
function getListenerCount(): int {
    // In production, query your stream server's API
    return rand(47, 203);
}

/**
 * Get count of all news articles
 */
function getNewsCount(): int {
    try {
        $pdo = getPDO();
        return (int) $pdo->query('SELECT COUNT(*) FROM news WHERE published = 1')->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get count of total schedule slots
 */
function getScheduleCount(): int {
    try {
        $pdo = getPDO();
        return (int) $pdo->query('SELECT COUNT(*) FROM schedule')->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get count of total users
 */
function getUserCount(): int {
    try {
        $pdo = getPDO();
        return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Day name in Dutch
 */
function dutchDayName(string $day): string {
    $names = [
        'monday'    => 'Maandag',
        'tuesday'   => 'Dinsdag',
        'wednesday' => 'Woensdag',
        'thursday'  => 'Donderdag',
        'friday'    => 'Vrijdag',
        'saturday'  => 'Zaterdag',
        'sunday'    => 'Zondag',
    ];
    return $names[strtolower($day)] ?? ucfirst($day);
}

/**
 * Get today's day name in English (lowercase)
 */
function getTodayDayName(): string {
    $days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    return $days[date('w')];
}

/**
 * Truncate text to given length
 */
function truncate(string $text, int $length = 150): string {
    $text = strip_tags($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, strrpos(substr($text, 0, $length), ' ')) . '...';
}

/**
 * Check if IP is rate limited for requests
 */
function isRateLimited(string $ip): bool {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM requests 
            WHERE ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ');
        $stmt->execute([$ip, REQUEST_RATE_LIMIT]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get user IP address
 */
function getUserIp(): string {
    $ip = $_SERVER['HTTP_CLIENT_IP'] 
        ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
        ?? $_SERVER['REMOTE_ADDR'] 
        ?? '0.0.0.0';
    return filter_var(explode(',', $ip)[0], FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

// =====================================================
// AFDELINGEN
// =====================================================

/**
 * Alle afdelingen ophalen
 */
function getDepartments(): array {
    try {
        return getPDO()->query('SELECT * FROM departments ORDER BY name ASC')->fetchAll();
    } catch (PDOException $e) { return []; }
}

/**
 * Één afdeling op slug
 */
function getDepartment(string $slug): ?array {
    try {
        $stmt = getPDO()->prepare('SELECT * FROM departments WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) { return null; }
}

/**
 * E-mailadressen van één afdeling
 */
function getDepartmentEmails(string $departmentSlug): array {
    try {
        $stmt = getPDO()->prepare('SELECT * FROM department_emails WHERE department_slug = ? ORDER BY label ASC');
        $stmt->execute([$departmentSlug]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

// =====================================================
// SOLLICITATIES
// =====================================================

/**
 * Aantal nieuwe sollicitaties (optioneel gefilterd op afdeling)
 */
function getNewApplicationsCount(array $depts = []): int {
    try {
        $pdo = getPDO();
        if (empty($depts)) {
            return (int)$pdo->query('SELECT COUNT(*) FROM applications WHERE status = "new"')->fetchColumn();
        }
        $placeholders = implode(',', array_fill(0, count($depts), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'new' AND department IN ($placeholders)");
        $stmt->execute($depts);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) { return 0; }
}

/**
 * Haal sollicitaties op (gefilterd op afdeling, status, paginering)
 */
function getApplications(array $depts = [], string $status = 'all', int $limit = 100, int $offset = 0): array {
    try {
        $pdo    = getPDO();
        $where  = [];
        $params = [];

        if (!empty($depts)) {
            $placeholders = implode(',', array_fill(0, count($depts), '?'));
            $where[]  = "department IN ($placeholders)";
            $params   = array_merge($params, $depts);
        }
        if ($status !== 'all') {
            $where[]  = 'status = ?';
            $params[] = $status;
        }

        $sql = 'SELECT a.*, u.username as reviewer_name FROM applications a
                LEFT JOIN users u ON a.reviewed_by = u.id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?';

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

/**
 * Één sollicitatie ophalen op id
 */
function getApplication(int $id): ?array {
    try {
        $stmt = getPDO()->prepare('SELECT a.*, u.username as reviewer_name FROM applications a LEFT JOIN users u ON a.reviewed_by = u.id WHERE a.id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) { return null; }
}

/**
 * Status-label in Nederlands
 */
function applicationStatusLabel(string $status): string {
    return ['new'=>'Nieuw','in_review'=>'In behandeling','accepted'=>'Geaccepteerd','rejected'=>'Afgewezen'][$status] ?? $status;
}

// =====================================================
// BEDRIJFSMAIL CREDENTIALS
// =====================================================

/**
 * Versleutel een wachtwoord voor opslag in de database.
 * Gebruikt AES-256-CBC met een random IV per opslag.
 */
function encryptMailPassword(string $plaintext): string {
    $ivLen = openssl_cipher_iv_length(MAIL_CRYPT_CIPHER);
    $iv    = random_bytes($ivLen);
    $key   = hash('sha256', MAIL_CRYPT_KEY, true); // 32-byte sleutel
    $enc   = openssl_encrypt($plaintext, MAIL_CRYPT_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    // Sla IV + ciphertext samen op als base64
    return base64_encode($iv . $enc);
}

/**
 * Ontsleutel een opgeslagen wachtwoord.
 * Geeft null terug als ontsleuteling mislukt.
 */
function decryptMailPassword(string $stored): ?string {
    $data  = base64_decode($stored, true);
    if ($data === false) return null;
    $ivLen = openssl_cipher_iv_length(MAIL_CRYPT_CIPHER);
    if (strlen($data) <= $ivLen) return null;
    $iv    = substr($data, 0, $ivLen);
    $enc   = substr($data, $ivLen);
    $key   = hash('sha256', MAIL_CRYPT_KEY, true);
    $plain = openssl_decrypt($enc, MAIL_CRYPT_CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    return $plain !== false ? $plain : null;
}

/**
 * Haal de mailcredentials op voor een specifieke gebruiker.
 * Geeft null terug als er geen credentials zijn.
 */
function getUserMailCredentials(int $userId): ?array {
    try {
        $stmt = getPDO()->prepare(
            'SELECT * FROM user_mail_credentials WHERE user_id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        // Ontsleutel het wachtwoord in-memory, NOOIT in de DB opslaan als plaintext
        $row['mail_password_plain'] = decryptMailPassword($row['mail_password_enc']);
        return $row;
    } catch (PDOException $e) {
        error_log('getUserMailCredentials error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Sla mailcredentials op (nieuw of update) voor een gebruiker.
 * Het wachtwoord wordt automatisch versleuteld.
 */
function saveUserMailCredentials(
    int    $userId,
    string $mailAddress,
    string $plainPassword,
    string $imapServer  = 'mail.soulfm.nl',
    string $smtpServer  = 'mail.soulfm.nl',
    int    $imapPort    = 993,
    int    $smtpPort    = 587,
    string $extraNotes  = ''
): bool {
    try {
        $enc  = encryptMailPassword($plainPassword);
        $pdo  = getPDO();
        $stmt = $pdo->prepare('
            INSERT INTO user_mail_credentials
                (user_id, mail_address, mail_password_enc, imap_server, smtp_server,
                 imap_port, smtp_port, extra_notes)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                mail_address     = VALUES(mail_address),
                mail_password_enc= VALUES(mail_password_enc),
                imap_server      = VALUES(imap_server),
                smtp_server      = VALUES(smtp_server),
                imap_port        = VALUES(imap_port),
                smtp_port        = VALUES(smtp_port),
                extra_notes      = VALUES(extra_notes),
                updated_at       = NOW()
        ');
        $stmt->execute([
            $userId, $mailAddress, $enc,
            $imapServer, $smtpServer,
            $imapPort, $smtpPort,
            $extraNotes ?: null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('saveUserMailCredentials error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verwijder mailcredentials van een gebruiker.
 */
function deleteUserMailCredentials(int $userId): bool {
    try {
        getPDO()->prepare('DELETE FROM user_mail_credentials WHERE user_id = ?')
                ->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Haal alle gebruikers op met hun mailcredentials (voor admin overzicht).
 */
function getAllUsersWithMailStatus(): array {
    try {
        return getPDO()->query('
            SELECT u.id, u.username, u.email, u.role, u.active,
                   mc.mail_address,
                   mc.updated_at AS mail_updated_at,
                   IF(mc.id IS NOT NULL, 1, 0) AS has_mail_creds
            FROM users u
            LEFT JOIN user_mail_credentials mc ON mc.user_id = u.id
            WHERE u.role != "listener"
            ORDER BY u.role, u.username
        ')->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Haal afdelingsmail-credentials op voor een afdeling.
 */
function getDepartmentMailCredentials(string $departmentSlug): ?array {
    try {
        $stmt = getPDO()->prepare('SELECT * FROM department_mail_credentials WHERE department_slug = ? LIMIT 1');
        $stmt->execute([$departmentSlug]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['mail_password_plain'] = decryptMailPassword($row['mail_password_enc']);
        return $row;
    } catch (PDOException $e) {
        error_log('getDepartmentMailCredentials error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Opslaan/update van afdelingsmail-credentials.
 */
function saveDepartmentMailCredentials(
    string $departmentSlug,
    string $mailAddress,
    string $plainPassword,
    string $imapServer = 'mail.soulfm.nl',
    string $smtpServer = 'mail.soulfm.nl',
    int $imapPort = 993,
    int $smtpPort = 587,
    string $extraNotes = ''
): bool {
    try {
        $enc = encryptMailPassword($plainPassword);
        $stmt = getPDO()->prepare('
            INSERT INTO department_mail_credentials
                (department_slug, mail_address, mail_password_enc, imap_server, smtp_server, imap_port, smtp_port, extra_notes)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                mail_address = VALUES(mail_address),
                mail_password_enc = VALUES(mail_password_enc),
                imap_server = VALUES(imap_server),
                smtp_server = VALUES(smtp_server),
                imap_port = VALUES(imap_port),
                smtp_port = VALUES(smtp_port),
                extra_notes = VALUES(extra_notes),
                updated_at = NOW()
        ');
        $stmt->execute([
            $departmentSlug,
            $mailAddress,
            $enc,
            $imapServer,
            $smtpServer,
            $imapPort,
            $smtpPort,
            $extraNotes ?: null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('saveDepartmentMailCredentials error: ' . $e->getMessage());
        return false;
    }
}

/**
 * DJ live-credentials voor 1 gebruiker ophalen.
 */
function getDjLiveCredentials(int $userId): ?array {
    try {
        $stmt = getPDO()->prepare('SELECT * FROM dj_live_credentials WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['password_plain'] = decryptMailPassword($row['password_enc']);
        return $row;
    } catch (PDOException $e) {
        error_log('getDjLiveCredentials error: ' . $e->getMessage());
        return null;
    }
}

/**
 * DJ live-credentials opslaan of updaten.
 */
function saveDjLiveCredentials(
    int $userId,
    string $streamType,
    string $host,
    string $mountPoint,
    string $username,
    string $password,
    int $port,
    string $extraNotes = ''
): bool {
    try {
        $enc = encryptMailPassword($password);
        $stmt = getPDO()->prepare('
            INSERT INTO dj_live_credentials
                (user_id, stream_type, host, mount_point, username, password_enc, port, extra_notes)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                stream_type = VALUES(stream_type),
                host = VALUES(host),
                mount_point = VALUES(mount_point),
                username = VALUES(username),
                password_enc = VALUES(password_enc),
                port = VALUES(port),
                extra_notes = VALUES(extra_notes),
                updated_at = NOW()
        ');
        $stmt->execute([
            $userId,
            $streamType,
            $host,
            $mountPoint,
            $username,
            $enc,
            $port,
            $extraNotes ?: null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('saveDjLiveCredentials error: ' . $e->getMessage());
        return false;
    }
}

/**
 * DJ live-credentials verwijderen.
 */
function deleteDjLiveCredentials(int $userId): bool {
    try {
        getPDO()->prepare('DELETE FROM dj_live_credentials WHERE user_id = ?')->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Controleer of een tabel bestaat.
 */
function tableExists(string $tableName): bool {
    try {
        $stmt = getPDO()->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Alle gebruikers met status van live-credentials.
 */
function getDjsWithLiveStatus(): array {
    try {
        if (!tableExists('dj_live_credentials')) {
            return getPDO()->query('
                SELECT u.id, u.username, u.email, u.role, u.active,
                       NULL AS stream_type, NULL AS host, NULL AS mount_point, NULL AS live_username,
                       NULL AS port, NULL AS live_updated_at,
                       0 AS has_live_creds
                FROM users u
                ORDER BY u.role, u.username
            ')->fetchAll();
        }

        return getPDO()->query('
            SELECT u.id, u.username, u.email, u.role, u.active,
                   lc.stream_type, lc.host, lc.mount_point, lc.username AS live_username,
                   lc.port, lc.updated_at AS live_updated_at,
                   IF(lc.id IS NOT NULL, 1, 0) AS has_live_creds
            FROM users u
            LEFT JOIN dj_live_credentials lc ON lc.user_id = u.id
            ORDER BY u.role, u.username
        ')->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Publieke teamleden (alleen actief) gesorteerd op volgorde.
 */
function getPublicTeamMembers(): array {
    try {
        $stmt = getPDO()->query('
            SELECT id, name, role_title, bio, photo_url, display_order
            FROM team_members
            WHERE is_active = 1
            ORDER BY display_order ASC, name ASC
        ');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Alle teamleden voor admin beheer.
 */
function getAllTeamMembers(): array {
    try {
        $stmt = getPDO()->query('
            SELECT id, name, role_title, bio, photo_url, display_order, is_active, created_at, updated_at
            FROM team_members
            ORDER BY display_order ASC, name ASC
        ');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
