<?php
/*
    Security: class_random_skills.php and class_random_stat.php are standalone AJAX endpoints
    that bypass header.php entirely (they only require settings.php - no session/module bootstrap
    runs), so they never got header.php's login handling or its central CSRF check. That left
    both roll endpoints reachable by any anonymous visitor, with no check that the caller actually
    owns (or globally administers) the player being rolled for - letting anyone pre-seed/pollute a
    coach's pending skill/stat roll. This is the shared, single-location check both files call
    right after opening their own DB connection.

    Returns true and does nothing further if the request is allowed. On any failure, emits a
    403 JSON error itself and returns false - the caller should just `exit` in that case.
*/
function verifyRollRequest($conn, $playerId) {
    $playerId = (int) $playerId; # Defense in depth - callers already cast this, but it's spliced into raw SQL below.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $fail = function($msg) {
        http_response_code(403);
        echo json_encode(array('error' => $msg));
        return false;
    };

    // CSRF: this endpoint is only ever called via fetch()/XHR from a page that itself went
    // through header.php (and so already has a session token) - the caller must echo it back.
    $csrfOk = isset($_SESSION['csrf_token']) && isset($_POST['csrf_token'])
        && is_string($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if (!$csrfOk) {
        return $fail('Invalid or missing CSRF token.');
    }

    // Must be logged in.
    if (empty($_SESSION['logged_in']) || empty($_SESSION['coach_id'])) {
        return $fail('You must be logged in.');
    }
    $sessionCoachId = (int) $_SESSION['coach_id'];

    // Global admins (ring 5) may act on any player. Otherwise the session coach must own the
    // team that owns this player.
    $result = mysqli_query($conn, "SELECT ring FROM coaches WHERE coach_id = $sessionCoachId LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if (!$row) {
        return $fail('Coach not found.');
    }
    if ((int) $row['ring'] === 5) { // Coach::T_RING_GLOBAL_ADMIN
        return true;
    }

    $result = mysqli_query($conn, "SELECT t.owned_by_coach_id FROM players AS p INNER JOIN teams AS t ON t.team_id = p.owned_by_team_id WHERE p.player_id = $playerId LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if (!$row || (int) $row['owned_by_coach_id'] !== $sessionCoachId) {
        return $fail('You do not have access to this player.');
    }

    return true;
}
