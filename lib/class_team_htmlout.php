<?php

define('T_HTML_TEAMS_PER_PAGE', 50);

class Team_HTMLOUT extends Team
{
	public static function dispList() {
		global $lng;
		/*
			NOTE: We do NOT show teams not having played any matches for nodes = {T_NODE_TOURNAMENT, T_NODE_DIVISION}.
		*/
		list($sel_node, $sel_node_id, $sel_state, $sel_race, $sel_format) = HTMLOUT::nodeSelector(array('race' => true, 'state' => true, 'format' => true));
		$ALL_TIME = ($sel_node === false && $sel_node_id === false);
		$fields = '_RRP AS "team_id", owned_by_coach_id, f_race_id, teams.name AS "tname", f_cname, f_rname, tv, teams.rdy AS "rdy", teams.retired AS "retired", teams.format AS "format"';
		$where = array();
		if ($sel_state == T_STATE_ACTIVE) $where[] = 'teams.rdy IS TRUE AND teams.retired IS FALSE';
		if ($sel_race != T_RACE_ALL) 	  $where[] = "teams.f_race_id = $sel_race";
		if ($sel_format != T_FORMAT_ALL)  $where[] = "races.format = $sel_format";
		if ($sel_node == T_NODE_LEAGUE || $ALL_TIME) {
			if (!$ALL_TIME) {
				$where[] = "f_lid = $sel_node_id";
			}
			$where = (count($where) > 0) ? 'WHERE '.implode(' AND ', $where) : '';
			$queryCnt = "SELECT COUNT(*) FROM teams JOIN races ON teams.f_race_id = races.race_id $where";
			$queryGet = 'SELECT '.preg_replace('/\_RRP/', 'team_id', $fields).' FROM teams JOIN races ON teams.f_race_id = races.race_id '.$where.' ORDER BY tname ASC';
		} else {
			$q = "SELECT $fields FROM matches, teams, races, tours, divisions WHERE matches._RRP = teams.team_id AND teams.f_race_id = races.race_id AND matches.f_tour_id = tours.tour_id AND tours.f_did = divisions.did ";
			switch ($sel_node) {
				case false: break;
				case T_NODE_TOURNAMENT: $q .= "AND tours.tour_id = $sel_node_id";   break;
				case T_NODE_DIVISION:   $q .= "AND divisions.did = $sel_node_id";   break;
				case T_NODE_LEAGUE:     $q .= "AND divisions.f_lid = $sel_node_id"; break;
			}
			$q .= (count($where) > 0 ? ' AND ' : ' ').implode(' AND ', $where).' ';
			$_subt1 = '('.preg_replace('/\_RRP/', 'team1_id', $q).')';
			$_subt2 = '('.preg_replace('/\_RRP/', 'team2_id', $q).')';
			$queryCnt = "SELECT COUNT(*) FROM (($_subt1) UNION DISTINCT ($_subt2)) AS tmp";
			$queryGet = '('.$_subt1.') UNION DISTINCT ('.$_subt2.') ORDER BY tname ASC';
		}

		$result = mysql_query($queryCnt);
		list($cnt) = mysql_fetch_row($result);
		$pages = ($cnt == 0) ? 1 : ceil($cnt/T_HTML_TEAMS_PER_PAGE);
		global $DEA, $rules, $page;
		$page = (isset($_GET['page']) && $_GET['page'] <= $pages) ? $_GET['page'] : 1; # Page 1 is default, of course.
		$_url = "?section=teamlist&amp;";
		echo "<div class='tableResponsive'>\n";
		echo '<br><center><table>';
		echo '<tr><td>';
		echo $lng->getTrn('common/page').': '.implode(', ', array_map(create_function('$nr', 'global $page; return ($nr == $page) ? $nr : "<a href=\''.$_url.'page=$nr\'>$nr</a>";'), range(1,$pages)));
		echo '</td></td>';
		echo "<tr><td>".$lng->getTrn('common/teams').": $cnt</td></td>";
		echo '</table></center><br>';
		echo "</div>\n";
		$queryGet .= ' LIMIT '.(($page-1)*T_HTML_TEAMS_PER_PAGE).', '.(($page)*T_HTML_TEAMS_PER_PAGE);

		$teams = array();
		$result = mysql_query($queryGet);
		while ($t = mysql_fetch_object($result)) {
			$img = new ImageSubSys(IMGTYPE_TEAMLOGO, $t->team_id);
			$t->logo = "<img border='0px' height='20' width='20' alt='Team race picture' src='".$img->getPath($t->f_race_id)."'>";
			$format = $t->format;
			$t->format = $lng->getTrn('common/'.strtolower($t->format));
			$retired = $t->retired;
			$t->retired = ($t->retired) ? '<b>'.$lng->getTrn('common/yes').'</b>' : $lng->getTrn('common/no');
			$t->rdy = ($t->rdy && !$retired) ? '<font color="green">'.$lng->getTrn('common/yes').'</font>' : '<font color="red">'.$lng->getTrn('common/no').'</font>';
			$t->f_rname = $lng->getTrn('race/'.strtolower(str_replace(' ','', $t->f_rname)));
			$teams[] = $t;
		}
		if ($rules['dungeon'] == 0 || $rules['sevens'] == 0) {
			$fields = array(
				'logo'    => array('desc' => 'Logo', 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id'), 'nosort' => true),
				'tname'   => array('desc' => $lng->getTrn('common/name'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
				'f_cname' => array('desc' => $lng->getTrn('common/coach'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id')),
				'format'  => array('desc' => $lng->getTrn('common/format'), 'nosort' => true),
				'rdy'     => array('desc' => $lng->getTrn('common/ready'), 'nosort' => true),
				'retired' => array('desc' => $lng->getTrn('common/retired'), 'nosort' => true),
				'f_rname' => array('desc' => $lng->getTrn('common/race'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id')),
				'tv'      => array('desc' => 'TV', 'nosort' => true, 'kilo' => true, 'suffix' => 'k'),
		);
		} else {
			$fields = array(
				'logo'    => array('desc' => 'Logo', 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id'), 'nosort' => true),
				'tname'   => array('desc' => $lng->getTrn('common/name'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
				'f_cname' => array('desc' => $lng->getTrn('common/coach'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id')),
				'rdy'     => array('desc' => $lng->getTrn('common/ready'), 'nosort' => true),
				'retired' => array('desc' => $lng->getTrn('common/retired'), 'nosort' => true),
				'f_rname' => array('desc' => $lng->getTrn('common/race'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id')),
				'tv'      => array('desc' => 'TV', 'nosort' => true, 'kilo' => true, 'suffix' => 'k'),
		);	
			
		}
		HTMLOUT::sort_table(
			$lng->getTrn('common/teams'),
			"index.php$_url",
			$teams,
			$fields,
			array(),
			array(),
			array('doNr' => false, 'noHelp' => true, 'noSRdisp' => true)
		);
	}

	public static function standings($node = false, $node_id = false) {
		global $lng, $settings;
		title($lng->getTrn('menu/statistics_menu/team_stn'));
		echo $lng->getTrn('common/notice_simul')."<br><br>\n";
		list($teams, $sortRule) = HTMLOUT::standings(STATS_TEAM,$node,$node_id,array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,false,false), 'hidemenu' => false, 'return_objects' => true));
		$fields = array(
			'name'         => array('desc' => $lng->getTrn('common/team'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
			'f_rname'      => array('desc' => $lng->getTrn('common/race'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id')),
			'f_cname'      => array('desc' => $lng->getTrn('common/coach'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id')),
			'rg_ff'        => array('desc' => 'Dedicated Fans'),
			'rerolls'      => array('desc' => 'RR'),
			'ass_coaches'  => array('desc' => 'Ass. coaches'),
			'cheerleaders' => array('desc' => 'Cheerleaders'),
			'treasury'     => array('desc' => 'Treasury', 'kilo' => true, 'suffix' => 'k'),
			'tv'           => array('desc' => 'TV', 'kilo' => true, 'suffix' => 'k'),
		);

		HTMLOUT::sort_table(
			$lng->getTrn('standings/team/tblTitle2'),
			urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,false,false),
			$teams,
			$fields,
			$sortRule,
			array(),
			array('noHelp' => true, 'noSRdisp' => false, 'doNr' => false)
		);
	}

	public static function profile($tid) {
		global $coach, $settings, $rules;
		$t = new self($tid);
		setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $t->f_lid)); // Load correct $rules for league.

		/* Argument(s) passed to generating functions. */
		$ALLOW_EDIT = $t->allowEdit(); # Show team action boxes?
		$DETAILED   = (isset($_GET['detailed']) && $_GET['detailed'] == 1);# Detailed roster view?

		/* Team pages consist of the output of these generating functions. */
		$t->handleActions($ALLOW_EDIT); # Handles any actions/request sent.
		list($players, $players_backup) = $t->_loadPlayers($DETAILED); # Should come after handleActions().
		$t->_roster($ALLOW_EDIT, $DETAILED, $players);
		$players = $players_backup; # Restore the $players array (_roster() manipulates the passed $players array).
		$t->_menu($ALLOW_EDIT, $DETAILED);

		switch (isset($_GET['subsec']) ? $_GET['subsec'] : 'man') {
			case 'hhmerc': $t->_HHMerc($DETAILED); break;
			case 'hhstar': $t->_HHStar($DETAILED); break;
			case 'man': $t->_actionBoxes($ALLOW_EDIT, $players); break;
			case 'about': $t->_about($ALLOW_EDIT); break;
			case 'news': $t->_news($ALLOW_EDIT); break;
			case 'games': $t->_games(); break;
		}
		if (isset($_GET['subsec'])){
			?>
			<!-- Following HTML from ./lib/class_team_htmlout.php profile -->
			<script language="JavaScript" type="text/javascript">
			window.location = "#anc";
			</script>
			<?php
		}
	}

	public function handleActions($ALLOW_EDIT) {
		global $coach;
		$team = $this; // Copy. Used instead of $this for readability.
		// No request sent?
		if (!isset($_POST['type']) || !$ALLOW_EDIT) {
			return false;
		}
		// Handle hire/fire phase skip (session only, no DB write)
		if ($_POST['type'] === 'skip_hire_phase' && $ALLOW_EDIT) {
			$_SESSION['hire_fire_phase_' . $this->team_id] = 'fire';
			$tid = (int) $this->team_id;
			echo '<script>window.location.replace("index.php?section=objhandler&type=1&obj=2&obj_id=' . $tid . '&subsec=man");</script>';
			exit;
		}
		// Handle request.
		if (get_magic_quotes_gpc()) {
			$_POST['name']     = stripslashes(isset($_POST['name'])  ? $_POST['name']  : '');
			$_POST['skill']    = stripslashes(isset($_POST['skill']) ? $_POST['skill'] : '');
			$_POST['thing']    = stripslashes(isset($_POST['thing']) ? $_POST['thing'] : '');
			$_POST['teamtext'] = stripslashes(isset($_POST['teamtext']) ? $_POST['teamtext'] : '');
			$_POST['txt']      = stripslashes(isset($_POST['txt']) ? $_POST['txt'] : '');
		}
		$p = (isset($_POST['player']) && $_POST['type'] != 'hire_player') ? new Player($_POST['player']) : null;
		switch ($_POST['type']) {
			case 'select_league':      status($team->selectLeague($_POST['teamleague'])); break;	
			case 'select_rule':      status($team->selectRule($_POST['rule'])); break;		
			case 'select_captain':      status($p->selectCaptain($_POST['player'])); break;	
			case 'hire_player':
				list($exitStatus, $pid) = Player::create(
					array(
						'nr'        => $_POST['number'],
						'f_pos_id'  => $_POST['player'],
						'team_id'   => $team->team_id,
						'name'      => $_POST['name']
					),
					array(
						'JM' => isset($_POST['as_journeyman']) && $_POST['as_journeyman']
					)
				);
				status(!$exitStatus, $exitStatus ? Player::$T_CREATE_ERROR_MSGS[$exitStatus] : null);
				break;
			case 'hire_journeyman': status($p->hireJourneyman()); break;
			case 'fire_player':
				// Check minimum TV before allowing player to be fired
				$canFire = true;
				$errorMsg = '';
				
				// Check minimum TV setting
				if (isset($rules['min_tv']) && $rules['min_tv'] > 0) {
					$minTV = $rules['min_tv'];
					
					// Calculate what TV would be after firing this player
					$playerToFire = new Player($_POST['player']);
					$refund = $playerToFire->value * $rules['player_refund'];
					$projectedTV = $team->tv - $playerToFire->value + $refund;
					
					if ($projectedTV < $minTV) {
						$canFire = false;
						$errorMsg = "Cannot fire player: Team value would drop below league minimum of " . ($minTV/1000) . "k (projected TV after firing: " . ($projectedTV/1000) . "k)";
					}
				}
				
				if ($canFire) {
					status($p->sell());
				} else {
					status(false, $errorMsg);
				}
				break;
			case 'unbuy_player':    status($p->unbuy()); break;
			case 'rename_player':   status($p->rename($_POST['name'])); break;
			case 'renumber_player': status($p->renumber($_POST['number'])); break;
			case 'apply_random_skill':
				$playerId = isset($_POST['player_id']) ? $_POST['player_id'] : null;
				$skillId = isset($_POST['skill_id']) ? $_POST['skill_id'] : null;
				$skillType = isset($_POST['skill_type']) ? $_POST['skill_type'] : null;
				
				if ($playerId && $skillId && $skillType) {
					// Validate against pending roll record
                    $pid_check = (int)$playerId;
                    $pr = mysql_fetch_assoc(mysql_query(
                        "SELECT roll_id, roll_data FROM pending_rolls "
                        . "WHERE player_id = $pid_check AND is_confirmed = 0 "
                        . "AND roll_type = 'skill' LIMIT 1"
                    ));
                    if (!$pr) {
                        status(false, 'No pending skill roll found for this player. '
                            . 'Please generate a roll first.');
                        break;
                    }
                    // Verify the submitted skill was one of the two options offered
                    $offered = json_decode($pr['roll_data'], true);
                    $validIds = array();
                    if (is_array($offered)) {
                        foreach ($offered as $opt) {
                            $validIds[] = (int)$opt['id'];
                        }
                    }
                    if (!in_array((int)$skillId, $validIds)) {
                        status(false, 'Submitted skill does not match the pending roll. '
                            . 'You must choose one of the two skills that were rolled.');
                        break;
                    }
					$p = new Player($playerId);
					
					// Third Season Rules: Only Primary random skills allowed
					if ($skillType !== 'P') {
						status(false, 'Only Primary random skills are allowed in Third Season rules');
						break;
					}
					
					// Determine SPP cost based on number of skills (Primary only)
					$numSkills = $p->numberOfAchSkill();
					
					// SPP costs for random PRIMARY skills based on number of achieved skills
					$sppCosts = array(
						0 => 3,   // 0 skills: 3 SPP
						1 => 4,   // 1 skill: 4 SPP
						2 => 6,   // 2 skills: 6 SPP
						3 => 8,   // 3 skills: 8 SPP
						4 => 10,  // 4 skills: 10 SPP
						5 => 15   // 5 skills: 15 SPP
					);
					
					$sppCost = isset($sppCosts[$numSkills]) ? $sppCosts[$numSkills] : 15;
					
					// N for normal/primary (random primary skills are always type N)
					$type = 'N';
					
					// Add the skill with the correct SPP cost and 'R' for Random
					$applied = $p->addSkill($type, (int)$skillId, $sppCost, 'P');
                    if ($applied) {
                        $rid  = (int)$pr['roll_id'];
                        $cid  = (int)$skillId;
                        $now  = date('Y-m-d H:i:s');
                        mysql_query(
                            "UPDATE pending_rolls "
                            . "SET is_confirmed = 1, chosen_id = '$cid', "
                            . "confirmed_at = '$now' "
                            . "WHERE roll_id = $rid"
                        );
                    }
                    status($applied);
				}
				break;
			case 'retire_player':   status($p->retirePlayer());
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
			case 'rename_team':     status($team->rename($_POST['name'])); break;
			case 'buy_goods':       status($team->buy($_POST['thing'])); break;
			case 'drop_goods':      status($team->drop($_POST['thing'])); break;
			case 'ready_state':     status($team->setReady(isset($_POST['bool']))); break;
			case 'retire':          status(isset($_POST['bool']) && $team->setRetired(true)); break;
			case 'delete':          status(isset($_POST['bool']) && $team->delete()); break;
			case 'skill':
				$type = null;
				$skillcost = $_POST['skillcost'];
				list($skcost, $skcosttype) = explode('|', $skillcost);
				$p->setChoosableSkills();
				if     (in_array($_POST['skill'], $p->choosable_skills['norm'])) $type = 'N';
				elseif (in_array($_POST['skill'], $p->choosable_skills['doub'])) $type = 'D';
				else                                                             $type = 'C'; # Assume it's a characteristic.
				status($p->addSkill($type, ($type == 'C') ? (int) str_replace('ach_','',$_POST['skill']) : (int) $_POST['skill'], $skcost, $skcosttype));
				break;
			case 'teamtext': 	status($team->saveText($_POST['teamtext'])); break;
			case 'teamsponsor': status($team->saveSponsor($_POST['teamsponsor'])); break;
			case 'teamstadium': status($team->saveStadium($_POST['teamstadium'])); break;
			case 'news':     	status($team->writeNews($_POST['txt'])); break;
			case 'newsdel':  	status($team->deleteNews($_POST['news_id'])); break;
			case 'newsedit': 	status($team->editNews($_POST['news_id'], $_POST['txt'])); break;
			case 'pic':
				if ($_POST['add_del'] == 'add') {
					if ($_POST['pic_obj'] == IMGTYPE_TEAMSTADIUM) {
						list($status, $msg) = $team->saveStadiumPic(ImageSubSys::$defaultHTMLUploadName.'_stad');
						status($status, (!$status) ? $msg : '');
					}
					elseif ($_POST['pic_obj'] == IMGTYPE_TEAMLOGO) {
						list($status, $msg) = $team->saveLogo(ImageSubSys::$defaultHTMLUploadName.'_logo');
						status($status, (!$status) ? $msg : '');
					}
				} else {
					if ($_POST['pic_obj'] == IMGTYPE_TEAMSTADIUM)
						status($team->deleteStadiumPic());
					elseif ($_POST['pic_obj'] == IMGTYPE_TEAMLOGO)
						status($team->deleteLogo());
				}
				break;
			case 'expensive_mistakes':
				require_once('lib/class_expensive_mistakes.php');
				
				$result = ExpensiveMistakes::performRoll($team->team_id);
				
				if ($result['success']) {
					$msg = "<h3>Expensive Mistakes Roll Result</h3>";
					$msg .= "<p><b>Treasury before:</b> " . ($result['treasury_before']/1000) . "k</p>";
					$msg .= "<p><b>D6 Roll:</b> " . $result['d6_roll'] . " (bracket: " . $result['bracket'] . ")</p>";
					$msg .= "<p><b>Outcome:</b> <font color='" . ($result['outcome'] == 'Catastrophe' ? 'red' : ($result['outcome'] == 'Major Incident' ? 'orange' : 'green')) . "'>" . $result['outcome'] . "</font></p>";
					
					if ($result['additional_rolls']) {
						$msg .= "<p><b>Details:</b> " . $result['additional_rolls'] . "</p>";
					}
					
					$msg .= "<p><b>Gold lost:</b> <font color='red'>" . ($result['loss_amount']/1000) . "k</font></p>";
					$msg .= "<p><b>Treasury after:</b> " . ($result['treasury_after']/1000) . "k</p>";
					
					status(true, $msg);
				} else {
					status(false, $result['error']);
				}
				break;
			case 'apply_random_stat':
				$playerId = isset($_POST['player'])   ? (int)$_POST['player']   : null;
				$statKey  = isset($_POST['stat_key']) ? $_POST['stat_key']       : null;
				$sppCost  = isset($_POST['spp_cost']) ? (int)$_POST['spp_cost']  : null;

				if ($playerId && $statKey && $sppCost) {
					// Validate against pending roll record
                    $pid_check = (int)$playerId;
                    $pr = mysql_fetch_assoc(mysql_query(
                        "SELECT roll_id, roll_data FROM pending_rolls "
                        . "WHERE player_id = $pid_check AND is_confirmed = 0 "
                        . "AND roll_type = 'stat' LIMIT 1"
                    ));
                    if (!$pr) {
                        status(false, 'No pending stat roll found for this player. '
                            . 'Please generate a roll first.');
                        break;
                    }
                    // Verify submitted stat was one of the offered options
                    $pendingData = json_decode($pr['roll_data'], true);
                    $offeredStats = isset($pendingData['offered'])
                        ? $pendingData['offered'] : array();
                    $isAnyRoll = isset($pendingData['is_any']) && $pendingData['is_any'];
                    $statKey_esc = mysql_real_escape_string($statKey);
                    if (!$isAnyRoll && !in_array($statKey_esc, $offeredStats)) {
                        status(false, 'Submitted stat does not match the pending roll.');
                        break;
                    }
					$p = new Player($playerId);
					global $CHR_CONV;
					// Find the chr_id for this stat key
					$chrId = null;
					foreach ($CHR_CONV as $id => $name) {
						if ($name === $statKey) { $chrId = $id; break; }
					}
					if ($chrId !== null) {
                        $applied = $p->addSkill('C', $chrId, $sppCost, 'X');
                        if ($applied) {
                            $rid     = (int)$pr['roll_id'];
                            $cid_esc = mysql_real_escape_string($statKey);
                            $now     = date('Y-m-d H:i:s');
                            mysql_query(
                                "UPDATE pending_rolls "
                                . "SET is_confirmed = 1, chosen_id = '$cid_esc', "
                                . "confirmed_at = '$now' "
                                . "WHERE roll_id = $rid"
                            );
                        }
                        status($applied);
                    } else {
						status(false, 'Invalid stat key: ' . htmlspecialchars($statKey));
					}
				} else {
					status(false, 'Missing parameters for stat increase.');
				}
				break;
			case 'apply_random_stat_skill':
				$playerId  = isset($_POST['player'])    ? (int)$_POST['player']    : null;
				$skillId   = isset($_POST['skill_id'])  ? (int)$_POST['skill_id']  : null;
				$skillType = isset($_POST['skill_type'])? $_POST['skill_type']      : null;
				$sppCost   = isset($_POST['spp_cost'])  ? (int)$_POST['spp_cost']  : null;

				if ($playerId && $skillId && $skillType && $sppCost) {
					// Validate against pending roll record (stat roll, skill-instead path)
                    $pid_check = (int)$playerId;
                    $pr = mysql_fetch_assoc(mysql_query(
                        "SELECT roll_id FROM pending_rolls "
                        . "WHERE player_id = $pid_check AND is_confirmed = 0 "
                        . "AND roll_type = 'stat' LIMIT 1"
                    ));
                    if (!$pr) {
                        status(false, 'No pending stat roll found for this player. '
                            . 'Please generate a roll first.');
                        break;
                    }
					$p = new Player($playerId);
					// Validate skill type
					$type    = ($skillType === 'N') ? 'N' : 'D';
                    // Map to the PV-formula cost bucket: 'P' = Primary/Normal (20k), 'S' = Secondary/Double (40k)
					$costType = ($type === 'N') ? 'P' : 'S';
					$applied  = $p->addSkill($type, $skillId, $sppCost, $costType);
                    if ($applied) {
                        $rid     = (int)$pr['roll_id'];
                        $cid_esc = 'skill:' . (int)$skillId;
                        $now     = date('Y-m-d H:i:s');
                        mysql_query(
                            "UPDATE pending_rolls "
                            . "SET is_confirmed = 1, "
                            . "chosen_id = '$cid_esc', "
                            . "confirmed_at = '$now' "
                            . "WHERE roll_id = $rid"
                        );
                    }
                    status($applied);
				} else {
					status(false, 'Missing parameters for skill-instead submission.');
				}
				break;
		}

		// Administrator tools used?
		if ($coach->isNodeCommish(T_NODE_LEAGUE, $team->f_lid)) {
			switch ($_POST['type']) {
				case 'unhire_journeyman': status($p->unhireJourneyman()); break;
				case 'unsell_player':     status($p->unsell()); break;
				case 'unbuy_goods':       status($team->unbuy($_POST['thing'])); break;
				case 'bank':
					status($team->dtreasury($dtreas = ($_POST['sign'] == '+' ? 1 : -1) * $_POST['amount'] * 1000));
					if (Module::isRegistered('LogSubSys')) {
						Module::run('LogSubSys', array('createEntry', T_LOG_GOLDBANK, $coach->coach_id, "Coach '$coach->name' (ID=$coach->coach_id) added a treasury delta for team '$team->name' (ID=$team->team_id) of amount = $dtreas"));
					}
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
				case 'spp':               status($p->dspp(($_POST['sign'] == '+' ? 1 : -1) * $_POST['amount'])); break;
				case 'dval':              status($p->dval(($_POST['sign'] == '+' ? 1 : -1) * $_POST['amount']*1000)); break;
				case 'extra_skills':
					$func = ($_POST['sign'] == '+') ? 'addSkill' : 'rmSkill';
					status($p->$func('E', $_POST['skill']));
					break;
				case 'ach_skills':
					$type = null;
					if     (in_array($_POST['skill'], $p->ach_nor_skills))  $type = 'N';
					elseif (in_array($_POST['skill'], $p->ach_dob_skills))  $type = 'D';
					else                                                    $type = 'C'; # Assume it's a characteristic.
					status($p->rmSkill($type, ($type == 'C') ? (int) str_replace('ach_','',$_POST['skill']) : (int) $_POST['skill']));
					break;
				case 'manage_hatred':
					$p = new Player((int)$_POST['player']);
					if ($_POST['hatred_action'] === 'remove') {
						status($p->removeHatred($_POST['race_id']));
					} elseif ($_POST['hatred_action'] === 'change') {
						status($p->changeHatred($_POST['race_id'], $_POST['new_race_id']));
					} else {
						status($p->addHatred($_POST['race_id']));
					}
					break;
			    case 'ff':
					status($team->setff_bought($_POST['amount']));
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
				case 'removeNiggle': status($p->removeNiggle()); break;
				case 'addniggle': status($p->addniggle()); break;
				case 'removeMNG': status($p->removeMNG()); 
					SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team->team_id));
					break;
				case 'removenegastat': status($p->removenegastat($_POST['stat'])); break;
				case 'resetleague': status($team->resetLeague()); break;
				case 'resetrule': status($team->resetRule()); break;
				case 'resetcaptain': status($team->resetCaptain()); break;
				case 'transferteam': status($team->setOwnership($_POST['coachname'])); break;
			}
		}
		$team->setStats(false,false,false); # Reload fields in case they changed after team actions made.
	}

	private function _loadPlayers($DETAILED) {
		/*
			Lets prepare the players for the roster.
		*/
		global $settings;
		$team = $this; // Copy. Used instead of $this for readability.
		$players = $players_org = array();
		$players_org = $team->getPlayers();
		// Make two copies: We will be overwriting $players later when the roster has been printed, so that the team actions boxes have the correct untempered player data to work with.
		foreach ($players_org as $p) {
			array_push($players, clone $p);
		}
		// Filter players depending on settings and view mode.
		$tmp_players = array();
		foreach ($players as $p) {
			if (!$DETAILED && ($p->is_dead || $p->is_sold)) {
				continue;
			}
			array_push($tmp_players, $p);
		}
		$players = $tmp_players;
		return array($players, $players_org);
	}

	private function _roster($ALLOW_EDIT, $DETAILED, $players) {
		global $rules, $settings, $lng, $skillididx, $coach, $DEA;
		$team = $this; // Copy. Used instead of $this for readability.

		/******************************
		 *   Make the players ready for roster printing.
		 ******************************/
		foreach ($players as $p) {
			/*
				Misc
			*/
			$p->name = preg_replace('/\s/', '&nbsp;', $p->name);
			$p->position = preg_replace('/\s/', '&nbsp;', $p->position);
			$p->info = '<i class="icon-info"></i>';
			$p->team_id = $team->team_id;
			if ($team->format == 'SV') {
				$p->mv_mvp = 0;
			}
			/*
				Colors
			*/
			// Fictive player color fields used for creating player table.
			$p->HTMLfcolor = '#000000';
			$p->HTMLbcolor = COLOR_HTML_NORMAL;
			if     ($p->is_sold && $DETAILED)   $p->HTMLbcolor = COLOR_HTML_SOLD; # Sold has highest priority.
			elseif ($p->is_dead && $DETAILED)   $p->HTMLbcolor = COLOR_HTML_DEAD;
			elseif ($p->is_mng)                 $p->HTMLbcolor = COLOR_HTML_MNG;
			elseif ($p->is_retired)             $p->HTMLbcolor = COLOR_HTML_RETIRED;
			elseif ($p->is_journeyman_used)     $p->HTMLbcolor = COLOR_HTML_JOURNEY_USED;
			elseif ($p->is_journeyman)          $p->HTMLbcolor = COLOR_HTML_JOURNEY;
			elseif ($p->mayHaveNewSkill())      $p->HTMLbcolor = COLOR_HTML_NEWSKILL;
			elseif ($DETAILED)                  $p->HTMLbcolor = COLOR_HTML_READY;
			if ($p->is_captain)  {
				if (strlen($p->getSkillsStr(true)) == 0 ) {				
					if (strlen($p->getHatredStr(true)) == 0 ) {
						$p->skills   = '<small><i>'.($team->format == 'SV' ? 'Veteran' : 'Pro (Captain)').'</i></small>';
					} else {
						$p->skills   = '<small><i>'.($team->format == 'SV' ? 'Veteran' : 'Pro (Captain)').', Hatred ('.$p->getHatredStr(true).')</i></small>';
					}
				} else {				
					if (strlen($p->getHatredStr(true)) == 0 ) {
						$p->skills   = '<small><i>'.($team->format == 'SV' ? 'Veteran' : 'Pro (Captain)').', </i>'.$p->getSkillsStr(true).'</small>';
					} else {
						$p->skills   = '<small><i>'.($team->format == 'SV' ? 'Veteran' : 'Pro (Captain)').', </i>'.$p->getSkillsStr(true).'<i> , Hatred ('.$p->getHatredStr(true).')</i></small>';
					}					
				}
			} else {
				if (strlen($p->getSkillsStr(true)) == 0 ) {
					if (strlen($p->getHatredStr(true)) == 0 ) {
						$p->skills   = '';
					} else {
						$p->skills   = '<small><i>Hatred ('.$p->getHatredStr(true).')</i></small>';
					}
				} else {				
					if (strlen($p->getHatredStr(true)) == 0 ) {
						$p->skills   = '<small>'.$p->getSkillsStr(true).'</small>';
					} else {
						$p->skills   = '<small>'.$p->getSkillsStr(true).'<i> , Hatred ('.$p->getHatredStr(true).')</i></small>';
					}
				}			
			}
			$p->keywords   = '<small>'.$p->getKeywordsStr(true).'</small>';
			$p->injs     = $p->getInjsStr(true);
			$p->position = "<div class='tableResponsive'><table style='border-spacing:0px;'><tr><td><img align='left' src='$p->icon' alt='player avatar'></td><td>".$lng->getTrn("position/".strtolower($lng->FilterPosition($p->position)))."</td></tr></table></div>";
			if ($DETAILED) {
				$p->mv_cas = "$p->mv_bh/$p->mv_si/$p->mv_ki";
				$p->mv_spp = "$p->mv_spp/$p->extra_spp";
			}
			// Characteristic's colors
			foreach (array('ma', 'ag', 'pa', 'av', 'st') as $chr) {
				$sub = $p->$chr - $p->{"def_$chr"};
				$defchr = $p->{"def_$chr"};
				if  ($chr == 'ma' || $chr == 'av' || $chr == 'st' ) {
					if ($sub == 0) {
						// Nothing!
					}
					elseif ($sub == 1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_EQP1;
					elseif ($sub > 1)   $p->{"${chr}_color"} = COLOR_HTML_CHR_GTP1;
					elseif ($sub == -1) $p->{"${chr}_color"} = COLOR_HTML_CHR_EQM1;
					elseif ($sub < -1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_LTM1;
					if ($p->$chr != $p->{"${chr}_ua"}) {
						$p->{"${chr}_color"} = COLOR_HTML_CHR_BROKENLIMIT;
						$p->$chr = $p->{$chr.'_ua'}.' <i>('.$p->$chr.' eff.)</i>';
					}
				}
				else {
					if ($defchr > 0) {
						if ($sub == 0) {
							// Nothing!
						}
						elseif ($sub == 1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_EQM1;
						elseif ($sub > 1)   $p->{"${chr}_color"} = COLOR_HTML_CHR_LTM1;
						elseif ($sub == -1) $p->{"${chr}_color"} = COLOR_HTML_CHR_EQP1;
						elseif ($sub < -1)  $p->{"${chr}_color"} = COLOR_HTML_CHR_GTP1;
						if ($p->$chr != $p->{"${chr}_ua"}) {
							$p->{"${chr}_color"} = COLOR_HTML_CHR_BROKENLIMIT;
							$p->$chr = $p->{$chr.'_ua'}.' <i>('.$p->$chr.' eff.)</i>';
						}	
					}
					else {
						if ($sub == 0) {
							// Nothing!
						}
						elseif ($sub == 7)  $p->{"${chr}_color"} = COLOR_HTML_CHR_EQM1;
						elseif ($sub > 7)   $p->{"${chr}_color"} = COLOR_HTML_CHR_LTM1;
						elseif ($sub == 6) $p->{"${chr}_color"} = COLOR_HTML_CHR_EQP1;
						elseif ($sub < 6)  $p->{"${chr}_color"} = COLOR_HTML_CHR_GTP1;
						if ($p->$chr != $p->{"${chr}_ua"}) {
							$p->{"${chr}_color"} = COLOR_HTML_CHR_BROKENLIMIT;
							$p->$chr = $p->{$chr.'_ua'}.' <i>(5 eff.)</i>';
						}	
					}
				}
			}
			/*
				New skills drop-down.
			*/
			$x = '';
			if ($ALLOW_EDIT && $p->mayHaveNewSkill()) {
				// Check if player has enough SPP for a chosen skill (not just random)
				$numSkills = $p->numberOfAchSkill();
				$chosenPrimaryCosts = array(0 => 6, 1 => 8, 2 => 12, 3 => 16, 4 => 20, 5 => 30);
				$hasEnoughForChosen = ($p->mv_spp >= $chosenPrimaryCosts[$numSkills]);
				$isSevens = ($team->format == 'SV');
				
				// If random skills are manual entry only and player doesn't have enough for chosen skill
				// (Sevens always goes straight to the full dropdown - there is no "wait for enough for chosen" state)
				if (!$isSevens && $rules['randomskillmanualentry'] == 1 && !$hasEnoughForChosen) {
					$x .= "<BR><small>&nbsp;&nbsp;&nbsp;<u>Random skill available, see Team management box below</u></small>";
				} else {
					// Get player's existing skills for filtering (both default and earned)
					$playerExistingSkills = array();
					
					// Get all skills as a string and convert to array
					$allSkillsStr = $p->getSkillsStr(false);
					if (!empty($allSkillsStr)) {
						// Skills are likely comma-separated, split them
						$skillsArray = explode(',', $allSkillsStr);
						foreach ($skillsArray as $skillName) {
							$skillName = trim($skillName);
							if (!empty($skillName)) {
								$playerExistingSkills[] = $skillName;
							}
						}
					}
					// Also include default/positional skills (e.g. Secret Weapon, Chainsaw)
					foreach ($DEA[$team->f_rname]['players'] as $posName => $posDetails) {
						if ($posDetails['pos_id'] == $p->f_pos_id) {
							if (isset($posDetails['def'])) {
								foreach ($posDetails['def'] as $skillId) {
									if (isset($skillididx[$skillId])) {
										$skillName = $skillididx[$skillId];
										if (!in_array($skillName, $playerExistingSkills)) {
											$playerExistingSkills[] = $skillName;
										}
									}
								}
							}
							break;
						}
					}
					
					// Skill incompatibility rules
					$incompatibleSkills = array(
						'Ball & Chain' => array('Diving Tackle', 'Eye Gouge', 'Frenzy', 'Grab', 'Hit and Run', 'Leap', 'Multiple Block', 'On the Ball', 'Shadowing', 'Steady Footing'),
						'Hit and Run' => array('Frenzy'),
						'Frenzy' => array('Grab', 'Hit and Run', 'Multiple Block'),
						'Grab' => array('Frenzy'),
						'Multiple Block' => array('Frenzy'),
						'Leap' => array('Pogo'),
						'Pogo' => array('Leap')
					);
					
					// Skill prerequisites (note: Throw Team-Mate is Extraordinary and can only be a default skill)
					$skillPrerequisites = array(
						'Bullseye' => array('Throw Team-Mate'),
						'Strong Arm' => array('Throw Team-Mate'),
						'Lethal Flight' => array('Right Stuff'),
						'Violent Innovator' => array('Bombardier', 'Ball & Chain', 'Breathe Fire', 'Chainsaw', 'Stab', 'Projectile Vomit'),
						'Saboteur' => array('Secret Weapon')						
					);
					
					// Function to check if skill is compatible
					$isSkillAllowed = function($skillId) use ($playerExistingSkills, $incompatibleSkills, $skillPrerequisites, $skillididx) {
						$skillName = $skillididx[$skillId];
						
						// Don't show skills player already has
						if (in_array($skillName, $playerExistingSkills)) {
							return false;
						}
						
						// Check incompatibility rules
						foreach ($incompatibleSkills as $baseSkill => $incompatibleList) {
							if (in_array($baseSkill, $playerExistingSkills) && in_array($skillName, $incompatibleList)) {
								return false;
							}
							if ($skillName === $baseSkill) {
								foreach ($incompatibleList as $incompatible) {
									if (in_array($incompatible, $playerExistingSkills)) {
										return false;
									}
								}
							}
						}
						
						// Check prerequisite rules
						if (isset($skillPrerequisites[$skillName])) {
							$hasPrerequisite = false;
							foreach ($skillPrerequisites[$skillName] as $prereq) {
								if (in_array($prereq, $playerExistingSkills)) {
									$hasPrerequisite = true;
									break;
								}
							}
							if (!$hasPrerequisite) {
								return false;
							}
						}
						
						return true;
					};
					
					// Function to group skills by category and sort
					$groupAndSortSkills = function($skillIds) use ($isSkillAllowed, $skillididx) {
						$categorizedSkills = array(
							'G' => array(), // General
							'A' => array(), // Agility
							'S' => array(), // Strength
							'P' => array(), // Passing
							'D' => array(), // Devious
							'M' => array()  // Mutation
						);
						
						foreach ($skillIds as $skillId) {
							if ($isSkillAllowed($skillId)) {
								// Get skill category from database
								$skillCat = get_alt_col('game_data_skills', 'skill_id', $skillId, 'cat');
								if ($skillCat && isset($categorizedSkills[$skillCat])) {
									$categorizedSkills[$skillCat][$skillId] = $skillididx[$skillId];
								}
							}
						}
						
						// Sort each category alphabetically
						foreach ($categorizedSkills as $cat => $skills) {
							asort($categorizedSkills[$cat]);
						}
						
						return $categorizedSkills;
					};
					
					$x .= "<form method='POST'>\n";
					$x .= "<select name='skill'>\n";
					$x .= "<option selected value='999'>-- Select Skill --</option>\n";
					
					// Primary skills - grouped by category and sorted
					$primarySkillsByCat = $groupAndSortSkills($p->choosable_skills['norm']);
					
					$categoryNames = array(
						'G' => 'General',
						'A' => 'Agility',
						'S' => 'Strength',
						'P' => 'Passing',
						'D' => 'Devious',
						'M' => 'Mutation'
					);
					
					$hasPrimarySkills = false;
					foreach ($primarySkillsByCat as $cat => $skills) {
						if (!empty($skills)) {
							$hasPrimarySkills = true;
							break;
						}
					}
					
					if ($hasPrimarySkills) {
						$x .= "<optgroup label='Primary skills'>\n";
						foreach ($primarySkillsByCat as $cat => $skills) {
							if (!empty($skills)) {
								$x .= "<optgroup label='&nbsp;&nbsp;" . $categoryNames[$cat] . "'>\n";
								foreach ($skills as $skillId => $skillName) {
									// Mark Elite skills with asterisks
									$eliteSkills = array('Block', 'Dodge', 'Guard', 'Mighty Blow');
									$displayName = in_array($skillName, $eliteSkills) ? $skillName . ' *' : $skillName;
									$x .= "<option value='$skillId'>&nbsp;&nbsp;&nbsp;&nbsp;$displayName</option>\n";
								}
								$x .= "</optgroup>\n";
							}
						}
						$x .= "</optgroup>\n";
					}
					
					// Secondary skills - grouped by category and sorted
					if (($p->numberOfAchSkill() == 0 && $p->mv_spp >= 10) || ($p->numberOfAchSkill() == 1 && $p->mv_spp >= 12) || ($p->numberOfAchSkill() == 2 && $p->mv_spp >= 16) || ($p->numberOfAchSkill() == 3 && $p->mv_spp >= 20) || ($p->numberOfAchSkill() == 4 && $p->mv_spp >= 24) || ($p->numberOfAchSkill() == 5 && $p->mv_spp >= 34)) { 
						$secondarySkillsByCat = $groupAndSortSkills($p->choosable_skills['doub']);
						
						$hasSecondarySkills = false;
						foreach ($secondarySkillsByCat as $cat => $skills) {
							if (!empty($skills)) {
								$hasSecondarySkills = true;
								break;
							}
						}
						
						if ($hasSecondarySkills) {
							$x .= "<optgroup label='Secondary skills'>\n";
							foreach ($secondarySkillsByCat as $cat => $skills) {
								if (!empty($skills)) {
									$x .= "<optgroup label='&nbsp;&nbsp;" . $categoryNames[$cat] . "'>\n";
									foreach ($skills as $skillId => $skillName) {
										// Mark Elite skills with asterisks
										$eliteSkills = array('Block', 'Dodge', 'Guard', 'Mighty Blow');
										$displayName = in_array($skillName, $eliteSkills) ? $skillName . ' *' : $skillName;
										$x .= "<option value='$skillId'>&nbsp;&nbsp;&nbsp;&nbsp;$displayName</option>\n";
									}
									$x .= "</optgroup>\n";
								}
							}
							$x .= "</optgroup>\n";
						}
					}
					
					// Characteristic improvements — only show if random stat management box is disabled
					if ($rules['randomstatrolls'] == 1) {
						if (($p->numberOfAchSkill() == 0 && $p->mv_spp >= 14) || ($p->numberOfAchSkill() == 1 && $p->mv_spp >= 16) || ($p->numberOfAchSkill() == 2 && $p->mv_spp >= 20) || ($p->numberOfAchSkill() == 3 && $p->mv_spp >= 24) || ($p->numberOfAchSkill() == 4 && $p->mv_spp >= 28) || ($p->numberOfAchSkill() == 5 && $p->mv_spp >= 38)) {
							$x .= "<optgroup label='Characteristic improvement'>\n";
							foreach ($p->choosable_skills['chr'] as $s) {
								global $CHR_CONV;
								if ($CHR_CONV[$s] == 'ma' || $CHR_CONV[$s] == 'av' || $CHR_CONV[$s] == 'st') {
									$x .= "<option value='ach_$s'>+ ".ucfirst($CHR_CONV[$s])."</option>\n";
								} else {
									$x .= "<option value='ach_$s'>- ".ucfirst($CHR_CONV[$s])."</option>\n";	
								}
							}
							$x .= "</optgroup>\n";
						}
					}
					$x .= "</select>\n";
					$x .= "<select name='skillcost'>\n";
					$x .= "<option selected value='99'>-- Select Skill Cost --</option>\n";

					$costs = self::_sppCostTable();
					$n = min($p->numberOfAchSkill(), 5);
					$c = $costs[$n];

					if (!$isSevens && $rules['randomskillmanualentry'] != 1) {
						$x .= "<option value='{$c[0]}|R'>{$c[0]} SPP (Random Primary)</option>\n";
					}
					if ($p->mv_spp >= $c[1]) {
						$x .= "<option value='{$c[1]}|P'>{$c[1]} SPP (".($isSevens ? 'Primary Skill' : 'Chosen Primary').")</option>\n";
					}
					if ($p->mv_spp >= $c[2]) {
						$x .= "<option value='{$c[2]}|S'>{$c[2]} SPP (".($isSevens ? 'Secondary Skill' : 'Chosen Secondary').")</option>\n";
					}
					if ($p->mv_spp >= $c[3] && $rules['randomstatrolls'] == 1) {
						$x .= "<option value='{$c[3]}|X'>{$c[3]} SPP (Random Stat Improvement)</option>\n";
						$x .= "<option value='{$c[3]}|S'>{$c[3]} SPP (Chosen Skill instead of Rolled Stat)</option>\n";
					}
					$x .= "</select>\n";
					$x .= "<input type='submit' name='button' value='OK' onClick=\"if(!confirm('".$lng->getTrn('common/confirm_box')."')){return false;}\">\n";
					$x .= "<input type='hidden' name='type' value='skill'>\n";
					$x .= "<input type='hidden' name='player' value='".$p->player_id."'>\n";
					$x .= "</form>\n";
					
					// Show random skill message below the dropdown if randomskillmanualentry is enabled and also checks if randomstat is enabled
					if ($rules['randomskillmanualentry'] == 1) {
						$hasEnoughForStat = ($p->mv_spp >= $c[3]);
						$showStat = ($hasEnoughForStat && $rules['randomstatrolls'] != 1);
						if ($showStat) {
							$x .= "<BR><small>&nbsp;&nbsp;&nbsp;<u>Random skill or stat increase available, see Team management box below</u></small>";
						} else {
							$x .= "<BR><small>&nbsp;&nbsp;&nbsp;<u>Random skill available, see Team management box below</u></small>";
						}
					}
					
					$x .= '</td>';
				}
			}	
			$p->skills .= $x;
			if ($p->pa == 0 || $p->pa >6) {       
				$p->pa = '-';
			}
			else {       
				$p->pa = $p->pa.'+';
			}
		}

		/* If enabled add stars and summed mercenaries entries to the roster */
		if ($DETAILED) {
			$stars = array();
			foreach (Star::getStars(STATS_TEAM, $team->team_id, false, false) as $s) {
				$s->name = preg_replace('/\s/', '&nbsp;', $s->name);
				$s->info = '<i class="icon-info"></i>';
				$s->player_id = $s->star_id;
				$s->team_id = $team->team_id;
				$s->nr = 0;
				$s->position = "<div class='tableResponsive'><table style='border-spacing:0px;'><tr><td><i>Star&nbsp;player</i></td></tr></table></div>";
				$s->setSkills(true);
				$s->skills = '<small>'.$s->skills.'</small>';
				$s->injs = '';
				$s->value = 0;
				$s->mv_cas = "$s->mv_bh/$s->mv_si/$s->mv_ki";
				foreach ($s->getStats(T_OBJ_TEAM,$team->team_id) as $k => $v) {
					$s->$k = $v;
				}
				$s->is_dead = $s->is_sold = $s->is_mng = $s->is_journeyman = $s->is_retired = false;
				$s->keywords = '';
				$s->HTMLbcolor = COLOR_HTML_STARMERC;
				$s->href = array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'player_id'); # Like in below $fields def, but with T_OBJ_STAR instead.
				array_push($stars, $s);
			}
			$players = array_merge($players, $stars);

			$smerc = (object) null;
			$smerc->mv_mvp = $smerc->mv_td = $smerc->mv_cp = $smerc->mv_intcpt = $smerc->mv_bh = $smerc->mv_si = $smerc->mv_ki = $smerc->skills = 0;
			foreach (Mercenary::getMercsHiredByTeam($team->team_id) as $merc) {
				$smerc->mv_mvp += $merc->mvp;
				$smerc->mv_td += $merc->td;
				$smerc->mv_cp += $merc->cp;
				$smerc->mv_intcpt += $merc->intcpt;
				$smerc->mv_bh += $merc->bh;
				$smerc->mv_si += $merc->si;
				$smerc->mv_ki += $merc->ki;
				$smerc->skills += $merc->skills;
			}
			$smerc->player_id = ID_MERCS;
			$smerc->team_id = $team->team_id;
			$smerc->nr = 0;
			$smerc->name = 'All&nbsp;mercenary&nbsp;hirings';
			$smerc->info = '<i class="icon-info"></i>';
			$smerc->position = "<i>Mercenaries</i>";
			$smerc->mv_cas = "$smerc->mv_bh/$smerc->mv_si/$smerc->mv_ki";
			$smerc->ma = '-';
			$smerc->st = '-';
			$smerc->ag = '-';
			$smerc->pa = '-';
			$smerc->av = '-';
			$smerc->skills = 'Total bought extra skills: '.$smerc->skills;
			$smerc->injs = '';
			$smerc->mv_spp = '-';
			$smerc->mv_misc = '-';
			$smerc->value = 0;
			$smerc->is_dead = $smerc->is_sold = $smerc->is_mng = $smerc->is_journeyman = $smerc->is_retired = false;
			$smerc->keywords = '';
			$smerc->HTMLbcolor = COLOR_HTML_STARMERC;
			array_push($players, $smerc);
		}
		/******************************
		 * Team players table
		 * ------------------
		 * Contains player information and menu(s) for skill choice.
		 ******************************/
		title($team->name . (($team->is_retired) ? ' <font color="red"> (Retired)</font>' : ''));
		$allowEdit = (isset($coach) && $coach)
			? $coach->isMyTeam($team->team_id) || $coach->mayManageObj(T_OBJ_TEAM, $team->team_id)
			: false;
		$fields = array(
			'nr'        => array('desc' => '#', 'editable' => 'updatePlayerNumber', 'javaScriptArgs' => array('team_id', 'player_id'), 'editableClass' => 'number', 'allowEdit' => $allowEdit),
			'name'      => array('desc' => $lng->getTrn('common/name'), 'editable' => 'updatePlayerName', 'javaScriptArgs' => array('team_id', 'player_id'), 'allowEdit' => $allowEdit),
			'info'      => array('desc' => '', 'nosort' => true, 'icon' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,false,false,false), 'field' => 'obj_id', 'value' => 'player_id')),
			'position'  => array('desc' => $lng->getTrn('common/pos'), 'nosort' => true),
			'keywords'  => array('desc' => $lng->getTrn('common/keywords'), 'nosort' => true),
			'ma'        => array('desc' => 'Ma'),
			'st'        => array('desc' => 'St'),
			'ag'        => array('desc' => 'Ag', 'suffix' => '+'),
			'pa'        => array('desc' => 'Pa'),	
			'av'        => array('desc' => 'Av', 'suffix' => '+'),
			'skills'    => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'injs'      => array('desc' => $lng->getTrn('common/injs'), 'nosort' => true),
			'mv_cp'     => array('desc' => 'Cp'),
			'mv_td'     => array('desc' => 'Td'),
			'mv_intcpt' => array('desc' => 'Int'),
			'mv_cas'    => array('desc' => ($DETAILED) ? 'BH/SI/Ki' : 'Cas', 'nosort' => ($DETAILED) ? true : false),
			'mv_mvp'    => array('desc' => 'MVP'),	
			'mv_misc'   => array('desc' => 'Misc'),
			'mv_spp'    => array('desc' => ($DETAILED) ? 'SPP/extra' : 'SPP', 'nosort' => ($DETAILED) ? true : false),
			'value'     => array('desc' => $lng->getTrn('common/value'), 'kilo' => true, 'suffix' => 'k'),
		);
		echo "<a href=".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$this->team_id,false,false)."&amp;detailed=".(($DETAILED) ? 0 : 1).">".$lng->getTrn('profile/team/viewtoggle')."</a><br><br>\n";
		HTMLOUT::sort_table(
			$team->name.' roster',
			urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).(($DETAILED) ? '&amp;detailed=1' : '&amp;detailed=0'),
			$players,
			$fields,
			($DETAILED) ? array('+is_dead', '+is_sold', '+is_mng', '+is_retired', '+is_journeyman', '+nr', '+name') : sort_rule('player'),
			(isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
			array('color' => ($DETAILED) ? true : false, 'doNr' => false, 'noHelp' => true)
		);
		?>
		<!-- Following HTML is from class_team_htmlout.php _roster -->
		<div class='tableResponsive'>
		<table class="text">
			<tr>
				<td style="width: 100%;"> </td>
				<?php
				if ($DETAILED) {
					?>
					<td style="background-color: <?php echo COLOR_HTML_READY;   ?>;"><font color='black'><b>&nbsp;Ready&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_MNG;     ?>;"><font color='black'><b>&nbsp;MNG&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_RETIRED;     ?>;"><font color='black'><b>&nbsp;Retired&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_JOURNEY; ?>;"><font color='black'><b>&nbsp;Journey&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_JOURNEY_USED; ?>;"><font color='black'><b>&nbsp;Used&nbsp;journey&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_DEAD;    ?>;"><font color='black'><b>&nbsp;Dead&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_SOLD;    ?>;"><font color='black'><b>&nbsp;Sold&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_STARMERC;?>;"><font color='black'><b>&nbsp;Star/merc&nbsp;</b></font></td>
					<td style="background-color: <?php echo COLOR_HTML_NEWSKILL;?>;"><font color='black'><b>&nbsp;New&nbsp;skill&nbsp;</b></font></td>
					<?php
				}
				?>
			</tr>
		</table>
		</div>
		<?php
	}

	private function _menu($ALLOW_EDIT, $DETAILED) {
		global $lng, $settings, $rules;
		$team = $this; // Copy. Used instead of $this for readability.
		$url = urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$this->team_id,false,false);
		?>
		<!-- Following HTML is from class_team_htmlout.php _menu -->
		<!-- Cyanide module team color style -->
		<style type="text/css">
		#cycolors {
		width: 128px;
		height: 112px;
		position: relative;
		background-image: url(images/cycolors.jpg);
		background-repeat: no-repeat;
		}

		#cycolors ul {
		margin: 0;
		padding: 0;
		padding-bottom:0px;padding-left:0px;padding-right:0px;padding-top:0px;
		list-style: none;
		}

		#cycolors a {
		position: absolute;
		width: 15px;
		height: 15px;
		text-indent: -1000em;
		padding-bottom:0px;padding-left:0px;padding-right:0px;padding-top:0px;
		border-bottom:0px;border-left:0px;border-top:0px;border-right:0px;
		}

		#cycolors a:hover { border: 1px solid #fff; }

		#cycolors .blue1 a {top: 0px;left: 0px;}
		#cycolors .cyan1 a {top: 0px;left: 16px;}
		#cycolors .green1 a {top: 0px;left: 32px;}
		#cycolors .yellow1 a {top: 0px;left: 48px;}
		#cycolors .red1 a {top: 0px;left: 64px;}
		#cycolors .magenta1 a {top: 0px;left: 80px;}
		#cycolors .purple1 a {top: 0px;left: 96px;}
		#cycolors .grey1 a {top: 0px;left: 112px;}

		#cycolors .blue2 a {top: 16px;left: 0px;}
		#cycolors .cyan2 a {top: 16px;left: 16px;}
		#cycolors .green2 a {top: 16px;left: 32px;}
		#cycolors .yellow2 a {top: 16px;left: 48px;}
		#cycolors .red2 a {top: 16px;left: 64px;}
		#cycolors .magenta2 a {top: 16px;left: 80px;}
		#cycolors .purple2 a {top: 16px;left: 96px;}
		#cycolors .grey2 a {top: 16px;left: 112px;}

		#cycolors .blue3 a {top: 32px;left: 0px;}
		#cycolors .cyan3 a {top: 32px;left: 16px;}
		#cycolors .green3 a {top: 32px;left: 32px;}
		#cycolors .yellow3 a {top: 32px;left: 48px;}
		#cycolors .red3 a {top: 32px;left: 64px;}
		#cycolors .magenta3 a {top: 32px;left: 80px;}
		#cycolors .purple3 a {top: 32px;left: 96px;}
		#cycolors .grey3 a {top: 32px;left: 112px;}

		#cycolors .blue4 a {top:48px;left: 0px;}
		#cycolors .cyan4 a {top:48px;left: 16px;}
		#cycolors .green4 a {top:48px;left: 32px;}
		#cycolors .yellow4 a {top:48px;left: 48px;}
		#cycolors .red4 a {top:48px;left: 64px;}
		#cycolors .magenta4 a {top:48px;left: 80px;}
		#cycolors .purple4 a {top:48px;left: 96px;}
		#cycolors .grey4 a {top:48px;left: 112px;}

		#cycolors .blue5 a {top:64px;left: 0px;}
		#cycolors .cyan5 a {top:64px;left: 16px;}
		#cycolors .green5 a {top:64px;left: 32px;}
		#cycolors .yellow5 a {top:64px;left: 48px;}
		#cycolors .red5 a {top:64px;left: 64px;}
		#cycolors .magenta5 a {top:64px;left: 80px;}
		#cycolors .purple5 a {top:64px;left: 96px;}
		#cycolors .grey5 a {top:64px;left: 112px;}

		#cycolors .blue6 a {top:80px;left: 0px;}
		#cycolors .cyan6 a {top:80px;left: 16px;}
		#cycolors .green6 a {top:80px;left: 32px;}
		#cycolors .yellow6 a {top:80px;left: 48px;}
		#cycolors .red6 a {top:80px;left: 64px;}
		#cycolors .magenta6 a {top:80px;left: 80px;}
		#cycolors .purple6 a {top:80px;left: 96px;}
		#cycolors .grey6 a {top:80px;left: 112px;}

		#cycolors .blue7 a {top:96px;left: 0px;}
		#cycolors .cyan7 a {top:96px;left: 16px;}
		#cycolors .green7 a {top:96px;left: 32px;}
		#cycolors .yellow7 a {top:96px;left: 48px;}
		#cycolors .red7 a {top:96px;left: 64px;}
		#cycolors .magenta7 a {top:96px;left: 80px;}
		#cycolors .purple7 a {top:96px;left: 96px;}
		#cycolors .grey7 a {top:96px;left: 112px;}
		</style>

		<ul class="rosterMenu" style="position:static; z-index:0;">
			<li><a href="<?php echo $url.'&amp;subsec=man';?>"><?php echo $lng->getTrn('profile/team/tmanage');?></a></li>
			<li><a href="<?php echo $url.'&amp;subsec=news';?>"><?php echo $lng->getTrn('profile/team/news');?></a></li>
			<li><a href="<?php echo $url.'&amp;subsec=about';?>"><?php echo $lng->getTrn('common/about');?></a></li>
			<li><a href="<?php echo $url.'&amp;subsec=games';?>"><?php echo $lng->getTrn('profile/team/games');?></a></li>
			<?php
			echo "<li><a href='${url}&amp;subsec=hhstar'>".$lng->getTrn('common/starhh')."</a></li>\n";
			echo "<li><a href='${url}&amp;subsec=hhmerc'>".$lng->getTrn('common/merchh')."</a></li>\n";
			
			$pdf    = (Module::isRegistered('PDFroster')) ? "handler.php?type=roster&amp;team_id=$this->team_id&amp;detailed=".($DETAILED ? '1' : '0') : '';
			$botocs = (Module::isRegistered('XML_BOTOCS') && $settings['leegmgr_botocs']) ? "handler.php?type=botocsxml&amp;teamid=$this->team_id" : '';
			$cyanide = (Module::isRegistered('XML_BOTOCS') && $settings['leegmgr_cyanide']) ? "handler.php?type=botocsxml&amp;teamid=$this->team_id&amp;cy" : '';
			if ($pdf || $botocs) {
			?>
			
			<?php
			}
			if (Module::isRegistered('IndcPage')) {
				echo "<li><a href='handler.php?type=inducements&amp;team_id=$team->team_id'>Inducements try-out</a></li>\n";
			}
			if (Module::isRegistered('SGraph')) {
				echo "<li><a href='handler.php?type=graph&amp;gtype=".SG_T_TEAM."&amp;id=$team->team_id''>Vis. stats</a></li>\n";
			}
			if (Module::isRegistered('Cemetery')) {
				echo "<li><a href='handler.php?type=cemetery&amp;tid=$team->team_id'>".$lng->getTrn('name', 'Cemetery')."</a></li>\n";
			}
			?>
			<?php if ($pdf)    { ?><li class="subfirst"><a TARGET="_blank" href="<?php echo $pdf;?>">PDF</a></li> <?php } ?>
		</ul>
		<br>
		<?php
	}

	private function _HHMerc($DETAILED)	{
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('common/merchh') . '</div>');
		$mdat = array();
		foreach (Mercenary::getMercsHiredByTeam($team->team_id, false) as $merc) {
			$o = (object) array();
			$m = new Match($merc->match_id);
			$o->date_played = $m->date_played;
			$o->opponent = ($m->team1_id == $team->team_id) ? $m->team1_name : $m->team2_name;
			foreach (array('match_id', 'skills', 'misc', 'cp', 'td', 'intcpt', 'bh', 'ki', 'si') as $f) {
				$o->$f = $merc->$f;
			}
			$o->cas = $o->bh+$o->ki+$o->si;
			$o->match = '[view]';
			$o->tour = get_alt_col('tours', 'tour_id', $m->f_tour_id, 'name');
			$o->score = "$m->team1_score - $m->team2_score";
			$o->result = matchresult_icon(
				(
				($m->team1_id == $team->team_id && $m->team1_score > $m->team2_score) ||
				($m->team2_id == $team->team_id && $m->team1_score < $m->team2_score)
				)
					? 'W'
					: (($m->team1_score == $m->team2_score) ? 'D' : 'L')
			);

			array_push($mdat, $o);
		}
		$fields = array(
			'date_played'   => array('desc' => $lng->getTrn('common/dateplayed')),
			'tour'          => array('desc' => $lng->getTrn('common/tournament')),
			'opponent'      => array('desc' => $lng->getTrn('common/opponent')),
			'skills' => array('desc' => $lng->getTrn('common/skills')),
			'cp'     => array('desc' => 'Cp'),
			'td'     => array('desc' => 'Td'),
			'intcpt' => array('desc' => 'Int'),
			'cas'    => array('desc' => 'Cas'),
			'bh'     => array('desc' => 'BH'),
			'si'     => array('desc' => 'Si'),
			'ki'     => array('desc' => 'Ki'),
			'misc'    => array('desc' => 'Misc SPP'),
			'score'  => array('desc' => $lng->getTrn('common/score'), 'nosort' => true),
			'result' => array('desc' => $lng->getTrn('common/result'), 'nosort' => true),
			'match'  => array('desc' => $lng->getTrn('common/match'), 'href' => array('link' => 'index.php?section=matches&amp;type=report', 'field' => 'mid', 'value' => 'match_id'), 'nosort' => true),
		);
		HTMLOUT::sort_table(
			"<a name='tp_mhhanc'>".$lng->getTrn('common/merchh')."</a>",
			urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=hhmerc'.(($DETAILED) ? '&amp;detailed=1' : '&amp;detailed=0'),
			$mdat,
			$fields,
			sort_rule('star_HH'),
			(isset($_GET['sorttp_mhh'])) ? array((($_GET['dirtp_mhh'] == 'a') ? '+' : '-') . $_GET['sorttp_mhh']) : array(),
			array('GETsuffix' => 'tp_mhh', 'doNr' => false,)
		);
	}

	private function _HHStar($DETAILED) {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('common/starhh') . '</div>');
		Star_HTMLOUT::starHireHistory(STATS_TEAM, $team->team_id, false, false, false, array(
			'url' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=hhstar'.(($DETAILED) ? '&amp;detailed=1' : '&amp;detailed=0'),
			'GET_SS' => 'tp_shh',)
		);
	}

	public static function teamManagementBox($teamId) {
		$team = new self($teamId);
		$ALLOW_EDIT = $team->allowEdit(); # Show team action boxes?
		$DETAILED = (isset($_GET['detailed']) && $_GET['detailed'] == 1);# Detailed roster view?
		$team->handleActions($ALLOW_EDIT); # Handles any actions/request sent.
		list($players, $players_backup) = $team->_loadPlayers($DETAILED); # Should come after handleActions().
		$team->_teamManagementBox($players, $team);
	}

	private function _actionBoxes($ALLOW_EDIT, $players) {
		/******************************
		 * Team management
		 * ---------------
		 * Here we are able to view team stats and manage the team, depending on visitor's privileges.
		 ******************************/
		global $lng, $rules, $settings, $skillarray, $coach, $DEA, $CHR_CONV, $stars, $specialruleididx;
		global $leagues, $divisions;
		global $racesHasNecromancer, $racesNoApothecary;
		global $T_ALLOWED_PLAYER_NR;	
		$team = $this; // Copy. Used instead of $this for readability.	
		$raceid = $team->f_race_id;	
		$race = new Race($raceid);	
		// Set list of coaches that a team can be transfered to	(excludes current team coach)	
		$teamcoach = $team->owned_by_coach_id;	
		$queryGet = 'SELECT coaches.coach_id AS "coach_id", coaches.name AS "cname" FROM coaches WHERE coaches.retired IS FALSE and coaches.coach_id != '.$teamcoach.' ORDER BY cname ASC';
		$coaches = array();
		$result = mysql_query($queryGet);
		while ($c = mysql_fetch_object($result)) {
			$coaches[] = $c;
		}
		$JMP_ANC = (isset($_POST['menu_tmanage']) || isset($_POST['menu_admintools'])); # Jump condition MUST be set here due to _POST variables being changed later.
		?>
		<!-- Following HTML is from class_team_htmlout.php _actionBoxes -->
		<a name="aanc"></a>
		<div class="boxTeamPage">
			<div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn('profile/team/box_info/title');?></div>
			<div class="boxBody">
				<div class='tableResponsive'>
				<table width="100%">
					<tr>
						<td><?php echo $lng->getTrn('common/coach');?></td>
						<td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_COACH,$team->owned_by_coach_id,false,false);?>"><?php echo $team->f_cname; ?></a></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/race');?></td>
						<td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_RACE,$team->f_race_id,false,false);?>"><?php echo $lng->getTrn('race/'.strtolower(str_replace(' ','', $team->f_rname))); ?></a></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/format');?></td>
						<td><?php echo $lng->getTrn('common/'.strtolower($team->format)); ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/teamleagues');?></td>
						<td><?php echo leaguesTrans($race->team_league);?></td>
					</tr>
					<?php 
					if (strlen($team->getLeagueoptions()) >1) {
						echo '<tr><td>';
						echo $lng->getTrn('common/teamchosenleague');
						echo '</td><td>';
						if (strlen($team->getLeaguechosen()) >= 1) { 
							$fr_text = 'profile/team/box_tm/favrule/r'.$team->getLeaguechosen();
							echo $lng->getTrn($fr_text);
						} else {
							echo '<i>'.$lng->getTrn('common/notyetselected').'</i>';
						}
						echo '</td></tr>';
					}?>
					<tr>
						<td><?php echo $lng->getTrn('common/teamspecialrules');?></td>
						<td><?php if (strlen($team->getTeamrules()) >0) {
								echo specialsTrans($race->special_rules);
								} else {
										if ($team->f_rname == 'Norse' && $team->getFavrulechosen() == 15) {
										echo 'Favoured of Khorne';
										} else {
										echo 'none';
										}
								}								
							 ?></td>
					</tr>
					<?php 
					if (strlen($team->getFavruleoptions()) != 0) {
						echo '<tr><td>';
						echo $lng->getTrn('common/teamfavspecialrules');
						echo '</td><td>';
						if (strlen($team->getFavrulechosen()) >= 1) { 
							$fr_text = 'profile/team/box_tm/favrule/r'.$team->getFavrulechosen();
							echo $lng->getTrn($fr_text);
						} else {
							echo '<i>'.$lng->getTrn('common/notyetselected').'</i>';
						}
						echo '</td></tr>';
					}?>
					<tr>
						<td><?php echo $lng->getTrn('common/league');?></td>
						<td><?php if (isset($leagues[$team->f_lid])) {
							echo "<a href=\"";
							echo urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,T_NODE_LEAGUE,$team->f_lid);
							echo "\">" . $leagues[$team->f_lid]['lname'] . "</a>";
						} else {
							echo '<i>'.$lng->getTrn('common/none').'</i>';
						} ?></td>
					</tr>
					<?php
					if ($team->f_did != self::T_NO_DIVISION_TIE) {
						?>
						<tr>
							<td><?php echo $lng->getTrn('common/division');?></td>
							<td><?php if (isset($divisions[$team->f_did])) {
							echo "<a href=\"";
							echo urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,T_NODE_DIVISION,$team->f_did);
							echo "\">" . $divisions[$team->f_did]['dname'] . "</a>";
						} else {
							echo '<i>'.$lng->getTrn('common/none').'</i>';
						} ?></td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td><?php echo $lng->getTrn('common/ready');?></td>
						<td><?php echo ($team->rdy) ? $lng->getTrn('common/yes') : $lng->getTrn('common/no'); ?></td>
					</tr>
					<tr>
						<td>TV</td>
						<td><?php echo $team->tv/1000 . 'k'; ?></td>
					</tr>
					<tr>
						 <td><?php echo $lng->getTrn('matches/report/treas')?></td>
						<td><?php echo $team->treasury/1000 . 'k'; ?></td>
					</tr>
					<tr>
					<?php
					if (in_array($team->f_race_id, $racesHasNecromancer)) {
						?>
						<td>Necromancer</td>
						<td><?php echo $lng->getTrn('common/yes');?></td>
						<?php
					}
					if (!in_array($team->f_race_id, $racesNoApothecary)) {
						echo "<td>".$lng->getTrn('common/apothecary')."</td>\n";
						echo "<td>" . ($team->apothecary ? $lng->getTrn('common/yes') : $lng->getTrn('common/no')) . "</td>\n";
					}
					?>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/reroll')?></td>
						<td><?php echo $team->rerolls; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('matches/report/ff')?></td>
						<td><?php echo $team->rg_ff; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/ass_coach')?></td>
						<td><?php echo $team->ass_coaches; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/cheerleader')?></td>
						<td><?php echo $team->cheerleaders; ?></td>
					</tr>
					<tr>
						<td colspan=2><hr></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/played');?></td>
						<td><?php echo $team->mv_played; ?></td>
					</tr>
					<tr>
						<td>WIN%</td>
						<td><?php echo sprintf("%1.1f", $team->rg_win_pct).'%'; ?></td>
					</tr>
					<tr>
						<td>ELO</td>
						<td><?php echo (($team->rg_elo) ? sprintf("%1.2f", $team->rg_elo) : '<i>N/A</i>'); ?></td>
					</tr>
					<tr>
						<td>W/L/D</td>
						<td><?php echo "$team->mv_won/$team->mv_lost/$team->mv_draw"; ?></td>
					</tr>
					<tr>
						<td>W/L/D <?php echo $lng->getTrn('common/streaks');?></td>
						<td><?php echo "$team->rg_swon/$team->rg_slost/$team->rg_sdraw"; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('common/wontours');?></td>
						<td><?php echo $team->wt_cnt; ?></td>
					</tr>
					<tr>
						<td><?php echo $lng->getTrn('profile/team/box_info/ltour');?></td>
						<td><?php echo Tour::getTourUrl($team->getLatestTour()); ?></td>
					</tr>
					<tr valign="top">
						<td><?php echo $lng->getTrn('common/playedtours');?></td>
						<td><small><?php $tours = $team->getToursPlayedIn(false);
						if (empty($tours)) {
							echo $lng->getTrn('common/none');
						} else {
							$first = true;
							foreach($tours as $tour) {
								if ($first) {
									$first = false;
								} else {
									echo ", ";
								}
								echo $tour->getUrl();
							}
						} ?></small></td>
					</tr>
					<?php
					if (Module::isRegistered('Prize')) {
						?>
						<tr valign="top">
							<td><?php echo $lng->getTrn('name', 'Prize');?></td>
							<td><small><?php echo Module::run('Prize', array('getPrizesString', T_OBJ_TEAM, $team->team_id));?></small></td>
						</tr>
						<?php
					}
					if (Module::isRegistered('FamousTeams')) {
						?>
						<tr>
							<td><?php echo $lng->getTrn('isfamous', 'FamousTeams');?></td>
							<td><?php echo (Module::run('FamousTeams', array('isInFT', $team->team_id))) ? '<b><font color="green">Yes</font></b>' : 'No';?></td>
						</tr>
						<?php
					}
					?>
				</table>
				</div>
			</div>
		</div>

		<?php
		if ($ALLOW_EDIT) {
			$this->_teamManagementBox($players, $team);
			if ($coach->isNodeCommish(T_NODE_LEAGUE, $team->f_lid)) {
				?>
				<!-- Following HTML is from class_team_htmlout.php _actionBoxes -->
				<div class="boxTeamPage">
					<div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>"><?php echo $lng->getTrn('profile/team/box_admin/title');?></div>
					<div class="boxBody">
						<?php
						$base = 'profile/team';
						$admin_tools = array(
							'unhire_journeyman' => $lng->getTrn($base.'/box_admin/unhire_journeyman'),
							'unsell_player'     => $lng->getTrn($base.'/box_admin/unsell_player'),
							'unbuy_goods'       => $lng->getTrn($base.'/box_admin/unbuy_goods'),
							'bank'              => $lng->getTrn($base.'/box_admin/bank'),
							'spp'               => $lng->getTrn($base.'/box_admin/spp'),
							'dval'              => $lng->getTrn($base.'/box_admin/dval'),
							'extra_skills'      => $lng->getTrn($base.'/box_admin/extra_skills'),
							'ach_skills'        => $lng->getTrn($base.'/box_admin/ach_skills'),
							'manage_hatred' 	=> $lng->getTrn($base.'/box_admin/manageHatred'),
							'ff'                => $lng->getTrn($base.'/box_admin/ff'),
							'resetleague'       => $lng->getTrn($base.'/box_admin/resetleague'),
							'resetrule'         => $lng->getTrn($base.'/box_admin/resetrule'),
							'resetcaptain'      => $lng->getTrn($base.'/box_admin/resetcaptain'),
							'transferteam'      => $lng->getTrn($base.'/box_admin/transferteam'),
							'removeNiggle'      => $lng->getTrn($base.'/box_admin/removeNiggle'),
							'addniggle'      	=> $lng->getTrn($base.'/box_admin/addniggle'),
							'removeMNG'      	=> $lng->getTrn($base.'/box_admin/removeMNG'),
							'removenegastat'    => $lng->getTrn($base.'/box_admin/removenegastat'),
						);
						// Set default choice.
						if (!isset($_POST['menu_admintools'])) {
							reset($admin_tools);
							$_POST['menu_admintools'] = key($admin_tools);
						}
						// If action is already chosen, then make it the default selected.
						if (isset($_POST['type']) && array_key_exists($_POST['type'], $admin_tools)) {
							$_POST['menu_admintools'] = $_POST['type'];
						}
						?>
						<form method="POST" name="menu_admintools_form">
							<select name="menu_admintools" onchange="document.menu_admintools_form.submit();">
						<?php
						foreach ($admin_tools as $opt => $desc)
						if ($opt != 'removeNiggle' && $opt != 'addniggle' && $opt != 'removeMNG' && $opt != 'removenegastat') {
									echo "<option value='$opt'" . ($_POST['menu_admintools'] == $opt ? 'SELECTED' : '') . ">$desc</option>";
						}
						?>
						<OPTGROUP LABEL='Redraft actions'>
						<?php
						foreach ($admin_tools as $opt => $desc)
						if ($opt == 'removeNiggle' || $opt == 'addniggle' || $opt == 'removeMNG' || $opt == 'removenegastat') {
									echo "<option value='$opt'" . ($_POST['menu_admintools'] == $opt ? 'SELECTED' : '') . ">$desc</option>";
						}
						?>
						</OPTGROUP>
						</select>
						<!-- <input type="submit" name="admintools" value="OK"> -->
						</form>

						<br><i><?php echo $lng->getTrn('common/desc');?>:</i><br><br>
						<form name='form_admintools' method='POST'>
							<?php
							$DISABLE = false;
							switch ($_POST['menu_admintools']) {
								/***************
								 * Un-hire journeymen
								 **************/
								case 'unhire_journeyman':
									echo $lng->getTrn('profile/team/box_admin/desc/unhire_journeyman');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->qty != 16)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="unhire_journeyman">
									<?php
									break;
								/***************
								 * Un-sell player
								 **************/
								case 'unsell_player':
									echo $lng->getTrn('profile/team/box_admin/desc/unsell_player');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold) {
												echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
												$DISABLE = false;
										}
									}
									?>
									</select>
									<input type="hidden" name="type" value="unsell_player">
									<?php
									break;
								/***************
								 * Un-buy team goods
								 **************/
								case 'unbuy_goods':
									echo $lng->getTrn('profile/team/box_admin/desc/unbuy_goods');
									?>
									<hr><br>
									<select name="thing">
									<?php
									$DISABLE = true;
										foreach ($team->getGoods() as $name => $details) {
										if ($team->$name > 0) { # Only allow to un-buy those things which we already have some of.
											echo "<option value='$name'>$details[item]</option>\n";
											$DISABLE = false;
										}
									}
									?>
									</select>
									<input type="hidden" name="type" value="unbuy_goods">
									<?php
									break;
								/***************
								 * Gold bank
								 **************/
								case 'bank':
									echo $lng->getTrn('profile/team/box_admin/desc/bank');
									?>
									<hr><br>
									&Delta; team treasury:<br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type='text' name="amount" maxlength=5 size=5>k
									<input type="hidden" name="type" value="bank">
									<?php
									break;
								/***************
								 * Manage Fan Factor
								***************/
								case 'ff':
									echo $lng->getTrn('profile/team/box_admin/desc/ff');
									?>
									<hr><br>
									Bought dedicated fans + Match dedicated fans = Total<br>
									<input type='text' name="amount" value="<?php echo $team->ff_bought.'" maxlength=2 size=1 style="text-align: right">+'.($team->rg_ff-$team->ff_bought).'='.$team->rg_ff ?>
									<input type="hidden" name="type" value="ff">
									<?php
									break;
								/***************
								 * Reset selected team league
								***************/
								case 'resetleague':
									echo $lng->getTrn('profile/team/box_admin/desc/resetleague');
									?>
									<input type="hidden" name="type" value="resetleague">
									<?php
									break;
								/***************
								 * Reset selected team special rule
								***************/
								case 'resetrule':
									echo $lng->getTrn('profile/team/box_admin/desc/resetrule');
									?>
									<input type="hidden" name="type" value="resetrule">
									<?php
									break;
								/***************
								 * Reset selected team captain
								***************/
								case 'resetcaptain':
									echo $lng->getTrn('profile/team/box_admin/desc/resetcaptain');
									?>
									<input type="hidden" name="type" value="resetcaptain">
									<?php
									break;
								/***************
								 * Transfer team to another coach
								***************/
								case 'transferteam':
									echo $lng->getTrn('profile/team/box_admin/desc/transferteam');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/coach');?>:<br>
									<select name="coachname">
									<?php
									$DISABLE = true;
									foreach ($coaches as $c) {
										echo "<option value='$c->coach_id'>$c->cname</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="transferteam">
									<?php
									break;
								/***************
								 * Manage extra SPP
								 **************/
								case 'spp':
									echo $lng->getTrn('profile/team/box_admin/desc/spp');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									//objsort($players, array('+is_dead', '+name'));
									foreach ($players as $p) {
										if (!$p->is_sold) {
											echo "<option value='$p->player_id'".(($p->is_dead) ? ' style="background-color:'.COLOR_HTML_DEAD.';"' : '').">$p->nr $p->name</option>";
											$DISABLE = false;
										}
									}
									objsort($players, array('+nr'));
									?>
									</select>
									<br><br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type='text' name='amount' maxlength="5" size="5"> &Delta; SPP
									<input type="hidden" name="type" value="spp">
									<?php
									break;
								/***************
								 * Manage extra player value
								 **************/
								case 'dval':
									echo $lng->getTrn('profile/team/box_admin/desc/dval');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									//objsort($players, array('+is_dead', '+name'));
									foreach ($players as $p) {
										if (!$p->is_sold) {
											echo "<option value='$p->player_id'".(($p->is_dead) ? ' style="background-color:'.COLOR_HTML_DEAD.';"' : '').">$p->nr $p->name (current extra = ".($p->extra_val/1000)."k)</option>";
											$DISABLE = false;
										}
									}
									objsort($players, array('+nr'));
									?>
									</select>
									<br><br>
									Set extra value to<br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type='text' name='amount' maxlength="10" size="6">k
									<input type="hidden" name="type" value="dval">
									<?php
									break;
								/***************
								 * Manage extra skills
								 **************/
								case 'extra_skills':
									echo $lng->getTrn('profile/team/box_admin/desc/extra_skills');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if (!$p->is_sold && !$p->is_dead) {
											echo "<option value='$p->player_id'>$p->nr $p->name</option>";
											$DISABLE = false;
										}
									}
									?>
									</select>
									<br><br>
									Skill:<br>
									<select name="skill">
									<?php
									foreach ($skillarray as $cat => $skills) {
										echo "<OPTGROUP LABEL='$cat'>";
										foreach ($skills as $id => $skill) {
											echo "<option value='$id'>$skill</option>";
										}
										echo "</OPTGROUP>";
									}
									?>
									</select>
									<br><br>
									Action (add/remove)<br>
									<input type="radio" CHECKED name="sign" value="+">+
									<input type="radio" name="sign" value="-">-
									<input type="hidden" name="type" value="extra_skills">
									<?php
									break;
								/***************
								 * Remove achieved skills
								 **************/
								case 'ach_skills':
								echo $lng->getTrn('profile/team/box_admin/desc/ach_skills');
								?>
								<hr><br>
								<?php
								// Build a map of player_id -> their achieved skills (normal/double) and stat improvements
								$playerAchSkills = array();
								foreach ($players as $p) {
									if ($p->is_dead || $p->is_sold) continue;
									$achSkills = array();
									$seen = array();
									// Normal (primary) achieved skills
									foreach ($p->ach_nor_skills as $skillId) {
										if (isset($seen[$skillId])) continue;
										foreach ($skillarray as $cat => $skills) {
											if ($cat === 'E') continue;
											if (isset($skills[$skillId])) {
												$achSkills[] = array('value' => (string)$skillId, 'label' => $skills[$skillId] . ' (primary)');
												$seen[$skillId] = true;
												break;
											}
										}
									}
									// Secondary (double) achieved skills
									foreach ($p->ach_dob_skills as $skillId) {
										if (isset($seen[$skillId])) continue;
										foreach ($skillarray as $cat => $skills) {
											if ($cat === 'E') continue;
											if (isset($skills[$skillId])) {
												$achSkills[] = array('value' => (string)$skillId, 'label' => $skills[$skillId] . ' (secondary)');
												$seen[$skillId] = true;
												break;
											}
										}
									}
									// Characteristic improvements
									foreach ($CHR_CONV as $key => $name) {
										$isImproved = false;
										if ($name == 'ma' || $name == 'st' || $name == 'av') {
											$isImproved = ($p->$name > $p->{"def_$name"});
										} else {
											$defVal = $p->{"def_$name"};
											$isImproved = ($defVal > 0 && $p->$name < $defVal);
										}
										if ($isImproved) {
											$label = ($name == 'ma' || $name == 'av' || $name == 'st') ? '+ '.ucfirst($name) : '- '.ucfirst($name);
											$achSkills[] = array('value' => 'ach_'.$key, 'label' => $label . ' (stat)');
										}
									}
									$playerAchSkills[$p->player_id] = $achSkills;
								}
								?>
								<script>
								var achSkillsByPlayer = <?php echo json_encode($playerAchSkills); ?>;

								function updateAchSkillDropdown() {
									var playerId = document.getElementById('ach_skills_player').value;
									var skillSelect = document.getElementById('ach_skills_skill');
									skillSelect.innerHTML = '';

									var skills = achSkillsByPlayer[playerId] || [];
									if (skills.length === 0) {
										var opt = document.createElement('option');
										opt.value = '';
										opt.textContent = '-- No achieved skills --';
										skillSelect.appendChild(opt);
										return;
									}

									skills.forEach(function(s) {
										var opt = document.createElement('option');
										opt.value = s.value;
										opt.textContent = s.label;
										skillSelect.appendChild(opt);
									});
								}
								</script>

								<?php echo $lng->getTrn('common/player');?>:<br>
								<select name="player" id="ach_skills_player" onchange="updateAchSkillDropdown()">
								<?php
								$DISABLE = true;
								foreach ($players as $p) {
									if (!$p->is_dead && !$p->is_sold) {
										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
								}
								?>
								</select>
								<br><br>
								Skill<br>
								<select name="skill" id="ach_skills_skill">
								</select>
								<input type="hidden" name="type" value="ach_skills">
								<script>
								// Populate on initial load
								updateAchSkillDropdown();
								</script>
								<?php
								break;
								/***************
								 * Manage Player Hatred
								 **************/
								 case 'manage_hatred':
									global $playerkeywordsarray;
									echo $lng->getTrn('common/player').':<br>';
									?>
									<select name="player">
									<?php
									foreach ($players as $p) {
										if (!$p->is_dead && !$p->is_sold) {
											echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										}
									}
									?>
									</select>
									<br><br>
									Action:<br>
									<select name="hatred_action" onchange="document.getElementById('new_race_select').disabled = (this.value !== 'change');">
										<option value="remove">Remove hatred of...</option>
										<option value="change">Change hatred of...</option>
										<option value="add">Add hatred of...</option>
									</select>
									<br><br>
									Race:<br>
									<select name="race_id">
									<?php
									foreach ($playerkeywordsarray['K'] as $kid => $kname) {
										echo "<option value='$kid'>$kname</option>\n";
									}
									?>
									</select>
									<br><br>
									Change to:<br>
									<select name="new_race_id" id="new_race_select" disabled>
									<?php
									foreach ($playerkeywordsarray['K'] as $kid => $kname) {
										echo "<option value='$kid'>$kname</option>\n";
									}
									?>
									</select>
									<input type="hidden" name="type" value="manage_hatred">
									<?php
									break;
								/***************
								 * Remove niggling injuries
								 **************/
								case 'removeNiggle':
									echo $lng->getTrn('profile/team/box_admin/desc/removeNiggle');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->getCurrentNiggleCount() <= 0)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="removeNiggle">
									<?php
									break;
								/***************
								 * Add niggling injury
								 **************/
								case 'addniggle':
									echo $lng->getTrn('profile/team/box_admin/desc/addniggle');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="addniggle">
									<?php
									break;
								/***************
								 * Remove MNG Status
								 **************/
								case 'removeMNG':
									echo $lng->getTrn('profile/team/box_admin/desc/removeMNG');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->getStatus('-1') == 1)
											continue;
										echo "<option value='$p->player_id'>$p->nr $p->name"?>
										<?php 
										if ($p->getStatus('-1') == 2) {
											echo " (MNG)</option>\n";
										} elseif ($p->getStatus('-1') == 0) {
											echo " (RET)</option>\n";
										} 
										$DISABLE = false;
									}
									?>
									</select>
									<input type="hidden" name="type" value="removeMNG">
									<?php
									break;
								/***************
								 * Remove a stat injury
								 **************/
								case 'removenegastat':
									echo $lng->getTrn('profile/team/box_admin/desc/removenegastat');
									?>
									<hr><br>
									<?php echo $lng->getTrn('common/player');?>:<br>
									<select name="player">
									<?php
									$DISABLE = true;
									foreach ($players as $p) {
										if ($p->is_sold || $p->is_dead || $p->is_journeyman || $p->inj_ma + $p->inj_st + $p->inj_ag + $p->inj_pa + $p->inj_av == 0)
											continue;

										echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
										$DISABLE = false;
									}
									?>
									</select>
									<br><br>
									Stat injury<br>
									<select name="stat">
									<?php
									foreach ($players as $p) {
										if  ($p->inj_ma > 0) {
										echo "<option value='ma'>- MA</option>\n";
										}
										if  ($p->inj_st > 0) {
										echo "<option value='st'>- ST</option>\n";
										}
										if  ($p->inj_ag > 0) {
										echo "<option value='ag'>+ AG</option>\n";
										}
										if  ($p->inj_pa > 0) {
										echo "<option value='pa'>+ PA</option>\n";
										}
										if  ($p->inj_av > 0) {
										echo "<option value='av'>- AV</option>\n";
										}
									}
									?>
									</select>
									<input type="hidden" name="type" value="removenegastat">
									<?php
									break;
							}
							?>
							<br><br>
							<?php
							$admin_confirm = array('manage_hatred');
							?>
							<input type="submit" name="button" value="OK" <?php echo ($DISABLE ? 'DISABLED' : '');?>
								<?php if (in_array($_POST['menu_admintools'], $admin_confirm)) { echo "onClick=\"if(!confirm('".$lng->getTrn('common/confirm_box')."')){return false;}\""; } ?>
							>
						</form>
					</div>
				</div>
				<?php
			}
		}
		?>
		<br>
		<div class="row"></div>
		<br>
		<?php
		if (!$settings['hide_ES_extensions']){
			?>
			<div class="row">
				<div class="boxWide">
					<div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('ES');"><b>[+/-]</b></a> &nbsp;<?php echo $lng->getTrn('common/extrastats'); ?></div>
					<div class="boxBody" id="ES" style='display:none;'>
						<?php
						HTMLOUT::generateEStable($this);
						?>
					</div>
				</div>
			</div>
			<?php
		}
		// If an team action was chosen, jump to actions HTML anchor.
		if ($JMP_ANC) {
			?>
			<script language="JavaScript" type="text/javascript">
			window.location = "#aanc";
			</script>
			<?php
		}
	}
	
	public static function _sppCostTable() {
		// Indexed by numberOfAchSkill() (0-5)
		// [random_primary, chosen_primary, chosen_secondary, stat_increase]
		return array(
			array(3,  6,  10, 14),
			array(4,  8,  12, 16),
			array(6,  12, 16, 20),
			array(8,  16, 20, 24),
			array(10, 20, 24, 28),
			array(15, 30, 34, 38),
		);
	}
	
	private static function _getHireFirePhase($team_id) {
		// Returns: 'unrestricted', 'hire_clean', 'hire_done', or 'fire'
		global $rules;
		if (empty($rules['enforce_hire_fire_order'])) {
			return 'unrestricted';
		}
		$tid = $team_id;
		// Get last match date for this team
		$res = mysql_query(
			"SELECT MAX(date_played) AS last_match FROM matches "
			. "WHERE (team1_id = $tid OR team2_id = $tid) "
			. "AND date_played IS NOT NULL"
		);
		$row = mysql_fetch_assoc($res);
		$lastMatch = $row ? $row['last_match'] : null;
		// No matches played yet: no enforcement
		if (!$lastMatch) {
			return 'unrestricted';
		}
		// Check: any player fired since last match?
		$res2 = mysql_query(
			"SELECT COUNT(*) AS cnt FROM players "
			. "WHERE owned_by_team_id = $tid "
			. "AND date_sold IS NOT NULL "
			. "AND date_sold > '$lastMatch'"
		);
		$row2 = mysql_fetch_assoc($res2);
		if ($row2 && $row2['cnt'] > 0) {
			$_SESSION['hire_fire_phase_' . $tid] = 'fire';
			return 'fire';
		}
		// Check: any player hired since last match and not yet played?
		$res3 = mysql_query(
			"SELECT COUNT(*) AS cnt FROM players "
			. "WHERE owned_by_team_id = $tid "
			. "AND date_bought > '$lastMatch' "
			. "AND player_id NOT IN "
			. "(SELECT DISTINCT f_player_id FROM match_data)"
		);
		$row3 = mysql_fetch_assoc($res3);
		if ($row3 && $row3['cnt'] > 0) {
			$_SESSION['hire_fire_phase_' . $tid] = 'hire';
			return 'hire_done';
		}
		// Session: coach clicked Skip this session
		$sessionKey = 'hire_fire_phase_' . $tid;
		if (isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === 'fire') {
			return 'fire';
		}
		return 'hire_clean';
	}
	
	/**
	 * Build stat improvement data for a player (current value, improvements, caps).
	 * Used to populate JS data attributes for the random stat modal.
	 */
	private static function _buildStatData($p) {
		return array(
			'ma' => array('current' => (int)$p->ma, 'improvements' => (int)$p->ach_ma),
			'st' => array('current' => (int)$p->st, 'improvements' => (int)$p->ach_st),
			'ag' => array('current' => (int)$p->ag, 'improvements' => (int)$p->ach_ag),
			'pa' => array('current' => (int)$p->pa, 'improvements' => (int)$p->ach_pa),
			'av' => array('current' => (int)$p->av, 'improvements' => (int)$p->ach_av),
		);
	}

	/**
	 * Returns a string of category letters the player has Primary access to (e.g. "GASP").
	 */
	private static function _getPrimaryCategories($p, $team, $DEA) {
		foreach ($DEA[$team->f_rname]['players'] as $posName => $posDetails) {
			if ($posDetails['pos_id'] == $p->f_pos_id) {
				return isset($posDetails['norm']) ? implode('', (array)$posDetails['norm']) : '';
			}
		}
		return '';
	}

	/**
	 * Returns a string of category letters the player has Secondary access to (e.g. "SM"),
	 * excluding categories already in Primary.
	 */
	private static function _getSecondaryCategories($p, $team, $DEA) {
		foreach ($DEA[$team->f_rname]['players'] as $posName => $posDetails) {
			if ($posDetails['pos_id'] == $p->f_pos_id) {
				$primary   = isset($posDetails['norm']) ? (array)$posDetails['norm'] : array();
				$secondary = isset($posDetails['doub']) ? (array)$posDetails['doub'] : array();
				// Only return cats not already in primary
				$diff = array_diff($secondary, $primary);
				return implode('', $diff);
			}
		}
		return '';
	}

	private function _teamManagementBox($players, $team) {
		global $lng, $rules, $DEA, $T_ALLOWED_PLAYER_NR, $skillididx;
		?>
		<!-- Following HTML is from class_team_htmlout.php _teamManagementBox -->
		<div class="boxTeamPage">
		<div class="boxTitle<?php echo T_HTMLBOX_COACH;?>"><?php echo $lng->getTrn('profile/team/box_tm/title') . ' - ' . $team->name;?></div>
		<div class="boxBody">
			<?php
			$base = 'profile/team';
				$tmanage = array(
					'select_league'     => $lng->getTrn($base.'/box_tm/select_league'),
					'select_rule'       => $lng->getTrn($base.'/box_tm/select_rule'),
					'select_captain'    => $team->format == 'SV' ? $lng->getTrn($base.'/box_tm/select_veteran') : $lng->getTrn($base.'/box_tm/select_captain'),
					'hire_player'       => $lng->getTrn($base.'/box_tm/hire_player'),
					'hire_journeyman'   => $lng->getTrn($base.'/box_tm/hire_journeyman'),
					'fire_player'       => $team->format == 'SV' ? $lng->getTrn($base.'/box_tm/fire_jm') : $lng->getTrn($base.'/box_tm/fire_player'),
					'unbuy_player'      => $lng->getTrn($base.'/box_tm/unbuy_player'),
					'rename_player'     => $lng->getTrn($base.'/box_tm/rename_player'),
					'renumber_player'   => $lng->getTrn($base.'/box_tm/renumber_player'),				
					'random_skill'   	=> $lng->getTrn($base.'/box_tm/random_skill'),
					'random_stat'    	=> $lng->getTrn($base.'/box_tm/random_stat'),					
					'expensive_mistakes' => $lng->getTrn($base.'/box_tm/expensive_mistakes'),
					'retire_player'   	=> $lng->getTrn($base.'/box_tm/retire_player'),
					'rename_team'       => $lng->getTrn($base.'/box_tm/rename_team'),
					'buy_goods'         => $lng->getTrn($base.'/box_tm/buy_goods'),
					'drop_goods'        => $lng->getTrn($base.'/box_tm/drop_goods'),
					'ready_state'       => $lng->getTrn($base.'/box_tm/ready_state'),
					'retire'            => $lng->getTrn($base.'/box_tm/retire'),
					'delete'            => $lng->getTrn($base.'/box_tm/delete'),
				);				
			# If random skills are turned off in the settings, hide option
			if ($rules['randomskillrolls'] == 1) {
			unset($tmanage['random_skill']);
			}
			# If random stat increases are turned off in the settings, hide option
			if ($rules['randomstatrolls'] == 1) {
				unset($tmanage['random_stat']);
			}
			# If a team league has already been selected OR if it does not apply, hide option
			if (strlen($team->getLeaguechosen()) >= 1 || strlen($team->getLeagueoptions()) == 1  ) { 
			unset($tmanage['select_league']);
			}
			# If a favoured of ... rule has already been selected OR if it does not apply, hide option
			if (strlen($team->getFavrulechosen()) >= 1 || strlen($team->getFavruleoptions()) == 0  ) { 
			unset($tmanage['select_rule']);
			}
			# If a team captain/veteran has already been selected, or neither applies, hide option
            if ((($team->getTeamrules() != 22 && strpos($team->getTeamrules(),"22") == FALSE) && $team->format != 'SV') || (strlen($team->getTeamcaptain()) >0)) {
            unset($tmanage['select_captain']);
			}
			# Sevens rules do not allow temporary retirement or dropping goods; firing is still
			# allowed but restricted to journeymen only (see fire_player case below), and only
			# shown at all if the team actually has a journeyman on the roster
			if ($team->format == 'SV') {
				unset($tmanage['retire_player']);
				unset($tmanage['drop_goods']);
				$hasJourneyman = false;
				foreach ($players as $p) {
					if ($p->is_journeyman && !$p->is_dead && !$p->is_sold) {
						$hasJourneyman = true;
						break;
					}
				}
				if (!$hasJourneyman) {
					unset($tmanage['fire_player']);
				}
			}
			# Enforce hire/fire order: lock out hiring once fire phase is active
			$_hireFire_phase = self::_getHireFirePhase($team->team_id);
			if ($_hireFire_phase === 'fire') {
				unset($tmanage['hire_player']);
				unset($tmanage['unbuy_player']);
			}
			# If one of these are selected from the menu, a JavaScript confirm prompt is displayed before submitting.
			# Note: Don't add "hire_player" here - players may be un-bought if not having played any games.
			$tmange_confirm = array('hire_journeyman', 'fire_player', 'buy_goods', 'drop_goods','retire_player','select_league','select_rule','select_captain','random_skill','random_stat');
			// Set default choice.
			if (!isset($_POST['menu_tmanage'])) {
				reset($tmanage);
				$_POST['menu_tmanage'] = key($tmanage);
			}
			// If action is already chosen, then make it the default selected.
			if (isset($_POST['type']) && array_key_exists($_POST['type'], $tmanage)) {
				$_POST['menu_tmanage'] = $_POST['type'];
			}
			?>
			<form method="POST" name="menu_tmanage_form">
				<select name="menu_tmanage" onchange="document.menu_tmanage_form.submit();">
					<?php
					foreach ($tmanage as $opt => $desc)
						echo "<option value='$opt'" . ($_POST['menu_tmanage'] == $opt ? 'SELECTED' : '') . ">$desc</option>";
					?>
				</select>
				<!-- <input type="submit" name="tmanage" value="OK"> -->
			</form>
			<br><i><?php echo $lng->getTrn('common/desc');?>:</i><br><br>
			<form name="form_tmanage" method="POST" enctype="multipart/form-data">
			<?php
			$DISABLE = false;
			switch ($_POST['menu_tmanage']) {
				/**************
				 * Select teams league
				 **************/
				case 'select_league':				
					echo $lng->getTrn('profile/team/box_tm/desc/select_league');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/teamleagues');?>:<br>
					<select name="teamleague">
					<?php
                    $optionsArr = explode(",", $team->getLeagueoptions()); 
					foreach ($optionsArr as $opt) {
					$opt_text = 'profile/team/box_tm/favrule/r'.$opt;
						echo "<option value='$opt'>".$lng->getTrn($opt_text)."</option>\n";
					}
					?>
					</select>
					<input type="hidden" name="type" value="select_league">
					<?php
					break;
				/**************
				 * Select teams special rule
				 **************/
				case 'select_rule':				
					echo $lng->getTrn('profile/team/box_tm/desc/select_rule');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/specialrules');?>:<br>
					<select name="rule">
					<?php
                    $optionsArr = explode(",", $team->getFavruleoptions()); 
					foreach ($optionsArr as $opt) {
					$opt_text = 'profile/team/box_tm/favrule/r'.$opt;
						echo "<option value='$opt'>".$lng->getTrn($opt_text)."</option>\n";
					}
					?>
					</select>
					<input type="hidden" name="type" value="select_rule">
					<?php
					break;
				/**************
				 * Select teams captain
				 **************/
				case 'select_captain':				
					echo $lng->getTrn($base.'/box_tm/desc/'.($team->format == 'SV' ? 'select_veteran' : 'select_captain'));
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold || $p->is_bigguy || $p->is_retired || $p->is_captain)
							continue;
						if ($team->format == 'SV') {
							$isLineman = false;
							foreach ($DEA[$team->f_rname]['players'] as $posDetails) {
								if ($posDetails['pos_id'] == $p->f_pos_id && $posDetails['pos_type'] == 'LN') {
									$isLineman = true;
									break;
								}
							}
							if (!$isLineman) continue;
						}

						echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<input type="hidden" name="type" value="select_captain">
					<?php
					break;
				/**************
				 * Hire player
				 **************/
				case 'hire_player':
					echo $lng->getTrn('profile/team/box_tm/desc/hire_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name='player'>
					<?php
					$active_players = array_filter($players, create_function('$p', "return (\$p->is_sold || \$p->is_dead || \$p->is_mng || \$p->is_retired) ? false : true;"));
					$DISABLE = true;
					foreach ($DEA[$team->f_rname]['players'] as $pos => $details) {
						// Show players on the select list if buyable, or if player is a potential journeyman AND team has not reached journeymen limit. Also Checking for big guy limits via isMaxBigGuys and dungeon bowl positional limits via isPlayerBuyable
						if ($team->format =='SV') {
							if ($team->isMaxBigGuys()) {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost'] && $details['is_bigguy'] == 0) ||
									(($details['qty'] == 11) && count($active_players) < $rules['journeymen_limit_sevens'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
							else {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost']) ||
									(($details['qty'] == 11) && count($active_players) < $rules['journeymen_limit_sevens'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
						} else {
							if ($team->isMaxBigGuys()) {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost'] && $details['is_bigguy'] == 0) ||
									(($details['qty'] == 16 || $details['qty'] == 12) && count($active_players) < $rules['journeymen_limit'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
							else {
								if (($team->isPlayerBuyable($details['pos_id']) && $team->treasury >= $details['cost']) ||
									(($details['qty'] == 16 || $details['qty'] == 12) && count($active_players) < $rules['journeymen_limit'])) {
									echo "<option value='$details[pos_id]'>" . $details['cost']/1000 . "k | ".$lng->GetTrn('position/'.strtolower($lng->FilterPosition($pos)))."</option>\n";
									$DISABLE = false;
								}
							}
						}
					}
					echo "</select>\n";
					?>
					<br><br>
					<?php echo $lng->getTrn('common/number');?>:<br>
					<select name="number">
					<?php
					foreach ($T_ALLOWED_PLAYER_NR as $i) {
						foreach ($players as $p) {
							if ($p->nr == $i && !$p->is_sold && !$p->is_dead)
								continue 2;
						}
						echo "<option value='$i'>$i</option>\n";
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->GetTrn('common/journeyman')?> ? <input type="checkbox" name="as_journeyman" value="1">
					<br><br>
					<?php echo $lng->getTrn('common/name');?>:<br>
					<input type="text" name="name">
					<input type="hidden" name="type" value="hire_player">
					<?php
					break;
				/**************
				 * Hire journeymen
				 **************/
				case 'hire_journeyman':
					echo $lng->getTrn('profile/team/box_tm/desc/hire_journeyman');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						$price = $p->value;
						if (!$p->is_journeyman || $p->is_sold || $p->is_dead ||
							$team->treasury < $price || !$team->isPlayerBuyable($p->f_pos_id) || $team->isFull()) {
							continue;
						}

						echo "<option value='$p->player_id'>$p->nr $p->name | " . $price/1000 . " k</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<input type="hidden" name="type" value="hire_journeyman">
					<?php
					break;
				/**************
				 * Fire player
				 **************/
				case 'fire_player':
				// Reuse phase variable set earlier when building the tmanage array.
				// Defensive fallback in case variable is somehow not set.
				// Sevens teams can only fire journeymen, so the hire/fire ordering rule doesn't apply to them.
				$_fp_phase = ($team->format == 'SV') ? 'unrestricted' : (isset($_hireFire_phase) ? $_hireFire_phase : 'unrestricted');
				if ($_fp_phase === 'hire_clean') {
					// Hire phase, no hires yet: show skip button, lock fire OK button
					echo $lng->getTrn('profile/team/box_tm/desc/skip_hire');
					echo '<br><br><form method="POST" style="margin-top:10px;">';
					echo '<input type="hidden" name="type" value="skip_hire_phase">';
					echo '<input type="submit" value="Skip Hire Phase" '
					   . 'style="background:#e07b00;color:white;padding:6px 14px;'
					   . 'border:none;border-radius:3px;cursor:pointer;">';
					echo '</form>';
					$DISABLE = true;
				} else {
					// hire_done, fire, or unrestricted: show normal fire UI
					if ($team->format == 'SV') {
						echo $lng->getTrn('profile/team/box_tm/desc/fire_jm');
					} else {
						echo $lng->getTrn('profile/team/box_tm/desc/fire_player')
							 .' '.$rules['player_refund']*100 . "%.";
					}
					// Warning banner only in hire_done phase
					if ($_fp_phase === 'hire_done') {
						echo '<div style="background:#fff3cd;border:1px solid #e0a800;'
						   . 'padding:10px;margin:10px 0;border-radius:3px;">';
						echo $lng->getTrn('profile/team/box_tm/desc/fire_player');
						echo '</div>';
					}
					// Minimum TV display (unchanged from original)
					$minTV = 0;
					if (isset($rules['min_tv']) && $rules['min_tv'] > 0) {
						$minTV = $rules['min_tv'];
						echo "<br><br><b>League Minimum TV: " . ($minTV/1000) . "k</b>";
						echo "<br>Current Team TV: " . ($team->tv/1000) . "k";
					}
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					// Count rostered players (unchanged from original)
					$rostered_players = 0;
					foreach ($players as $p) {
						if (!$p->is_dead && !$p->is_sold && !$p->is_retired	&& !$p->is_journeyman && !$p->is_mng) {
							$rostered_players++;
						}
					}
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold || ($p->is_captain && !$p->can_firecap)) continue;
						if ($team->format == 'SV' && !$p->is_journeyman) continue;
						$can_fire = false;
						$reason   = '';
						if ($p->is_journeyman) {
							$can_fire = true;
						} else {
							if ($rules['fireunder11'] == 1) {
								$can_fire = true;
							} else {
								$can_fire = ($rostered_players > 11);
								if (!$can_fire)
									$reason = ' (would drop below 11 players)';
							}
							if ($can_fire && $minTV > 0) {
								$refund      = $p->value * $rules['player_refund'];
								$projectedTV = $team->tv - $p->value + $refund;
								if ($projectedTV < $minTV) {
									$can_fire = false;
									$reason = ' (TV would drop below '
											. ($minTV/1000) . 'k min)';
								}
							}
						}
						if ($can_fire) {
							echo "<option value='$p->player_id'>"
							   . ($rules['player_refund']
								   ? (($p->value/1000)*$rules['player_refund'])."k refund | "
								   : "")
							   . "$p->nr $p->name</option>\n";
							$DISABLE = false;
						} else if (!empty($reason)) {
							echo "<option value='$p->player_id' disabled "
							   . "style='color:#999;'>$p->nr $p->name$reason</option>\n";
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="fire_player">
					<?php
				}
				break;
				/***************
				 * Un-buy player
				 **************/
				case 'unbuy_player':
					echo $lng->getTrn('profile/team/box_tm/desc/unbuy_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_unbuyable() && !$p->is_sold) {
								echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
								$DISABLE = false;
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="unbuy_player">
					<?php
					break;
				/**************
				 * Rename player
				 **************/
				case 'rename_player':
					echo $lng->getTrn('profile/team/box_tm/desc/rename_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						unset($color);
						if ($p->is_dead)
							$color = COLOR_HTML_DEAD;
						elseif ($p->is_sold)
							$color = COLOR_HTML_SOLD;

						echo "<option value='$p->player_id' ".(isset($color) ? "style='background-color: $color;'" : '').">$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->getTrn('common/name');?>:<br>
					<input type='text' name='name' maxlength=50 size=20>
					<input type="hidden" name="type" value="rename_player">
					<?php
					break;
				/**************
				 * Renumber player
				 **************/
				case 'renumber_player':
					echo $lng->getTrn('profile/team/box_tm/desc/renumber_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						unset($color);
						if ($p->is_dead)
							$color = COLOR_HTML_DEAD;
						elseif ($p->is_sold)
							$color = COLOR_HTML_SOLD;

						echo "<option value='$p->player_id' ".(isset($color) ? "style='background-color: $color;'" : '').">$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<br><br>
					<?php echo $lng->getTrn('common/number');?>:<br>
					<select name="number">
					<?php
					foreach ($T_ALLOWED_PLAYER_NR as $i) {
						echo "<option value='$i'>$i</option>\n";
					}
					?>
					</select>
					<input type="hidden" name="type" value="renumber_player">
					<?php
					break;
				/**************
				 * New Random Skill
				 **************/
				case 'random_skill':
				echo $lng->getTrn('profile/team/box_tm/desc/random_skill');
				if ($rules['randomstatrolls'] == 1) {
					echo $lng->getTrn('profile/team/box_tm/desc/random_skill2');
				}
				?>
				<hr><br>
				<?php echo $lng->getTrn('common/player');?>:<br>
				<select name="player" id="random_skill_player">
				<?php
				$hasEligiblePlayers = false;
				foreach ($players as $p) {
					if ($p->is_dead || $p->is_sold || $p->is_retired) 
						continue;
					if (($p->numberOfAchSkill() == 0 && $p->mv_spp > 2) || 
						($p->numberOfAchSkill() == 1 && $p->mv_spp > 3) || 
						($p->numberOfAchSkill() == 2 && $p->mv_spp > 5) || 
						($p->numberOfAchSkill() == 3 && $p->mv_spp > 7) || 
						($p->numberOfAchSkill() == 4 && $p->mv_spp > 9) || 
						($p->numberOfAchSkill() == 5 && $p->mv_spp > 14)) {
						
						// Find the position details by searching for matching pos_id
						$primarySkills = '';
						
						foreach ($DEA[$team->f_rname]['players'] as $posName => $posDetails) {
							if ($posDetails['pos_id'] == $p->f_pos_id) {
								// norm array contains primary skills
								$primarySkills = isset($posDetails['norm']) ? implode('', $posDetails['norm']) : '';
								break;
							}
						}						
						echo "<option value='$p->player_id' data-primary='$primarySkills'>$p->nr $p->name ($p->mv_spp SPP)</option>\n";
						$hasEligiblePlayers = true;
					}
				}
				?>
				</select>
				<br><br>
				<input type="hidden" name="skill_type" id="random_skill_type" value="P">
				<?php echo $lng->getTrn('common/skill_cat');?>:<br>
				<select name="skill_cat" id="random_skill_cat">
					<option value="">-- Select player first --</option>
				</select>
				<br><br>
				<button type="button" id="generate_random_skills" <?php echo !$hasEligiblePlayers ? 'disabled' : ''; ?>>
					Generate Two Random Skills
				</button>
				
				<!-- Modal for skill selection -->
				<div id="skill_selection_modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000;">
					<div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:30px; border-radius:10px; min-width:400px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
						<h3 style="margin-top:0;">Choose Your Skill</h3>
						<p style="color:#d32f2f; font-weight:bold; margin:10px 0;">⚠️ You must choose one of these skills.</p>
						<div id="skill_options" style="margin:20px 0;">
							<!-- Skills will be loaded here -->
						</div>
					</div>
				</div>
				
				<script>
				// Category name mapping
				var categoryNames = {
					'G': 'General',
					'A': 'Agility',
					'D': 'Devious',
					'S': 'Strength',
					'P': 'Passing',
					'M': 'Mutation'
				};

				// Track if skills have been generated
				var skillsGenerated = false;

				// Update skill categories when player changes
				function updateSkillCategories() {
					var playerSelect = document.getElementById('random_skill_player');
					var skillCatSelect = document.getElementById('random_skill_cat');
					
					var selectedPlayer = playerSelect.options[playerSelect.selectedIndex];
					
					if (!selectedPlayer) {
						skillCatSelect.innerHTML = '<option value="">-- Select player first --</option>';
						return;
					}
					
					// Always use Primary for Third Season rules
					var availableCategories = selectedPlayer.getAttribute('data-primary') || '';
					
					// Clear and rebuild category dropdown
					skillCatSelect.innerHTML = '';
					
					if (availableCategories.length === 0) {
						skillCatSelect.innerHTML = '<option value="">-- No primary categories available --</option>';
						return;
					}
					
					// Add available categories
					for (var i = 0; i < availableCategories.length; i++) {
						var cat = availableCategories.charAt(i);
						if (categoryNames[cat]) {
							var option = document.createElement('option');
							option.value = cat;
							option.textContent = categoryNames[cat];
							skillCatSelect.appendChild(option);
						}
					}
				}

				// Attach event listener
				document.getElementById('random_skill_player').addEventListener('change', function() {
					skillsGenerated = false;
					document.getElementById('generate_random_skills').disabled = false;
					updateSkillCategories();
				});

				// Initialize on page load
				updateSkillCategories();
				
				<?php
				// On page load: check if this team has any player with a pending skill roll
				$pending_skill = mysql_fetch_assoc(mysql_query(
					"SELECT pr.roll_id, pr.player_id, pr.roll_data "
					. "FROM pending_rolls pr "
					. "JOIN players p ON pr.player_id = p.player_id "
					. "WHERE p.owned_by_team_id = " . (int)$team->team_id
					. " AND pr.roll_type = 'skill' AND pr.is_confirmed = 0 LIMIT 1"
				));
				if ($pending_skill) {
					$ps_player = (int)$pending_skill['player_id'];
					$ps_data   = json_encode($pending_skill['roll_data']);
					echo "window._pendingSkillPlayer = $ps_player;\n";
					// Get actual current spp cost for this player
					$spp_cost_res = mysql_fetch_assoc(mysql_query(
						"SELECT (COUNT(ps.f_skill_id) + 
								 COALESCE(p.ach_ma,0) + COALESCE(p.ach_st,0) + 
								 COALESCE(p.ach_ag,0) + COALESCE(p.ach_pa,0) + 
								 COALESCE(p.ach_av,0)) as cnt 
						 FROM players p
						 LEFT JOIN players_skills ps ON ps.f_pid = p.player_id 
							 AND ps.type IN ('N','D','C')
						 WHERE p.player_id = $ps_player"
					));
					$actual_num_skills = (int)$spp_cost_res['cnt'];
					$spp_costs = array(0=>3, 1=>4, 2=>6, 3=>8, 4=>10, 5=>15);
					$actual_spp_cost = isset($spp_costs[$actual_num_skills]) ? $spp_costs[$actual_num_skills] : 15;
					echo "window._pendingSkillSppCost = $actual_spp_cost;\n";
					echo "window._pendingSkillData   = JSON.parse(" . $ps_data . ");\n";
				}
				?>
				if (window._pendingSkillPlayer) {
					// Pre-select the player
					var pSel = document.getElementById('random_skill_player');
					for (var i = 0; i < pSel.options.length; i++) {
						if (parseInt(pSel.options[i].value) === window._pendingSkillPlayer) {
							pSel.selectedIndex = i;
							break;
						}
					}
					// Disable generate button — roll already exists
					var genBtn = document.getElementById('generate_random_skills');
					genBtn.disabled = true;
					genBtn.textContent = 'Roll already generated — choose a skill above';
					// Show the modal with existing results
					// (re-use the existing skill display code by triggering the AJAX response handler)
					var existingModal = document.getElementById('skill_selection_modal');
					// Build display from pending data
					var html = '';
					var data = window._pendingSkillData;
					data.forEach(function(skill) {
						var borderColor = skill.is_elite ? '#FF9800' : '#ddd';
						html += '<div style="margin:10px 0; padding:15px; border:2px solid ' + borderColor + '; border-radius:5px; cursor:pointer;" '
							+ 'onmouseover="this.style.borderColor=\'' + (skill.is_elite ? '#F57C00' : '#4CAF50') + '\'; this.style.background=\'#f0f9f0\'" '
							+ 'onmouseout="this.style.borderColor=\'' + borderColor + '\'; this.style.background=\'white\'" '
							+ 'onclick="selectSkill(' + window._pendingSkillPlayer + ', ' + skill.id + ', \'' + skill.name.replace(/'/g, "\\'") + '\')">';
						html += '<div style="color:#888; font-size:12px; margin-bottom:5px;">🎲 ' + skill.roll_info + '</div>';
						html += '<strong style="font-size:18px;">' + skill.name + '</strong>';
						if (skill.is_elite) {
							html += ' <span style="background:#FF9800; color:white; padding:2px 6px; border-radius:3px; font-size:11px; font-weight:bold;">ELITE</span>';
						}
						html += '<br>';
						if (skill.description) {
							html += '<span style="color:#666;">' + skill.description + '</span><br>';
						}
						html += '<div style="margin-top:8px; padding-top:8px; border-top:1px solid #eee; font-size:13px; color:#555;">';
						html += '<span style="margin-right:15px;">💎 <strong>-' + (window._pendingSkillSppCost || skill.spp_cost) + ' SPP</strong></span>';
						html += '<span>💰 <strong>+' + (parseInt(skill.value_increase) / 1000) + 'k</strong> value</span>';
						html += '</div>';
						html += '</div>';
					});
					document.getElementById('skill_options').innerHTML = html;
					existingModal.style.display = 'block';
				}

				document.getElementById('generate_random_skills').addEventListener('click', function() {
					const playerId = document.getElementById('random_skill_player').value;
					const skillType = 'P'; // Always Primary for Third Season rules
					const skillCat = document.getElementById('random_skill_cat').value;
					
					if (!playerId) {
						alert('Please select a player');
						return;
					}
					
					if (!skillCat) {
						alert('Please select a skill category');
						return;
					}

					// NEW: Confirmation before rolling
					const playerSelect = document.getElementById('random_skill_player');
					const skillCatSelect = document.getElementById('random_skill_cat');
					const playerName = playerSelect.options[playerSelect.selectedIndex].text;
					const catName = skillCatSelect.options[skillCatSelect.selectedIndex].text;
					
					if (!confirm('Rolling a random Primary skill for\n\nPlayer: ' + playerName + '\nCategory: ' + catName + '\n\nAre you sure? This cannot be undone!')) {
						return;
					}

					// Show loading in modal
					const modal = document.getElementById('skill_selection_modal');
					const skillOptions = document.getElementById('skill_options');
					skillOptions.innerHTML = '<p>Rolling dice... 🎲🎲</p>';
					modal.style.display = 'block';
					
					// AJAX call to get 2 random skills
					fetch('lib/class_random_skills.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: 'player_id=' + playerId + '&skill_type=' + skillType + '&skill_cat=' + skillCat
					})
					.then(response => response.text())
					.then(text => {
						console.log('Raw response:', text);
						const data = JSON.parse(text);
						if (data.error) {
							skillOptions.innerHTML = '<p style="color:red;">' + data.error + '</p>';
							// Add close button on error
							skillOptions.innerHTML += '<br><button type="button" onclick="closeSkillModal()" style="background:#ccc; padding:8px 16px; border:none; border-radius:4px; cursor:pointer;">Close</button>';
							return;
						}
						
						// Mark that skills have been generated - disable the generate button
						skillsGenerated = true;
						document.getElementById('generate_random_skills').disabled = true;
						document.getElementById('generate_random_skills').textContent = 'Skills Generated - Choose One Above';
						
						// Display the 2 skills as buttons
						let html = '';
						data.skills.forEach((skill, index) => {
							// Determine border color based on elite status
							const borderColor = skill.is_elite ? '#FF9800' : '#ddd';
							const hoverBorderColor = skill.is_elite ? '#F57C00' : '#4CAF50';
							
							html += '<div style="margin:10px 0; padding:15px; border:2px solid ' + borderColor + '; border-radius:5px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor=\'' + hoverBorderColor + '\'; this.style.background=\'#f0f9f0\'" onmouseout="this.style.borderColor=\'' + borderColor + '\'; this.style.background=\'white\'" onclick="selectSkill(' + playerId + ', ' + skill.id + ', \'' + skill.name.replace(/'/g, "\\'") + '\')">';
							html += '<div style="color:#888; font-size:12px; margin-bottom:5px;">🎲 ' + skill.roll_info + '</div>';
							html += '<strong style="font-size:18px;">' + skill.name + '</strong>';
							
							// Add Elite indicator if applicable
							if (skill.is_elite) {
								html += ' <span style="background:#FF9800; color:white; padding:2px 6px; border-radius:3px; font-size:11px; font-weight:bold;">ELITE</span>';
							}
							
							html += '<br>';
							
							if (skill.description) {
								html += '<span style="color:#666;">' + skill.description + '</span><br>';
							}
							
							// Add SPP cost and value increase info
							html += '<div style="margin-top:8px; padding-top:8px; border-top:1px solid #eee; font-size:13px; color:#555;">';
							html += '<span style="margin-right:15px;">💎 <strong>-' + skill.spp_cost + ' SPP</strong></span>';
							
							// Format value increase (convert 20000 to 20k, 30000 to 30k)
							const valueInK = (parseInt(skill.value_increase) / 1000) + 'k';
							html += '<span>💰 <strong>+' + valueInK + '</strong> value</span>';
							html += '</div>';
							
							html += '</div>';
						});
						
						// Check if both skills are the same
						if (data.skills[0].id === data.skills[1].id) {
							html += '<p style="color:#ff9800; font-style:italic; text-align:center; margin-top:10px;">⚠️ Same skill rolled twice - you must select this skill!</p>';
						}
						
						skillOptions.innerHTML = html;
					})
					.catch(error => {
						console.error('Fetch error:', error);
						skillOptions.innerHTML = '<p style="color:red;">Error generating skills. Please try again.</p>';
						skillOptions.innerHTML += '<br><button type="button" onclick="closeSkillModal()" style="background:#ccc; padding:8px 16px; border:none; border-radius:4px; cursor:pointer;">Close</button>';
					});
				});

				function selectSkill(playerId, skillId, skillName) {
					if (!confirm('You selected: ' + skillName + '\n\nAre you sure? This cannot be undone.')) {
						return;
					}
					document.getElementById('final_player_id').value = playerId;
					document.getElementById('final_skill_id').value = skillId;
					document.getElementById('final_skill_type').value = 'P';
					document.getElementById('final_skill_form').submit();
				}

				function closeSkillModal() {
					// Only allow closing on error (when skills haven't been successfully generated)
					if (!skillsGenerated) {
						document.getElementById('skill_selection_modal').style.display = 'none';
					}
				}

				// Prevent closing modal by clicking outside when skills are generated
				document.getElementById('skill_selection_modal').addEventListener('click', function(e) {
					if (e.target === this && skillsGenerated) {
						alert('You must choose one of the two skills. You cannot cancel or re-roll!');
					} else if (e.target === this && !skillsGenerated) {
						closeSkillModal();
					}
				});
				</script>
							
				<?php
				break;
				
				/**************
				 * Random Stat Increase
				 **************/
				case 'random_stat':
					$costs = self::_sppCostTable();
					echo $lng->getTrn('profile/team/box_tm/desc/random_stat');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player" id="rstat_player_select">
					<?php
					$hasStatEligible = false;
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold || $p->is_retired) continue;
						$n = min($p->numberOfAchSkill(), 5);
						$statCost = $costs[$n][3];
						if ($p->mv_spp < $statCost) continue;

						// Build cap/improvement data for JS
						$statData = self::_buildStatData($p);

						$p->setChoosableSkills();

						// Build skill lists by category for the skill-instead picker
						// keyed by category letter -> array of [id, name]
						$normByCat = array();
						$seenNorm = array();
						foreach (array_unique($p->choosable_skills['norm']) as $sid) {
							if (isset($seenNorm[$sid])) continue;
							$seenNorm[$sid] = true;
							$cat = get_alt_col('game_data_skills', 'skill_id', $sid, 'cat');
							if (!$cat || $cat === 'E') continue;
							if (!isset($normByCat[$cat])) $normByCat[$cat] = array();
							$normByCat[$cat][] = array('id' => (int)$sid, 'name' => $skillididx[$sid]);
						}
						$doubByCat = array();
						$seenDoub = array();
						foreach (array_unique($p->choosable_skills['doub']) as $sid) {
							if (isset($seenDoub[$sid])) continue;
							$seenDoub[$sid] = true;
							$cat = get_alt_col('game_data_skills', 'skill_id', $sid, 'cat');
							if (!$cat || $cat === 'E') continue;
							if (!isset($doubByCat[$cat])) $doubByCat[$cat] = array();
							$doubByCat[$cat][] = array('id' => (int)$sid, 'name' => $skillididx[$sid]);
						}
						// Sort each category alphabetically by name
						foreach ($normByCat as $cat => &$skills) usort($skills, function($a,$b){ return strcmp($a['name'],$b['name']); });
						foreach ($doubByCat as $cat => &$skills) usort($skills, function($a,$b){ return strcmp($a['name'],$b['name']); });
						unset($skills);

						echo "<option value='$p->player_id' "
							. "data-spp='$p->mv_spp' "
							. "data-skills='$n' "
							. "data-statcost='$statCost' "
							. "data-statjson='" . htmlspecialchars(json_encode($statData), ENT_QUOTES) . "' "
							. "data-primary='" . self::_getPrimaryCategories($p, $team, $DEA) . "' "
							. "data-secondary='" . self::_getSecondaryCategories($p, $team, $DEA) . "' "
							. "data-normbyCat='" . htmlspecialchars(json_encode($normByCat), ENT_QUOTES) . "' "
							. "data-doubbycat='" . htmlspecialchars(json_encode($doubByCat), ENT_QUOTES) . "'"
							. ">$p->nr $p->name ($p->mv_spp SPP)</option>\n";
						$hasStatEligible = true;
					}
					?>
					</select>

					<br><br>
					<div id="rstat_cost_display" style="color: var(--color-text-secondary); font-size: 13px;"></div>
					<br>

					<button type="button" id="rstat_roll_btn" <?php echo !$hasStatEligible ? 'disabled' : ''; ?>>
						Generate Random Stat Increase
					</button>

					<!-- Result modal -->
					<div id="rstat_modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000;">
						<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; min-width:420px; max-width:560px; box-shadow:0 4px 20px rgba(0,0,0,0.3); color: #222;">
							<h3 style="margin-top:0;" id="rstat_modal_title">Stat Roll Result</h3>
							<div id="rstat_modal_body"></div>
						</div>
					</div>

					<script>
					(function() {
						// D8 table: maps roll (1-8) to array of stat keys that are offered
						var D8_TABLE = {
							1: ['av'],
							2: ['av','pa'],
							3: ['av','ma','pa'],
							4: ['av','ma','pa'],
							5: ['ma','pa'],
							6: ['ag','ma'],
							7: ['ag','st'],
							8: ['ma','st','ag','av','pa']  // "Any" — player chooses
						};

						// Stat display names
						var STAT_NAMES = { ma:'MA', st:'ST', ag:'AG', pa:'PA', av:'AV' };

						// Caps: maximum IMPROVED value (av & ma increase is good, ag/pa decrease of roll needed is good)
						// For MA: max 9. For ST: max 8. For AV: max 11 (displayed as 11+). For AG: min 1+ (best). For PA: min 1+ (best).
						// We track improvements as number of times improved, cap at 2.
						// Stat floor/ceiling per stat key:
						var STAT_CAPS = { ma:9, st:8, ag:1, pa:1, av:11 };

						var playerSelect = document.getElementById('rstat_player_select');
						var rollBtn      = document.getElementById('rstat_roll_btn');
						var costDisplay  = document.getElementById('rstat_cost_display');
						var modal        = document.getElementById('rstat_modal');
						var modalBody    = document.getElementById('rstat_modal_body');

						var lastRoll = null;
						var rollLocked = false;
						var currentStatData = null;

						function getSelectedPlayerData() {
							var opt = playerSelect.options[playerSelect.selectedIndex];
							if (!opt) return null;
							return {
								id:         opt.value,
								name:       opt.text,
								spp:        parseInt(opt.getAttribute('data-spp')),
								skills:     parseInt(opt.getAttribute('data-skills')),
								statCost:   parseInt(opt.getAttribute('data-statcost')),
								statData:   JSON.parse(opt.getAttribute('data-statjson')),
								primary:    opt.getAttribute('data-primary'),   // e.g. "GASP"
								secondary:  opt.getAttribute('data-secondary')  // e.g. "SM"
							};
						}

						function updateCostDisplay() {
							var pd = getSelectedPlayerData();
							if (!pd) { costDisplay.innerHTML = ''; return; }
							costDisplay.innerHTML = 'SPP cost for stat increase (or skill instead): <strong>' + pd.statCost + ' SPP</strong> &nbsp;|&nbsp; Current SPP: <strong>' + pd.spp + '</strong>';
						}

						function isStatAvailable(statKey, statData) {
							var sd = statData[statKey];
							if (!sd) return false;
							if (sd.improvements >= 2) return false;
							// Check cap
							if (statKey === 'ma' || statKey === 'st' || statKey === 'av') {
								if (sd.current >= STAT_CAPS[statKey]) return false;
							} else {
								// ag/pa: improvement lowers the roll target; cap = 1 (can't go below 1+)
								if (sd.current <= STAT_CAPS[statKey]) return false;
							}
							return true;
						}

						function rollD8() {
							return Math.floor(Math.random() * 8) + 1;
						}

						playerSelect.addEventListener('change', function() {
							rollLocked = false;
							rollBtn.disabled = false;
							rollBtn.textContent = 'Roll D8 for Stat Increase';
							updateCostDisplay();
						});

						updateCostDisplay();
						
						<?php
						$pending_stat = mysql_fetch_assoc(mysql_query(
							"SELECT pr.roll_id, pr.player_id, pr.roll_data "
							. "FROM pending_rolls pr "
							. "JOIN players p ON pr.player_id = p.player_id "
							. "WHERE p.owned_by_team_id = " . (int)$team->team_id
							. " AND pr.roll_type = 'stat' AND pr.is_confirmed = 0 LIMIT 1"
						));
						if ($pending_stat) {
							$ps_player = (int)$pending_stat['player_id'];
							$ps_data   = json_encode($pending_stat['roll_data']);
							echo "window._pendingStatPlayer = $ps_player;\n";
							echo "window._pendingStatData   = JSON.parse(" . $ps_data . ");\n";
						}
						?>
						if (window._pendingStatPlayer) {
							var pSel = document.getElementById('rstat_player_select');
							for (var i = 0; i < pSel.options.length; i++) {
								if (parseInt(pSel.options[i].value) === window._pendingStatPlayer) {
									pSel.selectedIndex = i;
									pSel.dispatchEvent(new Event('change'));
									break;
								}
							}
							rollBtn.disabled = true;
							rollBtn.textContent = 'Roll already generated — choose above';
							var pd = getSelectedPlayerData();
							if (pd) {
								var rd = window._pendingStatData;
								var roll = rd.roll;
								var offered = rd.offered;
								var isAny = rd.is_any;
								currentStatData = pd.statData;
								rollLocked = true;
								var available = offered.filter(function(s) {
									return isStatAvailable(s, pd.statData);
								});
								var html = '<p style="font-size:15px;">⚠️ You have an existing pending roll: <strong>D8 = ' + roll + '</strong></p>';
								if (!isAny) {
									html += '<p>Offered stat(s): <strong>'
										+ offered.map(function(s){ return STAT_NAMES[s]; }).join(' or ')
										+ '</strong></p>';
								} else {
									html += '<p>Result: <strong>Any</strong> — choose any available stat.</p>';
								}
								if (available.length === 0) {
									html += '<p style="color:#d32f2f;"><strong>All offered stats are at cap. You must choose a skill instead.</strong></p>';
								}
								html += '<hr style="margin:12px 0;">';
								if (available.length > 0) {
									html += '<p style="font-weight:500; margin-bottom:8px;">Choose a stat increase:</p>';
									window.rstatSelectedStat = null;
									available.forEach(function(s) {
										html += '<div id="rstat_stat_' + s + '" '
											+ 'style="margin:8px 0; padding:12px 15px; border:2px solid #ddd; border-radius:5px; cursor:pointer;" '
											+ 'onclick="selectStatOption(\'' + s + '\')">'
											+ '<strong style="font-size:16px;">+ ' + STAT_NAMES[s] + '</strong>'
											+ '<span style="color:#666; font-size:13px; margin-left:10px;">' + getStatDesc(s) + '</span>'
											+ '<div style="font-size:12px; color:#888; margin-top:4px;">'
											+ pd.statCost + ' SPP &nbsp;|&nbsp; improved ' + pd.statData[s].improvements + '/2 times'
											+ '</div></div>';
									});
								}
								html += '<hr style="margin:12px 0;">';
								html += '<p style="font-weight:500; margin-bottom:8px;">Or choose a skill instead:</p>';
								html += buildSkillInsteadSection(pd);
								html += '<hr style="margin:12px 0;">';
								html += '<div style="margin-top:12px;">'
									+ '<button type="button" id="rstat_confirm_btn" '
									+ 'style="padding:10px 24px; background:#4CAF50; color:white; border:none; border-radius:4px; cursor:pointer; font-size:15px;" '
									+ 'onclick="confirmStatChoice(' + pd.id + ', ' + pd.statCost + ')">'
									+ 'Confirm Choice</button></div>';
								modalBody.innerHTML = html;
								modal.style.display = 'block';
							}
						}

						rollBtn.addEventListener('click', function() {
							var pd = getSelectedPlayerData();
							if (!pd) { alert('Please select a player.'); return; }

							// Confirmation popup
							if (!confirm(
								'Rolling a D8 stat increase for:\n\n'
								+ 'Player: ' + pd.name + '\n'
								+ 'SPP cost: ' + pd.statCost + ' SPP\n\n'
								+ 'This cannot be undone. Proceed?'
							)) return;

							currentStatData = pd.statData;
							rollBtn.disabled = true;
							rollBtn.textContent = 'Rolling...';
							var formData = new FormData();
							formData.append('player_id', pd.id);
							formData.append('spp_cost',  pd.statCost);
							fetch('lib/class_random_stat.php', {
								method: 'POST',
								body:   formData
							})
							.then(function(response) { return response.json(); })
							.then(function(data) {
								if (data.error) {
									alert('Error: ' + data.error);
									rollBtn.disabled = false;
									rollBtn.textContent = 'Roll D8 for Stat Increase';
									return;
								}
								var rd      = data.roll_data;
								var roll    = rd.roll;
								var offered = rd.offered;
								var isAny   = rd.is_any;
								// Store roll_id for reference (not used client-side but
								// confirms the server has recorded the roll)
								lastRoll = roll;
								var available = offered.filter(function(s) {
									return isStatAvailable(s, pd.statData);
								});
								var html = '<p style="font-size:15px;">' + (data.pending
									? '&#9888; You have an existing pending roll: <strong>D8 = ' + roll + '</strong></p>'
									: '&#127922; D8 roll: <strong>' + roll + '</strong></p>');
								if (!isAny) {
									html += '<p>Offered stat(s): <strong>'
										+ offered.map(function(s){ return STAT_NAMES[s]; }).join(' or ')
										+ '</strong></p>';
								} else {
									html += '<p>Result: <strong>Any</strong> — choose any available stat.</p>';
								}
								if (available.length === 0) {
									html += '<p style="color:#d32f2f;"><strong>All offered stats are at cap. ';
									html += 'You must choose a skill instead.</strong></p>';
								}
								html += '<hr style="margin:12px 0;">';
								if (available.length > 0) {
									html += '<p style="font-weight:500; margin-bottom:8px;">Choose a stat increase:</p>';
									window.rstatSelectedStat = null;
									available.forEach(function(s) {
										var displayName = STAT_NAMES[s];
										var desc = getStatDesc(s);
										html += '<div id="rstat_stat_' + s + '" ';
										html += 'style="margin:8px 0; padding:12px 15px; border:2px solid #ddd; ';
										html += 'border-radius:5px; cursor:pointer;" ';
										html += 'onclick="selectStatOption(\'' + s + '\')">';
										html += '<strong style="font-size:16px;">+ ' + displayName + '</strong>';
										html += '<span style="color:#666; font-size:13px; margin-left:10px;">' + desc + '</span>';
										html += '<div style="font-size:12px; color:#888; margin-top:4px;">';
										html += pd.statCost + ' SPP &nbsp;|&nbsp; improved ' + pd.statData[s].improvements + '/2 times';
										html += '</div></div>';
									});
								}
								html += '<hr style="margin:12px 0;">';
								html += '<p style="font-weight:500; margin-bottom:8px;">Or choose a skill instead:</p>';
								html += buildSkillInsteadSection(pd);
								html += '<hr style="margin:12px 0;">';
								html += '<div style="margin-top:12px;">';
								html += '<button type="button" id="rstat_confirm_btn" ';
								html += 'style="padding:10px 24px; background:#4CAF50; color:white; ';
								html += 'border:none; border-radius:4px; cursor:pointer; font-size:15px;" ';
								html += 'onclick="confirmStatChoice(' + pd.id + ', ' + pd.statCost + ')">';
								html += 'Confirm Choice</button>';
								html += '</div>';
								modalBody.innerHTML = html;
								modal.style.display = 'block';
								rollLocked  = true;
								rollBtn.disabled = true;
								rollBtn.textContent = 'Roll made — choose above';
							})
							.catch(function() {
								alert('Network error contacting server. Please try again.');
								rollBtn.disabled = false;
								rollBtn.textContent = 'Roll D8 for Stat Increase';
							});
						});

						function getStatDesc(s) {
							var descs = { ma:'Movement Allowance', st:'Strength', ag:'Agility', pa:'Passing Ability', av:'Armour Value' };
							return descs[s] || '';
						}

						function buildSkillInsteadSection(pd) {
							var catNames = { G:'General', A:'Agility', S:'Strength', P:'Passing', D:'Devious', M:'Mutation' };
							var normByCat = JSON.parse(playerSelect.options[playerSelect.selectedIndex].getAttribute('data-normbycat') || '{}');
							var doubByCat = JSON.parse(playerSelect.options[playerSelect.selectedIndex].getAttribute('data-doubbycat') || '{}');

							var html = '<select id="rstat_skill_instead" style="width:100%; margin-top:4px; padding:4px;">';
							html += '<option value="">-- Select a skill instead (optional) --</option>';

							// Primary categories
							var primary = pd.primary || '';
							for (var i = 0; i < primary.length; i++) {
								var cat = primary.charAt(i);
								if (!catNames[cat] || !normByCat[cat] || normByCat[cat].length === 0) continue;
								html += '<optgroup label="' + catNames[cat] + ' (Primary)">';
								normByCat[cat].forEach(function(skill) {
									html += '<option value="' + skill.id + '|N|' + pd.statCost + '">' + skill.name + '</option>';
								});
								html += '</optgroup>';
							}

							// Secondary categories
							var secondary = pd.secondary || '';
							for (var j = 0; j < secondary.length; j++) {
								var scat = secondary.charAt(j);
								if (!catNames[scat] || !doubByCat[scat] || doubByCat[scat].length === 0) continue;
								html += '<optgroup label="' + catNames[scat] + ' (Secondary)">';
								doubByCat[scat].forEach(function(skill) {
									html += '<option value="' + skill.id + '|D|' + pd.statCost + '">' + skill.name + '</option>';
								});
								html += '</optgroup>';
							}

							html += '</select>';
							return html;
						}
						
						window.selectStatOption = function(statKey) {
							// Deselect all stat boxes
							var STAT_NAMES = { ma:'MA', st:'ST', ag:'AG', pa:'PA', av:'AV' };
							Object.keys(STAT_NAMES).forEach(function(s) {
								var el = document.getElementById('rstat_stat_' + s);
								if (el) { el.style.borderColor = '#ddd'; el.style.background = 'white'; }
							});
							// Highlight selected
							var selected = document.getElementById('rstat_stat_' + statKey);
							if (selected) { selected.style.borderColor = '#4CAF50'; selected.style.background = '#f0f9f0'; }
							// Store selection and clear skill dropdown
							window.rstatSelectedStat = statKey;
							var skillSelect = document.getElementById('rstat_skill_instead');
							if (skillSelect) skillSelect.value = '';
						};

						window.confirmStatChoice = function(playerId, sppCost) {
							var skillSelect = document.getElementById('rstat_skill_instead');
							var skillVal = skillSelect ? skillSelect.value : '';

							if (window.rstatSelectedStat) {
								var statLabels = { ma:'MA', st:'ST', ag:'AG', pa:'PA', av:'AV' };
								var statName = statLabels[window.rstatSelectedStat] || window.rstatSelectedStat;
								if (!confirm('You selected: +' + statName + ' stat increase\nSPP cost: ' + sppCost + '\n\nAre you sure? This cannot be undone.')) {
									return;
								}
								document.getElementById('rstat_final_player').value = playerId;
								document.getElementById('rstat_final_stat').value   = window.rstatSelectedStat;
								document.getElementById('rstat_final_cost').value   = sppCost;
								document.getElementById('rstat_final_type').value   = 'apply_random_stat';
								document.getElementById('rstat_final_form').submit();
							} else if (skillVal) {
								var parts     = skillVal.split('|');
								var skillId   = parts[0];
								var skillType = parts[1];
								// Get the skill name from the dropdown option text
								var skillName = skillSelect.options[skillSelect.selectedIndex].text;
								var typeLabel = (skillType === 'D') ? 'Secondary' : 'Primary';
								if (!confirm('You selected: ' + skillName + ' (' + typeLabel + ' skill)\nSPP cost: ' + sppCost + '\n\nAre you sure? This cannot be undone.')) {
									return;
								}
								document.getElementById('rstat_final_player').value    = playerId;
								document.getElementById('rstat_final_stat').value      = '';
								document.getElementById('rstat_final_skill_id').value  = skillId;
								document.getElementById('rstat_final_skilltype').value = skillType;
								document.getElementById('rstat_final_cost').value      = sppCost;
								document.getElementById('rstat_final_type').value      = 'apply_random_stat_skill';
								document.getElementById('rstat_final_form').submit();
							} else {
								alert('Please either click a stat increase above, or choose a skill from the dropdown.');
							}
						};

						// Prevent closing modal by clicking outside once roll is locked
						modal.addEventListener('click', function(e) {
							if (e.target === modal && rollLocked) {
								alert('You must make a choice — select a stat or a skill to proceed.');
							}
						});
					})();
					</script>
					<?php
					$DISABLE = true; // OK button hidden — all submission via JS
					break;
	
				/**************
				 * Expensive Mistakes
				 **************/
				case 'expensive_mistakes':
				require_once('lib/class_expensive_mistakes.php');
				
				if (ExpensiveMistakes::shouldShowOption($team->team_id)) {				
					echo $lng->getTrn('profile/team/box_tm/desc/expensive_mistakes');
					?>
					<hr><br>
					
					<div style="background-color: #fffbea; border: 2px solid #f0ad4e; padding: 15px; margin-bottom: 15px;">
						<h4 style="margin-top: 0;">Expensive Mistakes Table</h4>
						<p><b>Current Treasury:</b> <?php echo $team->treasury/1000; ?>k</p>
						
						<div class='tableResponsive'>
						<table border="1" cellpadding="5" cellspacing="0" style="margin: 10px 0; background: white; border-collapse: collapse;">
							<tr style="background-color: #f0f0f0;">
								<th>D6</th>
								<th>100k-195k</th>
								<th>200k-295k</th>
								<th>300k-395k</th>
								<th>400k-495k</th>
								<th>500k-595k</th>
								<th>600k+</th>
							</tr>
							<?php
							$em_table = ExpensiveMistakes::$EM_TABLE;
							for ($i = 1; $i <= 6; $i++) {
								echo "<tr>";
								echo "<td style='text-align:center;'><b>$i</b></td>";
								$brackets = array('100k-195k', '200k-295k', '300k-395k', '400k-495k', '500k-595k', '600k+');
								foreach ($brackets as $bracket) {
									$outcome = $em_table[$i][$bracket];
									$color = '';
									if ($outcome == 'Crisis Averted') $color = 'background-color: #dff0d8;';
									if ($outcome == 'Minor Incident') $color = 'background-color: #fcf8e3;';
									if ($outcome == 'Major Incident') $color = 'background-color: #f2dede;';
									if ($outcome == 'Catastrophe') $color = 'background-color: #d9534f; color: white; font-weight: bold;';
									
									echo "<td style='$color text-align:center;'>$outcome</td>";
								}
								echo "</tr>";
							}
							?>
						</table>
						</div>
						
						<div style="margin: 10px 0; padding: 10px; background: #d9edf7; border-left: 4px solid #31708f;">
							<b>Outcomes:</b><br>
							• <b>Crisis Averted:</b> No treasury lost<br>
							• <b>Minor Incident:</b> Lose D3 × 10k gold<br>
							• <b>Major Incident:</b> Lose half of treasury (rounded down to nearest 5k)<br>
							• <b>Catastrophe:</b> Lose all treasury except 2D6 × 10k
						</div>
					</div>
					
					<input type="hidden" name="type" value="expensive_mistakes">
					<?php
				} else {
					echo $lng->getTrn('profile/team/box_tm/desc/no_expensive_mistakes') . ($team->treasury/1000) . "k</p>";
					$DISABLE = true;
				}
				break;
				
				/**************
				 * Temporary retire player
				 **************/
				case 'retire_player':
					echo $lng->getTrn('profile/team/box_tm/desc/retire_player');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/player');?>:<br>
					<select name="player">
					<?php
					$DISABLE = true;
					foreach ($players as $p) {
						if ($p->is_dead || $p->is_sold || $p->is_retired || !$p->can_retire)
							continue;

						echo "<option value='$p->player_id'>$p->nr $p->name</option>\n";
						$DISABLE = false;
					}
					?>
					</select>
					<input type="hidden" name="type" value="retire_player">
					<?php
					break;
				/**************
				 * Rename team
				 **************/
				case 'rename_team':
					echo $lng->getTrn('profile/team/box_tm/desc/rename_team');
					?>
					<hr><br>
					<?php echo $lng->getTrn('common/name');?>:<br>
					<input type='text' name='name' maxlength='50' size='20'>
					<input type="hidden" name="type" value="rename_team">
					<?php
					break;
				/**************
				 * Buy team goods
				 **************/
				case 'buy_goods':
					echo $lng->getTrn('profile/team/box_tm/desc/buy_goods');
					$goods_temp = $team->getGoods();
					if ($DEA[$team->f_rname]['other']['rr_cost'] != $goods_temp['rerolls']['cost'] && $team->format != 'SV') {
						echo $lng->getTrn('profile/team/box_tm/desc/buy_goods_warn');
					}
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/thing');?>:<br>
					<select name="thing">
					<?php
					$DISABLE = true;
					foreach ($team->getGoods() as $name => $details) {
						if ($name == 'ff_bought' && !$team->mayBuyFF())
							continue;
						if ($team->format =='SV' && $name == 'rerolls' && !$team->mayBuyRR())
							continue;
						if (($team->$name < $details['max'] || $details['max'] == -1) && $team->treasury >= $details['cost']) {
							echo "<option value='$name'>" . $details['cost']/1000 . "k | $details[item]</option>\n";
							$DISABLE = false;
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="buy_goods">
					<?php
					break;
				/**************
				 * Let go (drop) of team goods
				 **************/
				case 'drop_goods':
					echo $lng->getTrn('profile/team/box_tm/desc/drop_goods');
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/thing');?>:<br>
					<select name="thing">
					<?php
					$DISABLE = true;
					foreach ($team->getGoods() as $name => $details) {
						if ($name == 'ff_bought' && !$team->mayBuyFF())
							continue;
						if ($name == 'rerolls' && !$team->mayDropRR())
							continue;
						if ($team->$name > 0) {
							echo "<option value='$name'>$details[item]</option>\n";
							$DISABLE = false;
						}
					}
					?>
					</select>
					<input type="hidden" name="type" value="drop_goods">
					<?php
					break;
				/**************
				 * Set ready state
				 **************/
				case 'ready_state':
					echo $lng->getTrn('profile/team/box_tm/desc/ready_state');
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/teamready');?>
					<input type="checkbox" name="bool" value="1" <?php echo ($team->rdy) ? 'CHECKED' : '';?>>
					<input type="hidden" name="type" value="ready_state">
					<?php
					break;
				/***************
				 * Retire
				 **************/
				case 'retire':
					echo $lng->getTrn('profile/team/box_tm/desc/retire');
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/retire');?>
					<input type="checkbox" name="bool" value="1">
					<input type="hidden" name="type" value="retire">
					<?php
					break;
				/***************
				 * Delete
				 **************/
				case 'delete':
					echo $lng->getTrn('profile/team/box_tm/desc/delete');
					if (!$this->isDeletable()) {
						$DISABLE = true;
					}
					?>
					<hr><br>
					<?php echo $lng->getTrn('profile/team/box_tm/fdescs/suredeleteteam');?>
					<input type="checkbox" name="bool" value="1" <?php echo ($DISABLE) ? 'DISABLED' : '';?>>
					<input type="hidden" name="type" value="delete">
					<?php
					break;
				}
				?>
				<br><br>
				<input type="submit" name="button" id="form_ok_button" value="OK" <?php echo ($DISABLE ? 'DISABLED' : '');?>
					<?php if (in_array($_POST['menu_tmanage'], $tmange_confirm)) {echo "onClick=\"if(!confirm('".$lng->getTrn('common/confirm_box')."')){return false;}\"";}?>
				>
				<?php if(Mobile::isMobile()) {
					echo '<a href="' . getFormAction('') . '">' . $lng->getTrn('common/back') . '</a>';
				} ?>
			</form>
		</div>
	</div>
	<!-- Hidden form to submit final selection -->
	<form id="final_skill_form" method="post" style="display:none;">
		<input type="hidden" name="player_id" id="final_player_id">
		<input type="hidden" name="skill_id" id="final_skill_id">
		<input type="hidden" name="skill_type" id="final_skill_type">
		<input type="hidden" name="type" value="apply_random_skill">
	</form>
	<form id="rstat_final_form" method="post" style="display:none;">
		<input type="hidden" name="player"         id="rstat_final_player">
		<input type="hidden" name="stat_key"       id="rstat_final_stat">
		<input type="hidden" name="skill_id"       id="rstat_final_skill_id">
		<input type="hidden" name="skill_type"     id="rstat_final_skilltype">
		<input type="hidden" name="spp_cost"       id="rstat_final_cost">
		<input type="hidden" name="num_skills"     id="rstat_final_skills">
		<input type="hidden" name="type"           id="rstat_final_type">
	</form>
	<script>
	var _menuVal = document.querySelector('select[name="menu_tmanage"]')
		? document.querySelector('select[name="menu_tmanage"]').value : '';
	if (_menuVal === 'random_skill' || _menuVal === 'random_stat') {
		var okButton = document.getElementById('form_ok_button');
		if (okButton) okButton.style.display = 'none';
	}
	</script>
	<?php
	}

	private function _about($ALLOW_EDIT) {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('common/about') . '</div>');
		?>
		<!-- Following HTML is from class_team_htmlout.php _about -->
		<div class='tableResponsive'>
		<table class='common teamAbout'>

			<tr class='commonhead'>
				<td><b><?php echo $lng->getTrn('profile/team/logo');?></b></td>
				<td><b><?php echo $lng->getTrn('profile/team/stad');?></b></td>
				<td><b><?php echo $lng->getTrn('common/about');?></b></td>
			</tr>
			<tr>
				<td>
					<?php
					ImageSubSys::makeBox(IMGTYPE_TEAMLOGO, $team->team_id, $ALLOW_EDIT, '_logo');
					?>
				</td>
				<td>
					<?php
					ImageSubSys::makeBox(IMGTYPE_TEAMSTADIUM, $team->team_id, $ALLOW_EDIT, '_stad');
					?>
				</td>
				<td valign='top'  rowspan="3" style='width: 100%;'>
					<?php
					$txt = $team->getText();
					if (empty($txt)) {
						$txt = $lng->getTrn('common/nobody');
					}

					if ($ALLOW_EDIT) {
						?>
						<form method='POST'>
							<textarea name='teamtext' rows='15' style='width: 100%;'><?php echo $txt;?></textarea>
							<br><br>
							<input type="hidden" name="type" value="teamtext">
							<center>
							<input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
							</center>
						</form>
						<?php
					}
					else {
						echo '<p>'.fmtprint($txt)."</p>\n";
					}
					?>
				</td>
			</tr>
			<tr class='commonhead'>
				<td><b><?php echo $lng->getTrn('profile/team/sponsor');?></b></td>
				<td><b><?php echo $lng->getTrn('profile/team/stadium');?></b></td>			
			</tr>
			<tr>
				<td valign='top'>
					<?php
					$txt = $team->getSponsor();
					if (empty($txt)) {
						$txt = $lng->getTrn('profile/team/nosponsor');
					}

					if ($ALLOW_EDIT) {
						?>
						<form method='POST'>
							<input type="text"  name='teamsponsor' value='<?php echo $txt;?>'></text>
							<br><br>
							<input type="hidden" name="type" value="teamsponsor">
							<input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
						</form>
						<?php
					}
					else {
						echo '<p>'.fmtprint($txt)."</p>\n";
					}
					?>
				</td>
				<td valign='top'>
					<?php
					$txt = $team->getStadium();
					if (empty($txt)) {
						$txt = $lng->getTrn('profile/team/nostadium');
					}

					if ($ALLOW_EDIT) {
						?>
						<form method='POST'>
							<input type="text" name='teamstadium' value='<?php echo $txt;?>'></text>
							<br><br>
							<input type="hidden" name="type" value="teamstadium">
							<input type="submit" name='Save' value='<?php echo $lng->getTrn('common/save');?>'>
						</form>
						<?php
					}
					else {
						echo '<p>'.fmtprint($txt)."</p>\n";
					}
					?>
				</td>
			</tr>
		</table>
		</div>
		<?php
	}

	private function _news($ALLOW_EDIT) {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('profile/team/news') . '</div>');
		$news = $team->getNews(MAX_TNEWS);
		?>
		<!-- Following HTML is from class_team_htmlout.php _news -->
		<div class="row">
			<div class="boxWide">
				<div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn('profile/team/tnews');?></div>
				<div class="boxBody">
				<?php
				$news_2 = array();
				foreach ($news as $n) {
					$news_2[] = '<p>'.fmtprint($n->txt).
					'<div id="newsedit'.$n->news_id.'" style="display:none; clear:both;"><form method="POST">
						<textarea name="txt" cols="60" rows="4">'.$n->txt.'</textarea>
						<input type="hidden" name="type" value="newsedit">
						<input type="hidden" name="news_id" value="'.$n->news_id.'">
						<br><br>
						<input type="submit" value="'.$lng->getTrn('common/submit').'">
					</form></div>
					<div style="text-align: right;"><p style="display: inline;">'.textdate($n->date, true).
					(($ALLOW_EDIT)
						? '&nbsp;'.inlineform(array('type' => 'newsdel', 'news_id' => $n->news_id), "newsForm$n->news_id", $lng->getTrn('common/delete')).
							"&nbsp; <a href='javascript:void(0);' onClick=\"slideToggle('newsedit".$n->news_id."');\">".$lng->getTrn('common/edit')."</a>"
						: '')
					.'</p></div><br></p>';
				}
				echo implode("<hr>\n", $news_2);
				if (empty($news)) {
					echo '<i>'.$lng->getTrn('profile/team/nonews').'</i>';
				}
				if ($ALLOW_EDIT) {
					?>
					<hr>
					<br>
					<b><?php echo $lng->getTrn('profile/team/wnews');?></b>
					<form method="POST">
						<textarea name='txt' cols='60' rows='4'></textarea>
						<br><br>
						<input type="hidden" name="type" value="news">
						<input type='submit' value="<?php echo $lng->getTrn('common/submit');?>">
					</form>
					<?php
				}
				?>
				</div>
			</div>
		</div>
		<?php
	}

	private function _games() {
		global $lng;
		$team = $this; // Copy. Used instead of $this for readability.
		title('<div class="team-management-title">' . $lng->getTrn('profile/team/games') . '</div>');
		HTMLOUT::recentGames(T_OBJ_TEAM, $team->team_id, false, false, false, false, array('url' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=games', 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'gp'));
		echo "<br>";
		HTMLOUT::upcomingGames(T_OBJ_TEAM, $team->team_id, false, false, false, false, array('url' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false).'&amp;subsec=games', 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'ug'));
	}
}