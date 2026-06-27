<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require_once('../settings.php');
header('Content-Type: application/json');
// D8 table identical to the JS version in class_team_htmlout.php
$D8_TABLE = array(
    1 => array('av'),
    2 => array('av','pa'),
    3 => array('av','ma','pa'),
    4 => array('av','ma','pa'),
    5 => array('ma','pa'),
    6 => array('ag','ma'),
    7 => array('ag','st'),
    8 => array('ma','st','ag','av','pa')  // Any
);
try {
    $playerId = isset($_POST['player_id']) ? (int)$_POST['player_id'] : 0;
    $sppCost  = isset($_POST['spp_cost'])  ? (int)$_POST['spp_cost']  : 0;
    if (!$playerId || !$sppCost) {
        throw new Exception('Missing required parameters');
    }
    $conn = mysqli_connect($db_host, $db_user, $db_passwd, $db_name);
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    mysqli_set_charset($conn, 'utf8');
    // Check for existing pending roll — return it if found
    $check = mysqli_query($conn,
        "SELECT roll_id, roll_data FROM pending_rolls "
        . "WHERE player_id = $playerId AND is_confirmed = 0 "
        . "AND roll_type = 'stat' LIMIT 1"
    );
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        echo json_encode(array(
            'roll_data' => json_decode($row['roll_data'], true),
            'pending'   => true,
            'roll_id'   => $row['roll_id']
        ));
        mysqli_close($conn);
        exit;
    }
    // Perform the D8 roll server-side
    $roll    = mt_rand(1, 8);
    $offered = $D8_TABLE[$roll];
    $isAny   = ($roll === 8);
    $rollData = array(
        'roll'     => $roll,
        'offered'  => $offered,
        'is_any'   => $isAny,
        'spp_cost' => $sppCost
    );
    $rollDataJson = json_encode($rollData);
    // Save to pending_rolls
    $esc = mysqli_real_escape_string($conn, $rollDataJson);
    mysqli_query($conn,
        "INSERT INTO pending_rolls "
        . "(player_id, roll_type, roll_data, is_confirmed) "
        . "VALUES ($playerId, 'stat', '$esc', 0)"
    );
    echo json_encode(array(
        'roll_data' => $rollData,
        'pending'   => false,
        'roll_id'   => (int)mysqli_insert_id($conn)
    ));
    mysqli_close($conn);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}