<?php
/**
 * SoulFM - Now Playing API Endpoint (AzuraCast Live Integration - No Cache)
 * GET /api/now-playing.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

// Haal direct de live data op via onze nieuwe functie in functions.php
$data = getLiveAzuraCastData();

// ===== DATA VERWERKEN =====
if ($data) {
    $isLive       = $data['live']['is_live'] ?? false;
    $streamerName = $data['live']['streamer_name'] ?? '';
    $listeners    = $data['listeners']['total'] ?? 0;
    
    $artist       = $data['now_playing']['song']['artist'] ?? 'SoulFM';
    $title        = $data['now_playing']['song']['title'] ?? 'Non-Stop';

    // Bepaal de DJ en het Programma uit de SQL-database
    $dbProgram    = getCurrentProgram();
    $djName       = $isLive ? $streamerName : ($dbProgram['dj_name'] ?? 'Autopilot');
    $programName  = $dbProgram['program_name'] ?? 'SoulFM Live';
    $startTime    = $dbProgram['start_time'] ?? '00:00:00';
    $endTime      = $dbProgram['end_time'] ?? '23:59:59';
    $genre        = $dbProgram['genre'] ?? 'Soul & Motown';

    // Haal de gefilterde songtekst op
    $nowPlayingSong = getCurrentSong();

    if ($nowPlayingSong === 'Non-Stop De Lekkerste Soul & Motown') {
        $artist = 'SoulFM';
        $title  = 'Non-Stop';
    }

    echo json_encode([
        'success'      => true,
        'program_name' => $programName,
        'dj_name'      => $djName,
        'song'         => $nowPlayingSong,
        'artist'       => $artist,
        'title'        => $title,
        'genre'        => $genre,
        'start_time'   => $startTime,
        'end_time'     => $endTime,
        'listeners'    => $listeners,
        'timestamp'    => time(),
    ]);
} else {
    // Fallback voor als AzuraCast even niet te bereiken is
    $program = getCurrentProgram();
    echo json_encode([
        'success'      => true,
        'program_name' => $program['program_name'] ?? 'SoulFM Live',
        'dj_name'      => $program['dj_name'] ?? 'Autopilot',
        'song'         => getCurrentSong(),
        'artist'       => 'SoulFM',
        'title'        => 'Non-Stop',
        'genre'        => $program['genre'] ?? 'Soul Mix',
        'start_time'   => $program['start_time'] ?? '00:00:00',
        'end_time'     => $program['end_time'] ?? '23:59:59',
        'listeners'    => getListenerCount(),
        'timestamp'    => time(),
    ]);
}