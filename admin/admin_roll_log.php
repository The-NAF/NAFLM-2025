<?php
/*
 * admin_roll_log.php
 * Random Roll History — admin view
 * Accessed via index.php?section=admin&subsec=roll_log
 */
global $coach, $rules, $lng;
$IS_COMMISH = is_object($coach) && (
    $coach->ring == Coach::T_RING_GLOBAL_ADMIN
    || $coach->isLeagueCommish()
);
if (!$IS_COMMISH) {
    echo "<div class='boxWide'>";
    HTMLOUT::helpBox('Access denied. You must be a league administrator.', '', 'errorBox');
    echo "</div>";
    return;
}
// --- Handle clear-pending POST action --
if (isset($_POST['action']) && $_POST['action'] === 'clear_pending') {
    $rid = isset($_POST['roll_id']) ? (int)$_POST['roll_id'] : 0;
    if ($rid > 0) {
        $cby = (int)$coach->coach_id;
        $now = date('Y-m-d H:i:s');
        mysql_query(
            "UPDATE pending_rolls "
            . "SET is_confirmed = 2, confirmed_at = '$now', cleared_by = $cby "
            . "WHERE roll_id = $rid AND is_confirmed = 0"
        );
        echo "<div class='boxWide'>";
        if (mysql_affected_rows() === 1) {
            HTMLOUT::helpBox('Pending roll cleared successfully.', '');
        } else {
            HTMLOUT::helpBox('Roll was not cleared (already confirmed or not found).', '', 'errorBox');
        }
        echo "</div>";
    }
}
// --- Build filter values from GET --
$filter_status   = isset($_GET['f_status'])  ? $_GET['f_status']  : 'all';
$filter_type     = isset($_GET['f_type'])    ? $_GET['f_type']    : 'all';
$filter_team     = isset($_GET['f_team'])    ? (int)$_GET['f_team'] : 0;
// --- Build WHERE clause --
$where = array('1=1');
if ($filter_status === 'pending') {
    $where[] = 'pr.is_confirmed = 0';
} elseif ($filter_status === 'confirmed') {
    $where[] = 'pr.is_confirmed = 1';
} elseif ($filter_status === 'cleared') {
    $where[] = 'pr.is_confirmed = 2';
}
if ($filter_type === 'skill') {
    $where[] = "pr.roll_type = 'skill'";
} elseif ($filter_type === 'stat') {
    $where[] = "pr.roll_type = 'stat'";
}
if ($filter_team > 0) {
    $where[] = "p.owned_by_team_id = $filter_team";
}
$whereStr = implode(' AND ', $where);
// --- Fetch records --
$sql = "
    SELECT
        pr.roll_id,
        pr.player_id,
        pl.nr             AS player_nr,
        pl.name           AS player_name,
        pl.owned_by_team_id,
        t.name            AS team_name,
        c.name            AS coach_name,
        pr.roll_type,
        pr.roll_data,
        pr.chosen_id,
        pr.is_confirmed,
        pr.generated_at,
        pr.confirmed_at,
        pr.cleared_by,
        clr.name          AS cleared_by_name
    FROM pending_rolls pr
    JOIN players  pl  ON pr.player_id        = pl.player_id
    JOIN teams    t   ON pl.owned_by_team_id = t.team_id
    JOIN coaches  c   ON t.owned_by_coach_id = c.coach_id
    LEFT JOIN coaches clr ON pr.cleared_by   = clr.coach_id
    WHERE $whereStr
    ORDER BY pr.generated_at DESC
    LIMIT 200
";
$result = mysql_query($sql);
// --- Team dropdown data --
$teamsResult = mysql_query(
    "SELECT DISTINCT t.team_id, t.name AS team_name "
    . "FROM teams t "
    . "JOIN players pl ON pl.owned_by_team_id = t.team_id "
    . "JOIN pending_rolls pr ON pr.player_id = pl.player_id "
    . "ORDER BY t.name"
);
// --- Status label helper --
function rollStatusLabel($isConfirmed) {
    if ($isConfirmed == 0) return "<span style='color:#fcf403;font-weight:bold;'>Pending</span>";
    if ($isConfirmed == 1) return "<span style='color:green;font-weight:bold;'>Confirmed</span>";
    return "<span style='color:#999;'>Cleared by Admin</span>";
}
// --- Decode roll summary for display --
function rollSummary($rollType, $rollData) {
    $data = json_decode($rollData, true);
    if (!$data) return '(unreadable)';
    if ($rollType === 'skill') {
        $names = array();
        foreach ($data as $opt) {
            $names[] = isset($opt['name']) ? $opt['name'] : '?';
        }
        return implode(' / ', $names);
    } elseif ($rollType === 'stat') {
        $roll    = isset($data['roll'])    ? $data['roll']    : '?';
        $offered = isset($data['offered']) ? $data['offered'] : array();
        $isAny   = isset($data['is_any'])  ? $data['is_any']  : false;
        $stats 	 = $isAny ? 'Any' : implode(' or ', array_map('strtoupper', $offered));
        return "D8=" . $roll . " (" . $stats . ")";
    }
    return '(unknown type)';
}
?>
<div class='boxWide'>
    <h3 class='boxTitle4'>Random Roll History</h3>
    <div class='boxConf'>
        <!-- Filter form -->
        <form method="GET" action="index.php" style="margin-bottom:16px;">
            <input type="hidden" name="section" value="admin">
            <input type="hidden" name="subsec"  value="roll_log">
            Status:&nbsp;
            <select name="f_status">
                <option value="all"<?php echo $filter_status==='all'?' selected':'';?>>All</option>
                <option value="pending"<?php echo $filter_status==='pending'?' selected':'';?>>Pending</option>
                <option value="confirmed"<?php echo $filter_status==='confirmed'?' selected':'';?>>Confirmed</option>
                <option value="cleared"<?php echo $filter_status==='cleared'?' selected':'';?>>Cleared by Admin</option>
            </select>
            &nbsp;&nbsp;
            Type:&nbsp;
            <select name="f_type">
                <option value="all"<?php echo $filter_type==='all'?' selected':'';?>>All</option>
                <option value="skill"<?php echo $filter_type==='skill'?' selected':'';?>>Skill</option>
                <option value="stat"<?php echo $filter_type==='stat'?' selected':'';?>>Stat</option>
            </select>
            &nbsp;&nbsp;
            Team:&nbsp;
            <select name="f_team">
                <option value="0">-- All Teams --</option>
                <?php
                while ($tr = mysql_fetch_assoc($teamsResult)) {
                    $sel = ($filter_team == $tr['team_id']) ? ' selected' : '';
                    echo "<option value='" . (int)$tr['team_id'] . "'$sel>" . htmlspecialchars($tr['team_name']) . "</option>";
                }
                ?>
            </select>
            &nbsp;&nbsp;
            <input type="submit" value="Filter">
        </form>
        <!-- Results table -->
        <div class='tableResponsive'>
        <table class="common" width="100%">
            <tr class="commonhead">
                <td colspan="9"><b>Roll Log
                <?php
                $totalRes = mysql_query(
                    "SELECT COUNT(*) AS cnt FROM pending_rolls pr "
                    . "JOIN players pl ON pr.player_id = pl.player_id "
                    . "WHERE $whereStr"
                );
                $totalRow = mysql_fetch_assoc($totalRes);
                echo ' (' . $totalRow['cnt'] . ' records';
                if ($totalRow['cnt'] > 200) echo ', showing latest 200';
                echo ')';
                ?>
                </b></td>
            </tr>
            <tr>
                <td><i>#</i></td>
                <td><i>Player</i></td>
                <td><i>Team / Coach</i></td>
                <td><i>Type</i></td>
                <td><i>Roll Offered</i></td>
                <td><i>Chosen</i></td>
                <td><i>Status</i></td>
                <td><i>Generated</i></td>
                <td><i>Confirmed / Cleared</i></td>
            </tr>
            <tr><td colspan="9"><hr></td></tr>
            <?php
            $rowNum = 1;
            while ($row = mysql_fetch_assoc($result)) {
                $bgColor = ($row['is_confirmed'] == 0) ? 'background:#fa0000;' : '';
                echo "<tr style='$bgColor'>";
                echo "<td>" . $rowNum++ . "</td>";
				echo "<td>#" . htmlspecialchars($row['player_nr']) . " " . htmlspecialchars($row['player_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['team_name']) . "<br><small>" . htmlspecialchars($row['coach_name']) . "</small></td>";
                echo "<td>" . htmlspecialchars($row['roll_type']) . "</td>";
                echo "<td>" . htmlspecialchars(rollSummary($row['roll_type'], $row['roll_data'])) . "</td>";
                $chosen = '<span style="color:#aaa;">—</span>';
				if ($row['chosen_id'] && $row['roll_type'] === 'skill') {
					$rollData = json_decode($row['roll_data'], true);
					if (is_array($rollData)) {
						foreach ($rollData as $opt) {
							if ((int)$opt['id'] === (int)$row['chosen_id']) {
								$chosen = htmlspecialchars($opt['name']);
								break;
							}
						}
					}
				} elseif ($row['chosen_id'] && strpos($row['chosen_id'], 'skill:') === 0) {
					// Stat roll where a skill was chosen instead — resolve the ID to a name
					global $skillididx;
					$sid = (int)substr($row['chosen_id'], 6);
					$chosen = isset($skillididx[$sid])
						? htmlspecialchars($skillididx[$sid]) . ' <small>(skill instead of stat)</small>'
						: htmlspecialchars($row['chosen_id']);
				} elseif ($row['chosen_id']) {
					$chosen = htmlspecialchars(strtoupper($row['chosen_id'])); // stat key e.g. 'ma'
				}
								echo "<td>" . $chosen . "</td>";
                echo "<td>" . rollStatusLabel($row['is_confirmed']);
                if ($row['is_confirmed'] == 2 && $row['cleared_by_name']) {
                    echo "<br><small>by " . htmlspecialchars($row['cleared_by_name']) . "</small>";
                }
                echo "</td>";
                echo "<td><small>" . htmlspecialchars($row['generated_at']) . "</small></td>";
                $confAt = $row['confirmed_at'] ? "<small>" . htmlspecialchars($row['confirmed_at']) . "</small>" : '<span style="color:#aaa;">—</span>';
                echo "<td>$confAt";
                // Clear button — only for pending rows
                if ($row['is_confirmed'] == 0) {
                    $rid = (int)$row['roll_id'];
                    $pname = htmlspecialchars($row['player_name']);
                    echo "<br><form method='POST' style='display:inline;'>";
                    echo "<input type='hidden' name='action'  value='clear_pending'>";
                    echo "<input type='hidden' name='roll_id' value='$rid'>";
                    echo "<input type='hidden' name='section' value='admin'>";
                    echo "<input type='hidden' name='subsec'  value='roll_log'>";
                    echo "<input type='submit' value='Clear' onclick=\"return confirm('Clear pending roll for $pname? This cannot be undone.')\">";
                    echo "</form>";
                }
                echo "</td>";
                echo "</tr>\n";
            }
            if ($rowNum === 1) {
                echo "<tr><td colspan='9' style='text-align:center; color:#999; padding:16px;'>No records match the current filter.</td></tr>\n";
            }
            ?>
        </table>
        </div>
    </div>
</div>