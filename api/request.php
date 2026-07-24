<?php
/**
 * SoulFM - Song Request API Endpoint
 * POST /api/request.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode niet toegestaan.']);
    exit;
}

$errors = [];

// CSRF
$token = trim($_POST['csrf_token'] ?? '');
if (!validateCsrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Beveiligingstoken ongeldig. Herlaad de pagina en probeer opnieuw.']);
    exit;
}

// Gather and sanitize inputs
$songTitle     = sanitize($_POST['song_title']     ?? '');
$artistName    = sanitize($_POST['artist_name']    ?? '');
$requesterName = sanitize($_POST['requester_name'] ?? '');
$message       = sanitize($_POST['message']        ?? '');
$agreeTerms    = !empty($_POST['agree_terms']);
$ip            = getUserIp();

// Validate
if (strlen($songTitle) < 1) {
    $errors['song_title'] = 'Vul de songtitel in.';
} elseif (strlen($songTitle) > 200) {
    $errors['song_title'] = 'Songtitel is te lang (max 200 tekens).';
}

if (strlen($artistName) < 1) {
    $errors['artist_name'] = 'Vul de artiest in.';
} elseif (strlen($artistName) > 200) {
    $errors['artist_name'] = 'Artiestennaam is te lang (max 200 tekens).';
}

if (strlen($requesterName) < 1) {
    $errors['requester_name'] = 'Vul jouw naam in.';
} elseif (strlen($requesterName) > 100) {
    $errors['requester_name'] = 'Naam is te lang (max 100 tekens).';
}

if (!$agreeTerms) {
    $errors['agree_terms'] = 'Je moet akkoord gaan met de huisregels.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Controleer de ingevulde gegevens.', 'errors' => $errors]);
    exit;
}

// Rate limiting
if (isRateLimited($ip)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Je hebt al een verzoekje ingediend. Wacht ' . REQUEST_RATE_LIMIT . ' minuten voor een nieuw verzoekje.'
    ]);
    exit;
}

// Insert into DB
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('
        INSERT INTO requests (song_title, artist_name, requester_name, message, ip_address, status)
        VALUES (?, ?, ?, ?, ?, "pending")
    ');
    $stmt->execute([$songTitle, $artistName, $requesterName, $message, $ip]);

    echo json_encode([
        'success' => true,
        'message' => 'Jouw verzoekje is ontvangen! We draaien het zo snel mogelijk.'
    ]);
} catch (PDOException $e) {
    error_log('Request insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Er is een fout opgetreden. Probeer het later opnieuw.']);
}
