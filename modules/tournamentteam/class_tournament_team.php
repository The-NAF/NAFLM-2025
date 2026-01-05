<?php
/*
 *  Tournament Team Creator Module
 *  
 */

class TournamentTeam implements ModuleInterface
{
    public static function main($argv)
	{
		$func = array_shift($argv);
		return call_user_func_array(array(__CLASS__, $func), $argv);
	}

    public static function getModuleAttributes()
	{
		return array(
			'author'     => 'Val Catella',
			'moduleName' => 'Tournament Team Builder',
			'date'       => '2025',
			'setCanvas'  => true,  // Always use canvas - we'll bypass it in pdf()
		);
	}

    public static function getModuleTables()
    {
        return array();
    }

    public static function getModuleUpgradeSQL()
    {
        return array();
    }

    public static function triggerHandler($type, $argv)
    {
    }

    public static function builder($argv = array())
    {
        global $raceididx, $DEA, $stars, $rules, $racesNoApothecary, $inducements, $lng, $skillarray, $IllegalSkillCombinations, $playerkeywordsididx, $playerkeywordsarray;

        if (!session_id()) {
            session_start();
        }

        // Handle form submission
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'reset') {
                unset($_SESSION['tournament_team']);
                $team_data = array();
				$team_data['inducements_expanded'] = 1; // Set default to expanded
            } elseif ($_POST['action'] == 'save_team') {
                $validation_error = self::saveTeamToSession();
                if ($validation_error) {
                    $team_data = self::getTeamDataFromPost();
                    echo "<div style='color: red; font-weight: bold; padding: 10px; background: #fee; border: 2px solid red; margin: 20px;'>ERROR: $validation_error</div>";
                } else {
                    $team_data = isset($_SESSION['tournament_team']) ? $_SESSION['tournament_team'] : array();
                }
            } else {
                $team_data = isset($_SESSION['tournament_team']) ? $_SESSION['tournament_team'] : array();
            }
        } else {
            $team_data = isset($_SESSION['tournament_team']) ? $_SESSION['tournament_team'] : array();
        }

        self::displayBuilder($team_data);
    }

    public static function getTeamDataFromPost()
    {
        global $raceididx;
        
        $team_data = array();
        $team_data['team_name'] = isset($_POST['team_name']) ? $_POST['team_name'] : '';
        $team_data['coach_name'] = isset($_POST['coach_name']) ? $_POST['coach_name'] : '';
        $team_data['naf_number'] = isset($_POST['naf_number']) ? $_POST['naf_number'] : '';
        $team_data['race_id'] = isset($_POST['race_id']) ? intval($_POST['race_id']) : -1;
		$team_data['selected_league'] = isset($_POST['selected_league']) ? intval($_POST['selected_league']) : -1;
		$team_data['selected_fav_rule'] = isset($_POST['selected_fav_rule']) ? intval($_POST['selected_fav_rule']) : -1;

		if ($team_data['race_id'] >= 0) {
			$team_data['race_name'] = $raceididx[$team_data['race_id']];
		}
        
        $team_data['players'] = array();
        if (isset($_POST['players']) && is_array($_POST['players'])) {
            foreach ($_POST['players'] as $idx => $player) {
                if (!empty($player['position'])) {
                    $p = array();
                    $p['nr'] = isset($player['nr']) ? $player['nr'] : ($idx + 1);
                    $p['position'] = $player['position'];
                    $p['is_star'] = (strpos($player['position'], 'STAR:') === 0);
                     $p['extra_skills'] = array();
                    if (isset($player['skills']) && is_array($player['skills'])) {
                        foreach ($player['skills'] as $skill) {
                            if (!empty($skill)) {
                                $p['extra_skills'][] = intval($skill);
                            }
                        }
                    }
                    
                    // Save stat increases
                    $p['stat_increases'] = array(
                        'ma' => 0,
                        'st' => 0,
                        'ag' => 0,
                        'pa' => 0,
                        'av' => 0
                    );
                    if (isset($_POST['player_stat_increases'][$idx]) && is_array($_POST['player_stat_increases'][$idx])) {
                        $stat_data = $_POST['player_stat_increases'][$idx];
                        $p['stat_increases']['ma'] = isset($stat_data['ma']) ? intval($stat_data['ma']) : 0;
                        $p['stat_increases']['st'] = isset($stat_data['st']) ? intval($stat_data['st']) : 0;
                        $p['stat_increases']['ag'] = isset($stat_data['ag']) ? intval($stat_data['ag']) : 0;
                        $p['stat_increases']['pa'] = isset($stat_data['pa']) ? intval($stat_data['pa']) : 0;
                        $p['stat_increases']['av'] = isset($stat_data['av']) ? intval($stat_data['av']) : 0;
                    }
                    
                    $team_data['players'][] = $p;
                }
            }
        }
        
        $team_data['sideline'] = array();
        $team_data['sideline']['rerolls'] = isset($_POST['sideline_rerolls']) ? intval($_POST['sideline_rerolls']) : 0;
        $team_data['sideline']['cheerleaders'] = isset($_POST['sideline_cheerleaders']) ? intval($_POST['sideline_cheerleaders']) : 0;
        $team_data['sideline']['ass_coaches'] = isset($_POST['sideline_coaches']) ? intval($_POST['sideline_coaches']) : 0;
        $team_data['sideline']['dedicated_fans'] = isset($_POST['sideline_fans']) ? intval($_POST['sideline_fans']) : 0;
        $team_data['sideline']['apothecary'] = isset($_POST['sideline_apothecary']) ? 1 : 0;
        $team_data['sideline']['free_first_df'] = isset($_POST['sideline_free_first_df']) ? 1 : 0;
        $team_data['inducements'] = array();
        if (isset($_POST['inducements']) && is_array($_POST['inducements'])) {
            foreach ($_POST['inducements'] as $ind_name => $quantity) {
                $qty = intval($quantity);
                if ($qty > 0) {
                    $team_data['inducements'][$ind_name] = $qty;
                }
            }
        }
        
		// Save inducements expanded state
		$team_data['inducements_expanded'] = isset($_POST['inducements_expanded']) ? intval($_POST['inducements_expanded']) : 1;
		// Save roster expanded state
		$team_data['roster_expanded'] = isset($_POST['roster_expanded']) ? intval($_POST['roster_expanded']) : 1;		
       // Tournament settings
		$team_data['tournament_settings'] = array();
		if (isset($_POST['tournament_settings']) && is_array($_POST['tournament_settings'])) {
			foreach ($_POST['tournament_settings'] as $key => $value) {
				$team_data['tournament_settings'][$key] = $value;
			}
		}
		// Save tournament settings expanded state
		$team_data['tournament_settings_expanded'] = isset($_POST['tournament_settings_expanded']) ? intval($_POST['tournament_settings_expanded']) : 0;
		
		return $team_data;
    }

    private static function saveTeamToSession()
    {
        global $DEA, $stars, $skillarray, $raceididx;
        
        $team_data = array();
        
        $team_data['team_name'] = isset($_POST['team_name']) ? $_POST['team_name'] : '';
        $team_data['coach_name'] = isset($_POST['coach_name']) ? $_POST['coach_name'] : '';
        $team_data['naf_number'] = isset($_POST['naf_number']) ? $_POST['naf_number'] : '';
        $team_data['race_id'] = isset($_POST['race_id']) ? intval($_POST['race_id']) : -1;
        
        if ($team_data['race_id'] < 0) {
            return null;
        }

        $team_data['race_name'] = $raceididx[$team_data['race_id']];
		$team_data['selected_league'] = isset($_POST['selected_league']) ? intval($_POST['selected_league']) : -1;
		$team_data['selected_fav_rule'] = isset($_POST['selected_fav_rule']) ? intval($_POST['selected_fav_rule']) : -1;
        
        // Players validation
        $team_data['players'] = array();
        $positional_counts = array();
        $total_players = 0;
        $star_count = 0;
        $regular_player_count = 0;
        $bigguy_count = 0;
        
        if (isset($_POST['players']) && is_array($_POST['players'])) {
            foreach ($_POST['players'] as $idx => $player) {
                if (!empty($player['position'])) {
                    $total_players++;
                    
                    if ($total_players > 16) {
                        return "Maximum 16 players allowed (including stars)!";
                    }
                    
                    $p = array();
                    $p['nr'] = isset($player['nr']) ? $player['nr'] : ($idx + 1);
                    $p['position'] = $player['position'];
                    $p['is_star'] = (strpos($player['position'], 'STAR:') === 0);
                    
                    if ($p['is_star']) {
                        $star_count++;
                        if ($star_count > 2) {
                            return "Maximum 2 star players allowed!";
                        }
                    } else {
                        $regular_player_count++;
                        
                        if (!isset($positional_counts[$player['position']])) {
                            $positional_counts[$player['position']] = 0;
                        }
                        $positional_counts[$player['position']]++;
                        
                        if (isset($DEA[$team_data['race_name']]['players'][$player['position']])) {
                            $pos_data = $DEA[$team_data['race_name']]['players'][$player['position']];
                            $max_qty = $pos_data['qty'];
                            
                            if ($positional_counts[$player['position']] > $max_qty) {
                                return "Too many " . $player['position'] . "! Maximum is " . $max_qty;
                            }
                            
                            $is_bigguy = isset($pos_data['is_bigguy']) ? $pos_data['is_bigguy'] : 0;
                            if ($is_bigguy == 1) {
                                $bigguy_count++;
                                $bigguy_max = isset($DEA[$team_data['race_name']]['other']['bigguy_qty']) ? $DEA[$team_data['race_name']]['other']['bigguy_qty'] : 0;
                                if ($bigguy_count > $bigguy_max) {
                                    return "Too many Big Guys! Maximum is " . $bigguy_max;
                                }
                            }
                        }
                    }
                    
                    $p['extra_skills'] = array();
                    if (isset($player['skills']) && is_array($player['skills'])) {
                        foreach ($player['skills'] as $skill) {
                            if (!empty($skill)) {
                                $p['extra_skills'][] = intval($skill);
                            }
                        }
                    }
                    
                    // Save stat increases
                    $p['stat_increases'] = array(
                        'ma' => 0,
                        'st' => 0,
                        'ag' => 0,
                        'pa' => 0,
                        'av' => 0
                    );
                    if (isset($_POST['player_stat_increases'][$idx]) && is_array($_POST['player_stat_increases'][$idx])) {
                        $stat_data = $_POST['player_stat_increases'][$idx];
                        $p['stat_increases']['ma'] = isset($stat_data['ma']) ? intval($stat_data['ma']) : 0;
                        $p['stat_increases']['st'] = isset($stat_data['st']) ? intval($stat_data['st']) : 0;
                        $p['stat_increases']['ag'] = isset($stat_data['ag']) ? intval($stat_data['ag']) : 0;
                        $p['stat_increases']['pa'] = isset($stat_data['pa']) ? intval($stat_data['pa']) : 0;
                        $p['stat_increases']['av'] = isset($stat_data['av']) ? intval($stat_data['av']) : 0;
                    }
                    
                    $team_data['players'][] = $p;
                }
            }
        }
		
		// Check Insignificant rule
		$players_with_insignificant = 0;
		$players_without_insignificant = 0;

		foreach ($team_data['players'] as $player) {
			$position = $player['position'];
			$has_insignificant = false;
			
			if ($player['is_star']) {
				$star_name = substr($position, 5);
				if (isset($stars[$star_name]['def']) && is_array($stars[$star_name]['def'])) {
					if (in_array(105, $stars[$star_name]['def'])) {  // 105 is Insignificant
						$has_insignificant = true;
					}
				}
			} else {
				if (isset($DEA[$race_name]['players'][$position]['def']) && is_array($DEA[$race_name]['players'][$position]['def'])) {
					if (in_array(105, $DEA[$race_name]['players'][$position]['def'])) {  // 105 is Insignificant
						$has_insignificant = true;
					}
				}
			}
			
			if ($has_insignificant) {
				$players_with_insignificant++;
			} else {
				$players_without_insignificant++;
			}
		}

		if ($players_with_insignificant > $players_without_insignificant) {
			return "Insignificant Rule: You cannot have more players with Insignificant (" . $players_with_insignificant . 
				   ") than players without it (" . $players_without_insignificant . ")";
		}
				
        // Check 11 player minimum (can now include stars)
		$total_players_for_minimum = $regular_player_count + $star_count;
		if ($total_players_for_minimum < 11) {
			return "You must have at least 11 players total (including stars). You currently have " . $total_players_for_minimum . " players (" . $regular_player_count . " regular, " . $star_count . " stars).";
		}
		
		// Check if tournament enforces 11 regular players (excluding stars)
		if (isset($_POST['tournament_settings']['enforce_11_regular_players']) && $_POST['tournament_settings']['enforce_11_regular_players']) {
			if ($regular_player_count < 11) {
				return "Tournament rule: You must have at least 11 REGULAR players (not including stars). You currently have " . $regular_player_count . " regular players.";
			}
		}
				
        // Sideline items
        $team_data['sideline'] = array();
        $team_data['sideline']['rerolls'] = isset($_POST['sideline_rerolls']) ? intval($_POST['sideline_rerolls']) : 0;
        $team_data['sideline']['cheerleaders'] = isset($_POST['sideline_cheerleaders']) ? intval($_POST['sideline_cheerleaders']) : 0;
        $team_data['sideline']['ass_coaches'] = isset($_POST['sideline_coaches']) ? intval($_POST['sideline_coaches']) : 0;
        $team_data['sideline']['dedicated_fans'] = isset($_POST['sideline_fans']) ? intval($_POST['sideline_fans']) : 0;
        $team_data['sideline']['apothecary'] = isset($_POST['sideline_apothecary']) ? 1 : 0;
        $team_data['sideline']['free_first_df'] = isset($_POST['sideline_free_first_df']) ? 1 : 0;
        // Inducements
        $team_data['inducements'] = array();
        if (isset($_POST['inducements']) && is_array($_POST['inducements'])) {
            foreach ($_POST['inducements'] as $ind_name => $quantity) {
                $qty = intval($quantity);
                if ($qty > 0) {
                    $team_data['inducements'][$ind_name] = $qty;
                }
            }
        }
        
		// Save inducements expanded state
		$team_data['inducements_expanded'] = isset($_POST['inducements_expanded']) ? intval($_POST['inducements_expanded']) : 1;
		// Save roster expanded state
		$team_data['roster_expanded'] = isset($_POST['roster_expanded']) ? intval($_POST['roster_expanded']) : 1;
		// Tournament settings
        $team_data['tournament_settings'] = array();
        if (isset($_POST['tournament_settings']) && is_array($_POST['tournament_settings'])) {
            foreach ($_POST['tournament_settings'] as $key => $value) {
                $team_data['tournament_settings'][$key] = $value;
            }
        }       
        // Save tournament settings expanded state
        $team_data['tournament_settings_expanded'] = isset($_POST['tournament_settings_expanded']) ? intval($_POST['tournament_settings_expanded']) : 0;
		
        $_SESSION['tournament_team'] = $team_data;
        return null;
    }

    private static function displayBuilder($team_data)
	{
		global $raceididx, $DEA, $stars, $rules, $lng, $skillarray, $inducements, $racesNoApothecary, $IllegalSkillCombinations, $starpairs, $playerkeywordsarray;
		
		// Set page title
		title("Tournament Team Builder (BETA)");
		echo '<div style="max-width: 1400px; margin: 0 auto 20px auto;">';
		echo '<p>Create a tournament team with unlimited budget and skills by default, or add limitations in Settings below. No database persistence - PDF output only.</p>';
		echo '</div>';
	
        $race_id = isset($team_data['race_id']) ? $team_data['race_id'] : -1;
        $race_name = ($race_id >= 0) ? $raceididx[$race_id] : '';
        
        $team_name_val = isset($team_data['team_name']) ? htmlspecialchars($team_data['team_name']) : '';
        $coach_name_val = isset($team_data['coach_name']) ? htmlspecialchars($team_data['coach_name']) : '';
        $naf_number_val = isset($team_data['naf_number']) ? htmlspecialchars($team_data['naf_number']) : '';

        // Calculate team value and skill counts
        $team_value = 0;
        $primary_skills_count = 0;
        $secondary_skills_count = 0;
        $elite_skills_count = 0;
        $player_count = 0;
        $star_count = 0;
        $regular_player_count = 0;
        
        // Elite skill IDs: Block(1), Dodge(23), Guard(52), Mighty Blow(54)
		$elite_skill_ids = array(1, 23, 52, 54);
        
        if (isset($team_data['players']) && is_array($team_data['players'])) {
            foreach ($team_data['players'] as $player) {
                $player_count++;
                $position = $player['position'];
                
                if (strpos($position, 'STAR:') === 0) {
                    $star_count++;
                    $star_name = substr($position, 5);
                    if (isset($stars[$star_name])) {
                        $team_value += $stars[$star_name]['cost'];
                    }
                } else {
                    $regular_player_count++;
                    if (isset($DEA[$race_name]['players'][$position])) {
                        $team_value += $DEA[$race_name]['players'][$position]['cost'];
                        
                        if (isset($player['extra_skills']) && is_array($player['extra_skills'])) {
                            $pos_data = $DEA[$race_name]['players'][$position];
                            $primary_cats = isset($pos_data['norm']) ? $pos_data['norm'] : array();
                            
                            foreach ($player['extra_skills'] as $skill_id) {
                                // Check if elite skill
                                if (in_array($skill_id, $elite_skill_ids)) {
                                    $elite_skills_count++;
                                }
                                
                                $is_primary = false;
                                foreach ($primary_cats as $cat) {
                                    if (isset($skillarray[$cat][$skill_id])) {
                                        $is_primary = true;
                                        break;
                                    }
                                }
                                
                                if ($is_primary) {
                                    $primary_skills_count++;
                                } else {
                                    $secondary_skills_count++;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Add sideline items to team value
        if (isset($team_data['sideline'])) {
            $sl = $team_data['sideline'];
            
            if ($race_id >= 0 && isset($DEA[$race_name])) {
                if (isset($sl['rerolls']) && $sl['rerolls'] > 0) {
                    $team_value += $DEA[$race_name]['other']['rr_cost'] * $sl['rerolls'];
                }
            }
            
            if (isset($sl['dedicated_fans']) && $sl['dedicated_fans'] > 1) {
                $team_value += $rules['cost_fan_factor'] * ($sl['dedicated_fans'] - 1);
            }
            
            if (isset($sl['cheerleaders']) && $sl['cheerleaders'] > 0) {
                $team_value += $rules['cost_cheerleaders'] * $sl['cheerleaders'];
            }
            
            if (isset($sl['ass_coaches']) && $sl['ass_coaches'] > 0) {
                $team_value += $rules['cost_ass_coaches'] * $sl['ass_coaches'];
            }
            
            if (isset($sl['apothecary']) && $sl['apothecary'] > 0) {
                $team_value += $rules['cost_apothecary'];
            }
        }
        
        // Add inducements
        if (isset($team_data['inducements'])) {
            foreach ($team_data['inducements'] as $ind_name => $qty) {
                $ind_name_display = str_replace('_', ' ', $ind_name);
                if (isset($inducements[$ind_name_display])) {
                    $team_value += $inducements[$ind_name_display]['cost'] * $qty;
                }
            }
        }

        // Get team special rules for inducement pricing
        $team_special_rules = array();
        if ($race_id >= 0 && isset($DEA[$race_name]['other']['special_rules'])) {
            $team_special_rules = $DEA[$race_name]['other']['special_rules'];
        }

        echo '<script type="text/javascript">';
		echo 'var tournamentSettings = ' . json_encode(isset($team_data['tournament_settings']) ? $team_data['tournament_settings'] : array()) . ';';
		echo 'var currentRaceId = ' . $race_id . ';';
		echo 'var currentTeamRules = ' . json_encode($team_special_rules) . ';';
		echo 'var raceStats = {};';
		echo 'var starStats = {};';
		echo 'var allSkills = {};';
		echo 'var skillCategories = {};';
		echo 'var eliteSkillIds = [1, 23, 52, 54];';  // Block, Dodge, Guard, Mighty Blow		
		echo 'var gamePrices = ' . json_encode(array(
			'cheerleaders' => $rules['cost_cheerleaders'],
			'ass_coaches' => $rules['cost_ass_coaches'],
			'apothecary' => $rules['cost_apothecary'],
			'fan_factor' => $rules['cost_fan_factor']
		)) . ';';
		echo 'var gameMaxValues = ' . json_encode(array(
			'max_ass_coaches' => $rules['max_ass_coaches'],
			'max_cheerleaders' => $rules['max_cheerleaders'],
			'initial_fan_factor' => $rules['initial_fan_factor'],
			'max_ini_fan_factor' => $rules['max_ini_fan_factor'],
			'max_rerolls' => $rules['max_rerolls']
		)) . ';';

		// Skill validation data
		$illegal = isset($IllegalSkillCombinations) && is_array($IllegalSkillCombinations) ? $IllegalSkillCombinations : array();
		echo 'var skillConflicts = ' . json_encode($illegal) . ';';
		echo 'var skillRequirements = {58:[110], 63:[110], 32:[104], 37:[91,93,95,97,106,133]};';
		
		// Special rules and leagues data
		echo "\n// Special rules and leagues\n";
		global $specialrulesarray;
		echo 'var leagueNames = ' . json_encode($specialrulesarray['L']) . ";\n";
		echo 'var specialRuleNames = ' . json_encode($specialrulesarray['R']) . ";\n";

		// Race data
		echo 'var racePositions = {};';
		echo 'var racePrices = {};';
		echo 'var raceMaxQty = {};';
		echo 'var raceSkillCats = {};';
		echo 'var raceBaseSkills = {};';
		echo 'var raceBaseSkillIds = {};';  
		echo 'var raceIsBigGuy = {};';
		echo 'var raceBigGuyMax = {};';
		echo 'var raceSpecialRules = {};';
		echo 'var raceRerollCost = {};';
		echo 'var raceLeagues = {};';
		echo 'var raceFavRules = {};';
		echo 'var raceTiers = {};';
		echo 'var raceStats = {};';

		// Star data
		echo 'var starPlayers = {};';
		echo 'var starPrices = {};';
		echo 'var starBaseSkills = {};';
		echo 'var starBaseSkillIds = {};';
		echo 'var starStats = {};';
		
		echo 'var inducements = {};';
		// Saved sideline values
		echo 'var savedSideline = ' . json_encode(isset($team_data['sideline']) ? $team_data['sideline'] : array()) . ';';
		// Saved inducements  
		echo 'var savedInducements = ' . json_encode(isset($team_data['inducements']) ? $team_data['inducements'] : array()) . ';';
		echo 'var savedLeague = ' . (isset($team_data['selected_league']) ? $team_data['selected_league'] : -1) . ';';
		echo 'var savedFavRule = ' . (isset($team_data['selected_fav_rule']) ? $team_data['selected_fav_rule'] : -1) . ';';

        // Build JavaScript data structures
        echo "\n// Skill data with categories\n";
		$added_skills = array(); // Track which skill names we've already added
		foreach ($skillarray as $cat => $skills) {
			if ($cat == 'E') continue;
			foreach ($skills as $skill_id => $skill_name) {
				$skill_trans = skillsTrans($skill_id);
				$display_name = is_array($skill_trans) ? implode(', ', $skill_trans) : $skill_trans;
				
				// Only add if we haven't seen this skill name before
				if (!isset($added_skills[$display_name])) {
					$display_safe = str_replace("'", "\\'", $display_name);
					echo "allSkills[$skill_id] = '$display_safe';\n";
					echo "skillCategories[$skill_id] = '$cat';\n";
					$added_skills[$display_name] = $skill_id;
				}
			}
		}

        echo "\n// Race positions with prices, limits, and skills\n";
        foreach ($raceididx as $rid => $rname) {
            if (!isset($DEA[$rname])) continue;
            
            echo "racePositions[$rid] = [];\n";
            echo "racePrices[$rid] = {};\n";
            echo "raceMaxQty[$rid] = {};\n";
            echo "raceSkillCats[$rid] = {};\n";
            echo "raceBaseSkills[$rid] = {};\n";
            echo "raceIsBigGuy[$rid] = {};\n";
            
            $bigguy_max = isset($DEA[$rname]['other']['bigguy_qty']) ? $DEA[$rname]['other']['bigguy_qty'] : 0;
            echo "raceBigGuyMax[$rid] = $bigguy_max;\n";
            
            $race_rules = isset($DEA[$rname]['other']['special_rules']) ? $DEA[$rname]['other']['special_rules'] : array();
            echo "raceSpecialRules[$rid] = " . json_encode($race_rules) . ";\n";
			
			$team_leagues = isset($DEA[$rname]['other']['team_league']) ? $DEA[$rname]['other']['team_league'] : array();
			echo "raceLeagues[$rid] = " . json_encode($team_leagues) . ";\n";

			$fav_rules = isset($DEA[$rname]['other']['fav_rules']) ? $DEA[$rname]['other']['fav_rules'] : array();
			echo "raceFavRules[$rid] = " . json_encode($fav_rules) . ";\n";
			
			$tier = isset($DEA[$rname]['other']['tier']) ? $DEA[$rname]['other']['tier'] : '';
			echo "raceTiers[$rid] = $tier;\n";
            
            $rr_cost = isset($DEA[$rname]['other']['rr_cost']) ? ($DEA[$rname]['other']['rr_cost'] / 1000) : 0;
            echo "raceRerollCost[$rid] = $rr_cost;\n";
            
            foreach ($DEA[$rname]['players'] as $pos => $pdata) {
                $pos_safe = str_replace("'", "\\'", $pos);
                $price = $pdata['cost'] / 1000;
                $max_qty = isset($pdata['qty']) ? $pdata['qty'] : 16;
                $norm_cats = isset($pdata['norm']) ? json_encode($pdata['norm']) : '[]';
                $doub_cats = isset($pdata['doub']) ? json_encode($pdata['doub']) : '[]';
                $is_bigguy = isset($pdata['is_bigguy']) ? $pdata['is_bigguy'] : 0;
                
                $base_skills = isset($pdata['def']) ? $pdata['def'] : array();
                $base_skills_trans = skillsTrans($base_skills);
                $base_skills_str = is_array($base_skills_trans) ? implode(', ', $base_skills_trans) : $base_skills_trans;
                $base_skills_safe = str_replace("'", "\\'", $base_skills_str);	
                
                echo "racePositions[$rid].push({name: '$pos_safe', id: " . $pdata['pos_id'] . "});\n";
                echo "racePrices[$rid]['$pos_safe'] = $price;\n";
                echo "raceMaxQty[$rid]['$pos_safe'] = $max_qty;\n";
                echo "raceSkillCats[$rid]['$pos_safe'] = {primary: $norm_cats, secondary: $doub_cats};\n";
                echo "raceBaseSkills[$rid]['$pos_safe'] = '$base_skills_safe';\n";		
				$base_skill_ids = isset($pdata['def']) && is_array($pdata['def']) ? json_encode($pdata['def']) : '[]';
				echo "if (!raceBaseSkillIds[$rid]) raceBaseSkillIds[$rid] = {};\n";
				echo "raceBaseSkillIds[$rid]['$pos_safe'] = $base_skill_ids;\n";
                echo "raceIsBigGuy[$rid]['$pos_safe'] = $is_bigguy;\n";
            }
			echo "raceStats[$rid] = {};\n";
			foreach ($DEA[$rname]['players'] as $pos => $pdata) {
				$pos_safe = str_replace("'", "\\'", $pos);
				$ma = isset($pdata['ma']) ? $pdata['ma'] : 0;
				$st = isset($pdata['st']) ? $pdata['st'] : 0;
				$ag = isset($pdata['ag']) ? $pdata['ag'] : 0;
				$pa = isset($pdata['pa']) ? $pdata['pa'] : 0;
				$av = isset($pdata['av']) ? $pdata['av'] : 0;
				
				echo "raceStats[$rid]['$pos_safe'] = {ma: $ma, st: $st, ag: $ag, pa: $pa, av: $av};\n";
			}
		}

		echo "\n// Race ID to name index\n";
		echo 'var raceididx = ' . json_encode($raceididx) . ";\n";
		echo "\n// Player keywords\n";
		echo "var playerkeywordsididx = {};\n";  
		if (isset($playerkeywordsarray)) {
			foreach ($playerkeywordsarray as $kcat => $pkeyw) {
				foreach ($pkeyw as $kid => $kname) {
					$kname_safe = str_replace("'", "\\'", $kname);
					echo "playerkeywordsididx[$kid] = '$kname_safe';\n";
				}
			}
		}
		echo "\n// Race keywords per position\n";
		echo 'var raceKeywords = {};';
		foreach ($raceididx as $rid => $rname) {
			if (!isset($DEA[$rname])) continue;
			echo "raceKeywords[$rid] = {};\n";
			foreach ($DEA[$rname]['players'] as $pos => $pdata) {
				$pos_safe = str_replace("'", "\\'", $pos);
				$keywords = isset($pdata['keyword']) ? json_encode($pdata['keyword']) : '[]';
				echo "raceKeywords[$rid]['$pos_safe'] = $keywords;\n";
			}
		}

		echo "\n// Star players with prices and skills\n";
		echo "var starIds = {};\n";
        foreach ($stars as $star_name => $sdata) {
            $star_safe = str_replace("'", "\\'", $star_name);
            $star_id = $sdata['id'];
            $price = $sdata['cost'] / 1000;
            $races_json = json_encode($sdata['races']);
            $teamrules_json = json_encode($sdata['teamrules']);
            
            $base_skills = isset($sdata['def']) ? $sdata['def'] : array();
            $base_skills_trans = skillsTrans($base_skills);
            $base_skills_str = is_array($base_skills_trans) ? implode(', ', $base_skills_trans) : $base_skills_trans;
            $base_skills_safe = str_replace("'", "\\'", $base_skills_str);
            
            echo "starIds['$star_safe'] = $star_id;\n";
            echo "starPlayers['$star_safe'] = {races: $races_json, teamrules: $teamrules_json};\n";
            echo "starPrices['$star_safe'] = $price;\n";
            echo "starBaseSkills['$star_safe'] = '$base_skills_safe';\n";
            $star_skill_ids = isset($sdata['def']) ? json_encode($sdata['def']) : '[]';
            echo "starBaseSkillIds['$star_safe'] = $star_skill_ids;\n";
        }

        foreach ($stars as $star_name => $sdata) {
            $star_safe = str_replace("'", "\\'", $star_name);
            $ma = isset($sdata['ma']) ? $sdata['ma'] : 0;
            $st = isset($sdata['st']) ? $sdata['st'] : 0;
            $ag = isset($sdata['ag']) ? $sdata['ag'] : 0;
            $pa = isset($sdata['pa']) ? $sdata['pa'] : 0;
            $av = isset($sdata['av']) ? $sdata['av'] : 0;
            
            echo "starStats['$star_safe'] = {ma: $ma, st: $st, ag: $ag, pa: $pa, av: $av};\n";
        }

        echo "\n// Star pairs\n";
		global $starpairs;
		if (!isset($starpairs)) {
			$starpairs = array();
		}
		echo 'var starPairs = ' . json_encode($starpairs) . ";\n";
		echo "var starPairsByName = {};\n";
		echo "var starPairsReverse = {};\n";

		foreach ($starpairs as $parent_id => $child_id) {
			$parent_name = '';
			$child_name = '';
			
			foreach ($stars as $sname => $sdata) {
				$star_id = isset($sdata['id']) ? $sdata['id'] : null;
				
				if ($star_id == $parent_id) {
					$parent_name = $sname;
				}
				if ($star_id == $child_id) {
					$child_name = $sname;
				}
			}
			
			if ($parent_name && $child_name) {
				$parent_safe = str_replace("'", "\\'", $parent_name);
				$child_safe = str_replace("'", "\\'", $child_name);
				echo "starPairsByName['$parent_safe'] = '$child_safe';\n";
				echo "starPairsReverse['$child_safe'] = '$parent_safe';\n";
			} 
		}

        echo "\n// Inducements (source=1 only) with dynamic pricing\n";
        foreach ($inducements as $ind_name => $ind_data) {
            if (isset($ind_data['source']) && $ind_data['source'] == 1) {
                $ind_safe = str_replace("'", "\\'", $ind_name);
                $cost = $ind_data['cost'];
                $max = isset($ind_data['max']) ? $ind_data['max'] : 10;
                $reduced_cost = isset($ind_data['reduced_cost']) ? $ind_data['reduced_cost'] : $cost;
                $reduced_cost_rules = isset($ind_data['reduced_cost_rules']) ? json_encode($ind_data['reduced_cost_rules']) : '[]';
                $reduced_cost_races = isset($ind_data['reduced_cost_races']) ? json_encode($ind_data['reduced_cost_races']) : '[]';
                $reduced_max = isset($ind_data['reduced_max']) ? $ind_data['reduced_max'] : $max;
                $teamrules = isset($ind_data['teamrules']) ? json_encode($ind_data['teamrules']) : '[]';
                
                echo "inducements['$ind_safe'] = {";
                echo "cost: $cost, ";
                echo "max: $max, ";
                echo "reducedCost: $reduced_cost, ";
                echo "reducedCostRules: $reduced_cost_rules, ";
                echo "reducedCostRaces: $reduced_cost_races, ";
                echo "reducedMax: $reduced_max, ";
                echo "teamrules: $teamrules";
                echo "};\n";
            }
        }

        ?>

var positionalCounts = {};
var selectedStars = [];
var bigGuyCount = 0;

function changeRace() {
    var raceSelect = document.getElementById('race_id');
    var newRaceId = parseInt(raceSelect.value);
    
    if (newRaceId < 0) return;
    
    // Show/hide tournament settings based on race selection
	var settingsPlaceholder = document.getElementById('tournament-settings-placeholder');
	var settingsContainer = document.getElementById('tournament-settings-container');
	var settingsToggle = document.getElementById('tournament-settings-toggle');
	if (settingsPlaceholder) settingsPlaceholder.style.display = 'none';
	// Don't change settingsContainer display here - let the toggle state control it
	if (settingsToggle) settingsToggle.style.display = 'inline';
    
    currentRaceId = newRaceId;
    currentTeamRules = raceSpecialRules[currentRaceId] || [];
    positionalCounts = {};
    selectedStars = [];
    bigGuyCount = 0;
    
    // Clear all player positions AND extra skills when race changes
    var playerRows = document.getElementsByClassName('player-row');
    for (var i = 0; i < playerRows.length; i++) {
        // Clear the position selection
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        if (posSelect) {
            posSelect.value = '';
            posSelect.removeAttribute('data-old-value');
            posSelect.removeAttribute('data-is-paired-child');
            posSelect.style.backgroundColor = '';
            posSelect.style.cursor = '';
        }
        
        // Clear extra skills
        var extraSkillsInput = document.getElementById('player-' + i + '-extra-skills');
        if (extraSkillsInput) {
            extraSkillsInput.value = '[]';
        }
        
        // Clear hidden form inputs
        var skillsContainer = playerRows[i].querySelector('.skills-hidden-inputs');
        if (skillsContainer) {
            skillsContainer.innerHTML = '';
        }
        
        // Clear the display
        var valueCell = playerRows[i].getElementsByClassName('value-cell')[0];
        if (valueCell) valueCell.textContent = '-';
			
		var valueCell = playerRows[i].getElementsByClassName('value-cell')[0];
		if (valueCell) valueCell.textContent = '-';

		var maCell = playerRows[i].getElementsByClassName('ma-cell')[0];
		if (maCell) maCell.textContent = '-';

		var stCell = playerRows[i].getElementsByClassName('st-cell')[0];
		if (stCell) stCell.textContent = '-';

		var agCell = playerRows[i].getElementsByClassName('ag-cell')[0];
		if (agCell) agCell.textContent = '-';

		var paCell = playerRows[i].getElementsByClassName('pa-cell')[0];
		if (paCell) paCell.textContent = '-';

		var avCell = playerRows[i].getElementsByClassName('av-cell')[0];
		if (avCell) avCell.textContent = '-';
				
        var baseSkillsDisplay = playerRows[i].querySelector('.base-skills-display');
        if (baseSkillsDisplay) {
            baseSkillsDisplay.textContent = '-';
            baseSkillsDisplay.style.color = '#000';
        }
        
        // Show the manage skills link (in case it was hidden for a star)
        var skillsCell = playerRows[i].getElementsByClassName('skills-cell')[0];
        if (skillsCell) {
            var manageSkillsLink = skillsCell.querySelector('a[onclick*="openSkillPopup"]');
            if (manageSkillsLink) {
                manageSkillsLink.style.display = 'inline';
            }
        }
    }
    
    updateLeagueDropdown();
    updateFavRulesDropdown();
    updateEffectiveRules();
    updateAllPlayerRows();
    updateInducementsDisplay();
    updateSidelineDisplay();
    updateCounters();
    updateTeamDetailsDisplay();
	updateRosterDisplay();
	
	// Auto-expand roster when race changes
	var rosterContent = document.getElementById('roster-container');
	var rosterLink = document.getElementById('roster-toggle');
	if (rosterContent.style.display === 'none') {
		rosterContent.style.display = 'block';
		rosterLink.textContent = '[Collapse]';
		document.getElementById('roster-expanded').value = '1';
	}
}

function updateAllPlayerRows() {
    var playerRows = document.getElementsByClassName('player-row');
    
    selectedStars = [];
	bigGuyCount = 0;
	var pairedStarsFound = {};

	for (var i = 0; i < playerRows.length; i++) {
		var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
		if (posSelect && posSelect.value) {
			if (posSelect.value.indexOf('STAR:') === 0) {
				var starName = posSelect.value.substring(5);
				
				// Check if this star is part of a pair
				if (starPairsByName[starName] || starPairsReverse[starName]) {
					var pairName = starPairsByName[starName] || starPairsReverse[starName];
					var pairKey = [starName, pairName].sort().join('|');
					
					if (!pairedStarsFound[pairKey]) {
						// First time seeing this pair - count it as 1 star
						selectedStars.push(posSelect.value);
						pairedStarsFound[pairKey] = true;
					}
				} else {
					// Regular star without a pair
					selectedStars.push(posSelect.value);
				}
            } else if (currentRaceId >= 0 && raceIsBigGuy[currentRaceId][posSelect.value] === 1) {
                bigGuyCount++;
            }
        }
    }
    
    for (var i = 0; i < playerRows.length; i++) {
        updatePlayerPosition(i);
    }
}

function updatePlayerPosition(rowIdx) {
    
    var playerRows = document.getElementsByClassName('player-row');
    if (rowIdx >= playerRows.length) return;
    
    var row = playerRows[rowIdx];
    var posSelect = row.getElementsByClassName('position-select')[0];
    
    if (!posSelect) return;
    
    // Skip paired children - don't rebuild their dropdown
	if (posSelect.getAttribute('data-is-paired-child') === '1') {
		return;
	}
		
    var currentVal = posSelect.value;
    posSelect.innerHTML = '<option value="">-- Select Position --</option>';
    
    var tempCounts = {};
    for (var i = 0; i < playerRows.length; i++) {
        var sel = playerRows[i].getElementsByClassName('position-select')[0];
        if (sel && sel.value && sel.value.indexOf('STAR:') !== 0) {
            tempCounts[sel.value] = (tempCounts[sel.value] || 0) + 1;
        }
    }
    
    if (currentRaceId >= 0 && racePositions[currentRaceId]) {
        for (var j = 0; j < racePositions[currentRaceId].length; j++) {
            var pos = racePositions[currentRaceId][j];
            var count = tempCounts[pos.name] || 0;
            var maxQty = raceMaxQty[currentRaceId][pos.name];
            var isBigGuy = raceIsBigGuy[currentRaceId][pos.name] === 1;
            var bigGuyMax = raceBigGuyMax[currentRaceId];
            var isCurrentSelection = (currentVal === pos.name);
            
            if (count >= maxQty && !isCurrentSelection) continue;
            if (isBigGuy && bigGuyCount >= bigGuyMax && !isCurrentSelection) continue;
            
            var opt = document.createElement('option');
            opt.value = pos.name;
            
            // Calculate remaining (don't count the current selection)
            var remaining = maxQty - count;
            if (isCurrentSelection) {
                remaining++;
            }
            
            // Show count for all options in dropdown, but we'll update the selected one after
            opt.textContent = pos.name + ' - ' + remaining + ' left';
            opt.setAttribute('data-position-name', pos.name); // Store clean name
            posSelect.appendChild(opt);
        }
    }
    
    var currentStarCount = selectedStars.length;
	var isCurrentStar = currentVal.indexOf('STAR:') === 0;

	// Only show stars if a race is selected
	if (currentRaceId >= 0 && (currentStarCount < 2 || isCurrentStar)) {
		var starGroup = document.createElement('optgroup');
		starGroup.label = '-- Star Players (Max 2) --';
		var hasStars = false;

		for (var starName in starPlayers) {
			var star = starPlayers[starName];
			
			// Skip stars with 0 cost (they're paired children)
			if (starPrices[starName] === 0) {
				continue;
			}
			
			// Skip stars already selected in other rows
			var starValue = 'STAR:' + starName;
			var alreadySelected = false;
			for (var k = 0; k < playerRows.length; k++) {
				if (k !== rowIdx) { // Don't check the current row
					var otherPosSelect = playerRows[k].getElementsByClassName('position-select')[0];
					if (otherPosSelect && otherPosSelect.value === starValue) {
						alreadySelected = true;
						break;
					}
				}
			}
			if (alreadySelected && currentVal !== starValue) {
				continue;
			}
				
			// Check if star's teamrules match current effective rules
			var starAvailable = false;

			if (star.teamrules.length === 0) {
				// No teamrules - not available to anyone
				starAvailable = false;
			} else {
				// Check if ANY of the star's teamrules match ANY of the team's currentTeamRules
				for (var j = 0; j < star.teamrules.length; j++) {
					if (currentTeamRules.indexOf(star.teamrules[j]) >= 0) {
						starAvailable = true;
						break;
					}
				}
			}
			
			if (!starAvailable) {
				continue;
			}
						
			var opt = document.createElement('option');
			opt.value = starValue;
			opt.textContent = starName + ' (Star)';
			starGroup.appendChild(opt);
			hasStars = true;
		}
		
		if (hasStars) {
			posSelect.appendChild(starGroup);
		}
	}

	posSelect.value = currentVal;
    
    // After setting the value, update the display of the selected option to just show the name
    if (currentVal && currentVal.indexOf('STAR:') !== 0) {
        var selectedOption = posSelect.options[posSelect.selectedIndex];
        if (selectedOption && selectedOption.getAttribute('data-position-name')) {
            selectedOption.textContent = selectedOption.getAttribute('data-position-name');
        }
    }

	if (currentVal) {
		updatePlayerDetails(rowIdx);
	}
}

function updatePlayerDetails(rowIdx) {
    var playerRows = document.getElementsByClassName('player-row');
    if (rowIdx >= playerRows.length) return;
    
    var row = playerRows[rowIdx];
    var posSelect = row.getElementsByClassName('position-select')[0];
    var valueCell = row.getElementsByClassName('value-cell')[0];
    var maCell = row.getElementsByClassName('ma-cell')[0];
    var stCell = row.getElementsByClassName('st-cell')[0];
    var agCell = row.getElementsByClassName('ag-cell')[0];
    var paCell = row.getElementsByClassName('pa-cell')[0];
    var avCell = row.getElementsByClassName('av-cell')[0];
    var skillsCell = row.getElementsByClassName('skills-cell')[0];
    var baseSkillsDisplay = skillsCell ? skillsCell.getElementsByClassName('base-skills-display')[0] : null;
    var manageSkillsLink = skillsCell ? skillsCell.querySelector('a[onclick*="openSkillPopup"]') : null;
    
    var position = posSelect.value;
    
    if (!position) {
        if (valueCell) valueCell.textContent = '-';
        if (maCell) maCell.textContent = '-';
        if (stCell) stCell.textContent = '-';
        if (agCell) agCell.textContent = '-';
        if (paCell) paCell.textContent = '-';
        if (avCell) avCell.textContent = '-';
        if (baseSkillsDisplay) {
            baseSkillsDisplay.textContent = '-';
            baseSkillsDisplay.style.color = '#000';
        }
        if (manageSkillsLink) manageSkillsLink.style.display = 'none';
        return;
    }
    
    var isStar = position.indexOf('STAR:') === 0;
    var price, baseSkills, ma, st, ag, pa, av;
    
    if (isStar) {
        var starName = position.substring(5);
        var starData = starPlayers[starName];
        price = starPrices[starName];
        baseSkills = starBaseSkills[starName];
        
        ma = starStats[starName] ? starStats[starName].ma : '-';
        st = starStats[starName] ? starStats[starName].st : '-';
        ag = starStats[starName] ? starStats[starName].ag : '-';
        pa = starStats[starName] ? starStats[starName].pa : '-';
        av = starStats[starName] ? starStats[starName].av : '-';
        
        if (manageSkillsLink) manageSkillsLink.style.display = 'none';
    } else {
        price = racePrices[currentRaceId][position];
        baseSkills = raceBaseSkills[currentRaceId][position];
        
        ma = raceStats[currentRaceId] && raceStats[currentRaceId][position] ? raceStats[currentRaceId][position].ma : '-';
        st = raceStats[currentRaceId] && raceStats[currentRaceId][position] ? raceStats[currentRaceId][position].st : '-';
        ag = raceStats[currentRaceId] && raceStats[currentRaceId][position] ? raceStats[currentRaceId][position].ag : '-';
        pa = raceStats[currentRaceId] && raceStats[currentRaceId][position] ? raceStats[currentRaceId][position].pa : '-';
        av = raceStats[currentRaceId] && raceStats[currentRaceId][position] ? raceStats[currentRaceId][position].av : '-';
        
        if (manageSkillsLink) manageSkillsLink.style.display = 'inline';
    }
    
    var agDisplay = (ag > 0 && ag < 7) ? ag + '+' : ag;
    var paDisplay = (pa > 0 && pa < 7) ? pa + '+' : pa;
    var avDisplay = (av > 0) ? av + '+' : av;
    
    // Get stat increases and apply them
    var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
    var statIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
    
    // Calculate increased stats for non-stars
    if (!isStar) {
        if (statIncreases.ma > 0) ma = ma + statIncreases.ma;
        if (statIncreases.st > 0) st = st + statIncreases.st;
        if (statIncreases.ag > 0) {
            ag = ag - statIncreases.ag;  // SUBTRACT for AG (lower is better)
            agDisplay = (ag > 0 && ag < 7) ? ag + '+' : ag;
        }
        if (statIncreases.pa > 0 && pa !== '-') {
            pa = pa - statIncreases.pa;  // SUBTRACT for PA (lower is better)
            paDisplay = (pa > 0 && pa < 7) ? pa + '+' : pa;
        }
        if (statIncreases.av > 0) {
            av = av + statIncreases.av;
            avDisplay = (av > 0) ? av + '+' : av;
        }
    }
    
    // Apply highlighting if increased
    var maStyle = statIncreases.ma > 0 ? 'background-color: #ccffcc; font-weight: bold;' : '';
    var stStyle = statIncreases.st > 0 ? 'background-color: #ccffcc; font-weight: bold;' : '';
    var agStyle = statIncreases.ag > 0 ? 'background-color: #ccffcc; font-weight: bold;' : '';
    var paStyle = statIncreases.pa > 0 ? 'background-color: #ccffcc; font-weight: bold;' : '';
    var avStyle = statIncreases.av > 0 ? 'background-color: #ccffcc; font-weight: bold;' : '';
    
    // Set base value initially
    if (valueCell) valueCell.textContent = price + 'k';
    
    if (maCell) {
        maCell.textContent = ma;
        maCell.style.cssText = 'text-align: center;' + maStyle;
    }
    if (stCell) {
        stCell.textContent = st;
        stCell.style.cssText = 'text-align: center;' + stStyle;
    }
    if (agCell) {
        agCell.textContent = agDisplay;
        agCell.style.cssText = 'text-align: center;' + agStyle;
    }
    if (paCell) {
        paCell.textContent = paDisplay;
        paCell.style.cssText = 'text-align: center;' + paStyle;
    }
    if (avCell) {
        avCell.textContent = avDisplay;
        avCell.style.cssText = 'text-align: center;' + avStyle;
    }
    if (baseSkillsDisplay) {
        baseSkillsDisplay.textContent = baseSkills;
        baseSkillsDisplay.style.color = '#000';
    }
    
    updateExtraSkillsDisplay(rowIdx);
	updatePlayerStatsDisplay(rowIdx);
    updateCounters();
}

function updateExtraSkillsDisplay(rowIdx) {
    var playerRows = document.getElementsByClassName('player-row');
    if (rowIdx >= playerRows.length) return;
    
    var row = playerRows[rowIdx];
    var posSelect = row.getElementsByClassName('position-select')[0];
    var skillsCell = row.getElementsByClassName('skills-cell')[0];
    if (!skillsCell) return;
    
    var baseSkillsDisplay = skillsCell.getElementsByClassName('base-skills-display')[0];
    if (!baseSkillsDisplay) return;
    
    var position = posSelect.value;
    if (!position) return;
    
    var isStar = position.indexOf('STAR:') === 0;
    var baseSkills = isStar ? starBaseSkills[position.substring(5)] : raceBaseSkills[currentRaceId][position];
    
    // Get extra skills from hidden input
    var extraSkillsInput = document.getElementById('player-' + rowIdx + '-extra-skills');
    var extraSkillIds = extraSkillsInput ? JSON.parse(extraSkillsInput.value || '[]') : [];
    
    var extraSkillsHTML = [];
    for (var i = 0; i < extraSkillIds.length; i++) {
        var skillId = parseInt(extraSkillIds[i]);
        if (skillId && allSkills[skillId]) {
            var skillName = allSkills[skillId];
            var isPrimary = false;
            
            if (!isStar) {
                var primaryCats = raceSkillCats[currentRaceId][position].primary;
                var skillCat = skillCategories[skillId];
                isPrimary = primaryCats.indexOf(skillCat) >= 0;
            }
            
            var color = isPrimary ? '#0000ff' : '#ff0000';
            extraSkillsHTML.push('<span style="color: ' + color + ';">' + skillName + '</span>');
        }
    }
    
    var displayHTML = baseSkills;
    if (extraSkillsHTML.length > 0) {
        displayHTML += ', ' + extraSkillsHTML.join(', ');
    }
    
    baseSkillsDisplay.innerHTML = displayHTML;
    updateCounters();
}

function addPlayer() {
    var tbody = document.getElementById('players-tbody');
    var rowCount = tbody.getElementsByTagName('tr').length;
    
    if (rowCount >= 16) {
        alert('Maximum 16 players allowed!');
        return;
    }
    
    // Find the first available player number
    var playerRows = document.getElementsByClassName('player-row');
    var usedNumbers = [];
    
    for (var i = 0; i < playerRows.length; i++) {
        var numberInput = playerRows[i].querySelector('input[type="number"]');
        if (numberInput) {
            usedNumbers.push(parseInt(numberInput.value));
        }
    }
    
    // Sort the used numbers
    usedNumbers.sort(function(a, b) { return a - b; });
    
    // Find the first gap or use next number
    var newPlayerNumber = 1;
    for (var i = 0; i < usedNumbers.length; i++) {
        if (usedNumbers[i] === newPlayerNumber) {
            newPlayerNumber++;
        } else {
            break;
        }
    }
    
    // Find the correct position to insert the new row
    var insertPosition = -1; // -1 means append at end
    for (var i = 0; i < playerRows.length; i++) {
        var numberInput = playerRows[i].querySelector('input[type="number"]');
        if (numberInput && parseInt(numberInput.value) > newPlayerNumber) {
            insertPosition = i;
            break;
        }
    }
    
    // Create the new row
    var newRow = tbody.insertRow(insertPosition);
    newRow.className = 'player-row';
    
    var html = '<td><input type="number" name="players[' + rowCount + '][nr]" value="' + newPlayerNumber + '" size="2" style="width: 40px;" /></td>';
    html += '<td><select name="players[' + rowCount + '][position]" class="position-select" onchange="onPositionChange(' + rowCount + ')"><option value="">-- Select Position --</option></select></td>';
    html += '<td class="ma-cell" style="text-align: center;">-</td>';
    html += '<td class="st-cell" style="text-align: center;">-</td>';
    html += '<td class="ag-cell" style="text-align: center;">-</td>';
    html += '<td class="pa-cell" style="text-align: center;">-</td>';
    html += '<td class="av-cell" style="text-align: center;">-</td>';
    html += '<td class="skills-cell">';
    html += '<span style="color: #000; margin-bottom: 5px;" class="base-skills-display">-</span>';
    html += '<input type="hidden" id="player-' + rowCount + '-extra-skills" value="[]" />';
    html += '<a href="javascript:void(0)" onclick="openSkillPopup(' + rowCount + ')" style="font-size: 11px; margin-left: 10px;">Manage Skills</a>';
    html += '</td>';
    html += '<td class="value-cell">-</td>';
    html += '<td>';
	html += '<button type="button" class="player-button button-move" onclick="movePlayerUp(' + rowCount + ')" title="Move Up">↑</button> ';
	html += '<button type="button" class="player-button button-move" onclick="movePlayerDown(' + rowCount + ')" title="Move Down">↓</button> ';
	html += '<button type="button" class="player-button button-copy" onclick="copyPlayer(' + rowCount + ')" title="Copy this player" style="font-size: 15px;">+</button> ';
	html += '<button type="button" class="player-button button-remove" onclick="removePlayer(this)" title="Remove">✕</button>';
	html += '</td>';
    
    newRow.innerHTML = html;
    
    // Fix all handlers after insertion
    fixMoveButtonHandlers();
    
    // Update the new row's position dropdown
    var newRowIndex = insertPosition === -1 ? rowCount : insertPosition;
    updatePlayerPosition(newRowIndex);
    updateCounters();
}

function onPositionChange(rowIdx) {
    var playerRows = document.getElementsByClassName('player-row');
    var row = playerRows[rowIdx];
    var posSelect = row.getElementsByClassName('position-select')[0];
    var position = posSelect.value;
    
    // Don't do anything if position is empty
    if (!position || position === '') {
        return;
    }
    
    // Block changes to paired children (but allow initial setup)
    if (posSelect.getAttribute('data-is-paired-child') === '1') {
        var oldValue = posSelect.getAttribute('data-old-value');
        if (oldValue && position !== oldValue) {
            // They're trying to change it - block it
            posSelect.value = oldValue;
            return;
        }
    }
    
    // If changing away from a paired star, clear the pair
    var oldValue = posSelect.getAttribute('data-old-value') || '';
    if (oldValue.indexOf('STAR:') === 0 && oldValue !== position) {
        var oldStarName = oldValue.substring(5);
        var oldPairName = starPairsByName[oldStarName] || starPairsReverse[oldStarName];
        
        if (oldPairName) {
            // Clear the paired star
            playerRows = document.getElementsByClassName('player-row');
            for (var i = 0; i < playerRows.length; i++) {
                var otherPos = playerRows[i].getElementsByClassName('position-select')[0];
                if (otherPos && otherPos.value === 'STAR:' + oldPairName) {
                    otherPos.value = '';
                    otherPos.setAttribute('data-old-value', '');
                    otherPos.removeAttribute('data-is-paired-child');
                    otherPos.style.backgroundColor = '';
                    otherPos.style.cursor = '';
                    break;
                }
            }
        }
    }
    
    // Store the new value for next time
    posSelect.setAttribute('data-old-value', position);
    
    // Check if this is a parent star that has a pair
    if (position.indexOf('STAR:') === 0) {
        var starName = position.substring(5);
        var pairedStarName = starPairsByName[starName];
        
        if (pairedStarName) {
			
            // This is a parent - add child in next row
            playerRows = document.getElementsByClassName('player-row');
            
            if (rowIdx + 1 >= playerRows.length) {
                addPlayer();
                playerRows = document.getElementsByClassName('player-row');
            }
            
            var nextRow = playerRows[rowIdx + 1];
            var nextPosSelect = nextRow.getElementsByClassName('position-select')[0];
            
            // Clear the dropdown and add just the child option
            nextPosSelect.innerHTML = '<option value="">-- Select Position --</option>';
            var pairedOption = document.createElement('option');
            pairedOption.value = 'STAR:' + pairedStarName;
            pairedOption.textContent = pairedStarName + ' (Paired Star)';
            nextPosSelect.appendChild(pairedOption);
            nextPosSelect.value = 'STAR:' + pairedStarName;
            nextPosSelect.setAttribute('data-old-value', 'STAR:' + pairedStarName);
            nextPosSelect.setAttribute('data-is-paired-child', '1');
            nextPosSelect.style.backgroundColor = '#f0f0f0';
            nextPosSelect.style.cursor = 'not-allowed';
            
            updatePlayerDetails(rowIdx + 1);
        }
    }
    
    updatePlayerDetails(rowIdx);
    updateAllPlayerRows();
}

function removePlayer(btn) {
    var row = btn.parentNode.parentNode;
    var tbody = row.parentNode;
    
    // Check if this is a paired star BEFORE removing anything
    var posSelect = row.getElementsByClassName('position-select')[0];
    var pairName = null;
    
    if (posSelect && posSelect.value.indexOf('STAR:') === 0) {
        var starName = posSelect.value.substring(5);
        pairName = starPairsByName[starName] || starPairsReverse[starName];
    }
    
    // Remove this row
    tbody.removeChild(row);
    
    // If it had a pair, find and remove the paired row
    if (pairName) {
        var playerRows = tbody.getElementsByTagName('tr');
        for (var i = 0; i < playerRows.length; i++) {
            var otherPosSelect = playerRows[i].getElementsByClassName('position-select')[0];
            if (otherPosSelect && otherPosSelect.value === 'STAR:' + pairName) {
                tbody.removeChild(playerRows[i]);
                break;
            }
        }
    }
    
    // FIX THE ONCLICK HANDLERS
    var playerRows = document.getElementsByClassName('player-row');
    for (var i = 0; i < playerRows.length; i++) {
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        if (posSelect) {
            posSelect.setAttribute('onchange', 'onPositionChange(' + i + ')');
        }
    }
    
    fixMoveButtonHandlers();  // Add this line
    updateAllPlayerRows();
    updateCounters();
}

function movePlayerUp(rowIdx) {
    var tbody = document.getElementById('players-tbody');
    var rows = tbody.getElementsByTagName('tr');
    
    if (rowIdx > 0 && rowIdx < rows.length) {
        var currentRow = rows[rowIdx];
        var previousRow = rows[rowIdx - 1];
        tbody.insertBefore(currentRow, previousRow);
        
        // Fix onclick handlers for all buttons after moving
        fixMoveButtonHandlers();
        renumberPlayers();
        updateCounters();
    }
}

function movePlayerDown(rowIdx) {
    var tbody = document.getElementById('players-tbody');
    var rows = tbody.getElementsByTagName('tr');
    
    if (rowIdx >= 0 && rowIdx < rows.length - 1) {
        var currentRow = rows[rowIdx];
        var nextRow = rows[rowIdx + 1];
        tbody.insertBefore(nextRow, currentRow);
        
        // Fix onclick handlers for all buttons after moving
        fixMoveButtonHandlers();
        renumberPlayers();
        updateCounters();
    }
}

function renumberPlayers() {
    var tbody = document.getElementById('players-tbody');
    var rows = tbody.getElementsByTagName('tr');
    
    // Collect all current numbers
    var numbers = [];
    for (var i = 0; i < rows.length; i++) {
        var numberInput = rows[i].querySelector('input[type="number"]');
        if (numberInput) {
            numbers.push(parseInt(numberInput.value));
        }
    }
    
    // Sort numbers to maintain their order
    numbers.sort(function(a, b) { return a - b; });
    
    // Reassign sorted numbers back to rows in order
    for (var i = 0; i < rows.length; i++) {
        var numberInput = rows[i].querySelector('input[type="number"]');
        if (numberInput) {
            numberInput.value = numbers[i];
        }
    }
}

function reorderRowsByNumber() {
    var tbody = document.getElementById('players-tbody');
    var rows = Array.prototype.slice.call(tbody.getElementsByTagName('tr'));
    
    // Sort rows by their number input value
    rows.sort(function(a, b) {
        var numA = parseInt(a.querySelector('input[type="number"]').value) || 0;
        var numB = parseInt(b.querySelector('input[type="number"]').value) || 0;
        return numA - numB;
    });
    
    // Re-append rows in sorted order
    for (var i = 0; i < rows.length; i++) {
        tbody.appendChild(rows[i]);
    }
    
    // Fix all handlers after reordering
    fixMoveButtonHandlers();
    updateCounters();
}

function fixMoveButtonHandlers() {
    var playerRows = document.getElementsByClassName('player-row');
    for (var i = 0; i < playerRows.length; i++) {
        var row = playerRows[i];
        
        // Find and update the move up button
		var upButton = row.querySelector('button[title="Move Up"]');
		if (upButton) {
			upButton.setAttribute('onclick', 'movePlayerUp(' + i + ')');
			// Disable if first row
			if (i === 0) {
				upButton.disabled = true;
			} else {
				upButton.disabled = false;
			}
		}

		// Find and update the move down button
		var downButton = row.querySelector('button[title="Move Down"]');
		if (downButton) {
			downButton.setAttribute('onclick', 'movePlayerDown(' + i + ')');
			// Disable if last row
			if (i === playerRows.length - 1) {
				downButton.disabled = true;
			} else {
				downButton.disabled = false;
			}
		}
        
        // Find and update the copy button
        var copyButton = row.querySelector('button[title="Copy this player"]');
        if (copyButton) {
            copyButton.setAttribute('onclick', 'copyPlayer(' + i + ')');
        }
        
        // Find and update the remove button
        var removeButton = row.querySelector('button[title="Remove"]');
        if (removeButton) {
            removeButton.setAttribute('onclick', 'removePlayer(this)');
        }
        
        // Fix the position select onchange handler
        var posSelect = row.getElementsByClassName('position-select')[0];
        if (posSelect) {
            posSelect.setAttribute('onchange', 'onPositionChange(' + i + ')');
        }
        
        // Fix the manage skills link
        var manageSkillsLink = row.querySelector('a[onclick*="openSkillPopup"]');
        if (manageSkillsLink) {
            manageSkillsLink.setAttribute('onclick', 'openSkillPopup(' + i + ')');
        }
        
        // Fix the extra skills input ID
        var extraSkillsInput = row.querySelector('input[type="hidden"][id^="player-"]');
        if (extraSkillsInput) {
            extraSkillsInput.id = 'player-' + i + '-extra-skills';
        }
        
        // Fix hidden skill inputs names
        var hiddenSkillInputs = row.querySelectorAll('.skills-hidden-inputs input[type="hidden"]');
        for (var j = 0; j < hiddenSkillInputs.length; j++) {
            var oldName = hiddenSkillInputs[j].name;
            var newName = oldName.replace(/players\[\d+\]/, 'players[' + i + ']');
            hiddenSkillInputs[j].name = newName;
        }
    }
}

function copyPlayer(rowIdx) {
    var playerRows = document.getElementsByClassName('player-row');
    if (rowIdx >= playerRows.length) return;
    
    var sourceRow = playerRows[rowIdx];
    var posSelect = sourceRow.getElementsByClassName('position-select')[0];
    var position = posSelect.value;
    
    if (!position) {
        alert('Please select a position first before copying!');
        return;
    }
    
    // Check if we're at max players
    var tbody = document.getElementById('players-tbody');
    var currentCount = tbody.getElementsByTagName('tr').length;
    
    // For stars, check star limit
    if (position.indexOf('STAR:') === 0) {
        var starName = position.substring(5);
        
        // Check if this is a paired star
        var isPaired = starPairsByName[starName] || starPairsReverse[starName];
        
        if (isPaired) {
            alert('Cannot copy paired star players!');
            return;
        }
        
        if (selectedStars.length >= 2) {
            alert('Maximum 2 star players allowed!');
            return;
        }
    } else {
        // For regular players, check positional and big guy limits
        var posCount = 0;
        var currentBigGuyCount = 0;
        
        for (var i = 0; i < playerRows.length; i++) {
            var otherPos = playerRows[i].getElementsByClassName('position-select')[0];
            if (otherPos && otherPos.value === position) {
                posCount++;
            }
            if (currentRaceId >= 0 && otherPos && otherPos.value && 
                raceIsBigGuy[currentRaceId][otherPos.value] === 1) {
                currentBigGuyCount++;
            }
        }
        
        var maxQty = raceMaxQty[currentRaceId][position];
        if (posCount >= maxQty) {
            alert('Maximum ' + maxQty + ' ' + position + ' allowed!');
            return;
        }
        
        var isBigGuy = raceIsBigGuy[currentRaceId][position] === 1;
        if (isBigGuy) {
            var bigGuyMax = raceBigGuyMax[currentRaceId];
            if (currentBigGuyCount >= bigGuyMax) {
                alert('Maximum ' + bigGuyMax + ' Big Guys allowed!');
                return;
            }
        }
    }
    
    // Get the extra skills from the source player
    var extraSkillsInput = document.getElementById('player-' + rowIdx + '-extra-skills');
    var extraSkills = extraSkillsInput ? JSON.parse(extraSkillsInput.value || '[]') : [];
    
    // Find the next empty row starting from the row after the current one
    var targetRowIdx = -1;
    for (var i = rowIdx + 1; i < playerRows.length; i++) {
        var checkPosSelect = playerRows[i].getElementsByClassName('position-select')[0];
        if (checkPosSelect && !checkPosSelect.value) {
            targetRowIdx = i;
            break;
        }
    }
    
    // If no empty row found after current row, check from the beginning
    if (targetRowIdx === -1) {
        for (var i = 0; i < rowIdx; i++) {
            var checkPosSelect = playerRows[i].getElementsByClassName('position-select')[0];
            if (checkPosSelect && !checkPosSelect.value) {
                targetRowIdx = i;
                break;
            }
        }
    }
    
    // If still no empty row found, add a new one (if under limit)
    if (targetRowIdx === -1) {
        if (currentCount >= 16) {
            alert('Maximum 16 players allowed!');
            return;
        }
        addPlayer();
        targetRowIdx = currentCount;
        playerRows = document.getElementsByClassName('player-row'); // Refresh the list
    }
    
    // Set the position on the target row
    var targetRow = playerRows[targetRowIdx];
    var targetPosSelect = targetRow.getElementsByClassName('position-select')[0];
    targetPosSelect.value = position;
    
    // Copy the extra skills
    var targetExtraSkillsInput = document.getElementById('player-' + targetRowIdx + '-extra-skills');
    if (targetExtraSkillsInput) {
        targetExtraSkillsInput.value = JSON.stringify(extraSkills);
    }
    
    // Update hidden form inputs for skills
    var skillsContainer = targetRow.querySelector('.skills-hidden-inputs');
    if (!skillsContainer) {
        skillsContainer = document.createElement('div');
        skillsContainer.className = 'skills-hidden-inputs';
        skillsContainer.style.display = 'none';
        targetRow.appendChild(skillsContainer);
    }
    
    skillsContainer.innerHTML = '';
    for (var i = 0; i < extraSkills.length; i++) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'players[' + targetRowIdx + '][skills][]';
        input.value = extraSkills[i];
        skillsContainer.appendChild(input);
    }
    
    // Trigger the position change to update everything
    onPositionChange(targetRowIdx);
}

function openSkillPopup(rowIdx) {
    var playerRows = document.getElementsByClassName('player-row');
    if (rowIdx >= playerRows.length) return;
    
    var row = playerRows[rowIdx];
    var posSelect = row.getElementsByClassName('position-select')[0];
    var position = posSelect.value;
    
    if (!position) {
        alert('Please select a position first!');
        return;
    }
    
    var extraSkillsInput = document.getElementById('player-' + rowIdx + '-extra-skills');
    var currentSkills = JSON.parse(extraSkillsInput.value || '[]');
    
    // Get base skills (only for regular players, not stars)
    var baseSkillIds = [];
    if (currentRaceId >= 0) {
        baseSkillIds = raceBaseSkillIds[currentRaceId][position] || [];
    }
    
    var allPlayerSkills = baseSkillIds.concat(currentSkills);
    
    function isSkillIllegal(skillId) {
        skillId = parseInt(skillId);
        for (var i = 0; i < allPlayerSkills.length; i++) {
            var has = parseInt(allPlayerSkills[i]);
            if (skillConflicts[has] && skillConflicts[has].indexOf(skillId) >= 0) return true;
            if (skillConflicts[skillId] && skillConflicts[skillId].indexOf(has) >= 0) return true;
        }
        return false;
    }
    
    function hasRequiredSkills(skillId) {
        skillId = parseInt(skillId);
        if (!skillRequirements[skillId]) return true;
        var req = skillRequirements[skillId];
        for (var i = 0; i < req.length; i++) {
            if (allPlayerSkills.indexOf(req[i]) >= 0) return true;
        }
        return false;
    }
    
    // Organize skills by category for primary and secondary
    var primarySkillsByCategory = {};
    var secondarySkillsByCategory = {};
    
    // Category names mapping
    var categoryNames = {
        'G': 'General',
        'A': 'Agility', 
        'P': 'Passing',
        'S': 'Strength',
        'D': 'Devious',
        'M': 'Mutations'
    };
    
    var primaryCats = raceSkillCats[currentRaceId][position].primary;
    var secondaryCats = raceSkillCats[currentRaceId][position].secondary;
    
    for (var skillId in allSkills) {
        var intId = parseInt(skillId);
        if (baseSkillIds.indexOf(intId) >= 0) continue; // Skip base skills
        if (isSkillIllegal(intId)) continue;
        if (!hasRequiredSkills(intId)) continue;
        
        var skillCat = skillCategories[skillId];
        var skillName = allSkills[skillId];
        var skillObj = {id: skillId, name: skillName, category: skillCat};
        
        if (primaryCats.indexOf(skillCat) >= 0) {
            if (!primarySkillsByCategory[skillCat]) {
                primarySkillsByCategory[skillCat] = [];
            }
            primarySkillsByCategory[skillCat].push(skillObj);
        } else if (secondaryCats.indexOf(skillCat) >= 0) {
            if (!secondarySkillsByCategory[skillCat]) {
                secondarySkillsByCategory[skillCat] = [];
            }
            secondarySkillsByCategory[skillCat].push(skillObj);
        }
    }
    
    // Sort skills alphabetically within each category
    for (var cat in primarySkillsByCategory) {
        primarySkillsByCategory[cat].sort(function(a, b) {
            return a.name.localeCompare(b.name);
        });
    }
    for (var cat in secondarySkillsByCategory) {
        secondarySkillsByCategory[cat].sort(function(a, b) {
            return a.name.localeCompare(b.name);
        });
    }
    
    // Build the popup HTML
    var popup = document.createElement('div');
    popup.id = 'skill-popup';
    popup.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 3px solid #333; padding: 20px; z-index: 10000; max-height: 80vh; overflow-y: auto; min-width: 400px;';
    
    var html = '<h3 style="margin-top: 0;">Manage Skills</h3>';
    html += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">';
    
    // Display primary skills by category
    var hasPrimarySkills = false;
    for (var cat in primarySkillsByCategory) {
        hasPrimarySkills = true;
    }
    
    if (hasPrimarySkills) {
        html += '<h4 style="color: #0000ff; margin: 10px 0 5px 0;">Primary Skills</h4>';
        
        // Order categories: G, A, P, S, D, M
        var categoryOrder = ['G', 'A', 'P', 'S', 'D', 'M'];
        for (var c = 0; c < categoryOrder.length; c++) {
            var cat = categoryOrder[c];
            if (primarySkillsByCategory[cat] && primarySkillsByCategory[cat].length > 0) {
                html += '<h5 style="color: #0000cc; margin: 8px 0 3px 0; font-size: 11px;">' + categoryNames[cat] + '</h5>';
                
                for (var i = 0; i < primarySkillsByCategory[cat].length; i++) {
                    var skill = primarySkillsByCategory[cat][i];
                    var checked = currentSkills.indexOf(parseInt(skill.id)) >= 0 ? 'checked' : '';
                    html += '<div style="margin: 3px 0 3px 15px;">';
                    html += '<input type="checkbox" id="skill-cb-' + skill.id + '" value="' + skill.id + '" ' + checked + ' onchange="updateSkillAvailability()" /> ';
                    html += '<label for="skill-cb-' + skill.id + '" id="skill-label-' + skill.id + '">' + skill.name + '</label>';
                    html += '</div>';
                }
            }
        }
    }
    
    // Display secondary skills by category
    var hasSecondarySkills = false;
    for (var cat in secondarySkillsByCategory) {
        hasSecondarySkills = true;
    }
    
    if (hasSecondarySkills) {
        html += '<h4 style="color: #ff0000; margin: 10px 0 5px 0;">Secondary Skills</h4>';
        
        var categoryOrder = ['G', 'A', 'P', 'S', 'D', 'M'];
        for (var c = 0; c < categoryOrder.length; c++) {
            var cat = categoryOrder[c];
            if (secondarySkillsByCategory[cat] && secondarySkillsByCategory[cat].length > 0) {
                html += '<h5 style="color: #cc0000; margin: 8px 0 3px 0; font-size: 11px;">' + categoryNames[cat] + '</h5>';
                
                for (var i = 0; i < secondarySkillsByCategory[cat].length; i++) {
                    var skill = secondarySkillsByCategory[cat][i];
                    var checked = currentSkills.indexOf(parseInt(skill.id)) >= 0 ? 'checked' : '';
                    html += '<div style="margin: 3px 0 3px 15px;">';
                    html += '<input type="checkbox" id="skill-cb-' + skill.id + '" value="' + skill.id + '" ' + checked + ' onchange="updateSkillAvailability()" /> ';
                    html += '<label for="skill-cb-' + skill.id + '" id="skill-label-' + skill.id + '">' + skill.name + '</label>';
                    html += '</div>';
                }
            }
        }
    }
    
    // Stat Increases Section (only show if enabled and not a star)
    var allowStatIncreases = document.getElementById('allow_stat_increases');
    var extraSkillsInput = document.getElementById('player-' + rowIdx + '-extra-skills');
    var posSelect = document.getElementsByClassName('player-row')[rowIdx].getElementsByClassName('position-select')[0];
    var isStar = posSelect.value.indexOf('STAR:') === 0;
    
    if (allowStatIncreases && allowStatIncreases.checked && !isStar) {
        html += '<div id="stat-increases-section" style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #ccc;">';
        html += '<h3 style="margin-bottom: 10px;">Stat Improvements</h3>';
        html += '<div id="stat-increase-message" style="margin-bottom: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffc107; display: none; border-radius: 4px;"></div>';
        
        // Get current stat increases
        var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
        var currentStatIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value) : {ma:0, st:0, ag:0, pa:0, av:0};
        
        // Get current player stats
        var playerData = teamPlayers[rowIdx] || {};
        var currentStats = {
            ma: playerData.ma || 0,
            st: playerData.st || 0,
            ag: playerData.ag || 0,
            pa: playerData.pa || 0,
            av: playerData.av || 0
        };
        
        // Check if costs are enabled
        var statCostsEnabled = document.getElementById('stat_increases_cost_gold');
        var showCosts = statCostsEnabled && statCostsEnabled.checked;
        
        html += '<div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">';
        
        var stats = ['ma', 'st', 'ag', 'pa', 'av'];
        var statLabels = {ma: 'MA', st: 'ST', ag: 'AG', pa: 'PA', av: 'AV'};
        
        for (var s = 0; s < stats.length; s++) {
            var stat = stats[s];
            var label = statLabels[stat];
            var current = currentStats[stat];
            var increases = currentStatIncreases[stat] || 0;
            
            // Get cost if enabled
            var costText = '';
            if (showCosts) {
                var costField = document.getElementById(stat + '_increase_cost');
                var cost = costField ? parseInt(costField.value) || 0 : 0;
                costText = cost > 0 ? ' (' + cost + 'k)' : '';
            }
            
            html += '<div class="stat-increase-option" data-stat="' + stat + '" style="text-align: center;">';
            // For AG and PA, show minus since lower is better
            var buttonLabel = (stat === 'ag' || stat === 'pa') ? '-' + label : '+' + label;
            html += '<button type="button" onclick="addStatIncrease(\'' + stat + '\', ' + rowIdx + ')" style="padding: 8px 15px;">' + buttonLabel + costText + '</button>';
            html += '<div style="margin-top: 5px; font-size: 12px;">Base: ' + current;
            if (increases > 0) {
                html += ' <span style="color: green; font-weight: bold;">(+' + increases + ')</span>';
            }
            html += '</div>';
            html += '<button type="button" onclick="removeStatIncrease(\'' + stat + '\', ' + rowIdx + ')" style="padding: 4px 10px; font-size: 11px; margin-top: 3px;">Remove</button>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '</div>';
    }
    
    html += '</div>';
    html += '<button onclick="saveSkillPopup(' + rowIdx + ')" style="margin-right: 10px; padding: 5px 15px;">Save</button>';
    html += '<button onclick="closeSkillPopup()" style="padding: 5px 15px;">Cancel</button>';
    
    popup.innerHTML = html;
    
    var overlay = document.createElement('div');
    overlay.id = 'skill-popup-overlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;';
    overlay.onclick = closeSkillPopup;
    
    document.body.appendChild(overlay);
    document.body.appendChild(popup);
    
    // Run initial availability check
    updateSkillAvailability();
}

function updateSkillAvailability() {
    var popup = document.getElementById('skill-popup');
    if (!popup) return;
    
    var checkboxes = popup.querySelectorAll('input[type="checkbox"]');
    var selectedSkillIds = [];
    
    // Collect all currently selected skills
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            selectedSkillIds.push(parseInt(checkboxes[i].value));
        }
    }
    
    // Check each checkbox against selected skills
    for (var i = 0; i < checkboxes.length; i++) {
        var checkbox = checkboxes[i];
        var skillId = parseInt(checkbox.value);
        var label = document.getElementById('skill-label-' + skillId);
        
        // Skip if this skill is currently selected
        if (checkbox.checked) {
            checkbox.disabled = false;
            if (label) {
                label.style.color = '';
                label.style.opacity = '1';
            }
            continue;
        }
        
        // Check if this skill conflicts with any selected skill
        var isConflicted = false;
        for (var j = 0; j < selectedSkillIds.length; j++) {
            var selectedId = selectedSkillIds[j];
            
            // Check if skillId conflicts with selectedId
            if (skillConflicts[selectedId] && skillConflicts[selectedId].indexOf(skillId) >= 0) {
                isConflicted = true;
                break;
            }
            
            // Check if selectedId conflicts with skillId
            if (skillConflicts[skillId] && skillConflicts[skillId].indexOf(selectedId) >= 0) {
                isConflicted = true;
                break;
            }
        }
        
        // Grey out and disable if conflicted
        if (isConflicted) {
            checkbox.disabled = true;
            if (label) {
                label.style.color = '#999';
                label.style.opacity = '0.5';
                label.style.textDecoration = 'line-through';
            }
        } else {
            checkbox.disabled = false;
            if (label) {
                label.style.color = '';
                label.style.opacity = '1';
                label.style.textDecoration = 'none';
            }
        }
    }
}

function saveSkillPopup(rowIdx) {
    var popup = document.getElementById('skill-popup');
    if (!popup) return;
    
    var checkboxes = popup.querySelectorAll('input[type="checkbox"]');
    var selectedSkills = [];
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            selectedSkills.push(parseInt(checkboxes[i].value));
        }
    }
    
    // VALIDATION: Check stacking rules
    var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
    var statIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
    
    // Count total stat improvements for this player
    var totalStatImprovements = (statIncreases.ma || 0) + (statIncreases.st || 0) + (statIncreases.ag || 0) + (statIncreases.pa || 0) + (statIncreases.av || 0);
    var totalExtraSkills = selectedSkills.length;
    var totalEnhancements = totalExtraSkills + totalStatImprovements;
    
    // Check if stat+skill stacking is allowed
    var allowStatSkillStacking = document.getElementById('allow_stat_skill_stacking');
    if (allowStatSkillStacking && !allowStatSkillStacking.checked) {
        if (totalStatImprovements > 0 && totalExtraSkills > 0) {
            alert('Cannot combine stat improvements with extra skills. This tournament does not allow stacking stats and skills on the same player.\n\nThis player has ' + totalStatImprovements + ' stat improvement(s). Please remove either the stat improvements or the extra skills before saving.');
            return;
        }
    }
    
    // Check max stacked skills per player (if > 0)
    var maxStackedField = document.getElementById('max_stacked_skills_count');
    var maxStacked = maxStackedField ? parseInt(maxStackedField.value) || 0 : 0;
    
    if (maxStacked > 0 && totalEnhancements > maxStacked) {
        alert('Cannot save. This player would have ' + totalEnhancements + ' total enhancements (skills + stat improvements), but the tournament limit is ' + maxStacked + ' per player.\n\nPlease reduce the number of skills or stat improvements before saving.');
        return;
    }
	
	// Check if secondary skills can be stacked
    var allowSecondaryStacking = document.getElementById('allow_secondary_stacking');
    if (allowSecondaryStacking && !allowSecondaryStacking.checked) {
        // Count secondary skills in selected skills
        var secondarySkillCount = 0;
        var row = document.getElementsByClassName('player-row')[rowIdx];
        var posSelect = row.getElementsByClassName('position-select')[0];
        var position = posSelect.value;
        
        if (position && position.indexOf('STAR:') !== 0 && currentRaceId >= 0) {
            var primaryCats = raceSkillCats[currentRaceId][position].primary;
            
            for (var i = 0; i < selectedSkills.length; i++) {
                var skillId = selectedSkills[i];
                var skillCat = skillCategories[skillId];
                var isPrimary = primaryCats.indexOf(skillCat) >= 0;
                
                if (!isPrimary) {
                    secondarySkillCount++;
                }
            }
        }
        
        // If player has 1+ secondary skills AND total enhancements > 1, block
        if (secondarySkillCount >= 1 && totalEnhancements > 1) {
            alert('Cannot save. This player has ' + secondarySkillCount + ' secondary skill(s) and secondary skills cannot be stacked in this tournament.\n\nOnce a player has a secondary skill, they cannot have any additional skills or stat improvements. Please reduce to either:\n- Multiple primary skills only, OR\n- 1 secondary skill only');
            return;
        }
    }
    
    // Check max players with stacked skills (if > 0)
    var maxPlayersStackedField = document.getElementById('max_players_multi_skills');
    var maxPlayersStacked = maxPlayersStackedField ? parseInt(maxPlayersStackedField.value) || 0 : 0;
    
    if (maxPlayersStacked > 0) {
        // Count how many OTHER players already have stacked skills/stats (2+)
        var playersWithStacking = 0;
        var playerRows = document.getElementsByClassName('player-row');
        
        for (var i = 0; i < playerRows.length; i++) {
            if (i === rowIdx) continue; // Skip current player
            
            // Get this player's extra skills
            var otherExtraSkillsInput = document.getElementById('player-' + i + '-extra-skills');
            var otherExtraSkills = otherExtraSkillsInput ? JSON.parse(otherExtraSkillsInput.value || '[]') : [];
            
            // Get this player's stat improvements
            var otherStatIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
            var otherStatIncreases = otherStatIncreasesInput ? JSON.parse(otherStatIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
            var otherTotalStats = (otherStatIncreases.ma || 0) + (otherStatIncreases.st || 0) + (otherStatIncreases.ag || 0) + (otherStatIncreases.pa || 0) + (otherStatIncreases.av || 0);
            
            var otherTotal = otherExtraSkills.length + otherTotalStats;
            
            if (otherTotal >= 2) {
                playersWithStacking++;
            }
        }
        
        // Check if current player would be stacked (2+)
        var currentPlayerIsStacked = totalEnhancements >= 2;
        
        if (currentPlayerIsStacked && playersWithStacking >= maxPlayersStacked) {
            alert('Cannot save. This would give ' + (playersWithStacking + 1) + ' players stacked skills/stats, but the tournament limit is ' + maxPlayersStacked + ' players.\n\nYou currently have ' + playersWithStacking + ' other player(s) with 2+ enhancements. Please reduce skills/stats on this player or another player before saving.');
            return;
        }
    }
    
    var extraSkillsInput = document.getElementById('player-' + rowIdx + '-extra-skills');
    extraSkillsInput.value = JSON.stringify(selectedSkills);
    
    // Update hidden form fields
    var row = document.getElementsByClassName('player-row')[rowIdx];
    var skillsContainer = row.querySelector('.skills-hidden-inputs');
    if (!skillsContainer) {
        skillsContainer = document.createElement('div');
        skillsContainer.className = 'skills-hidden-inputs';
        skillsContainer.style.display = 'none';
        row.appendChild(skillsContainer);
    }
    
    skillsContainer.innerHTML = '';
    for (var i = 0; i < selectedSkills.length; i++) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'players[' + rowIdx + '][skills][]';
        input.value = selectedSkills[i];
        skillsContainer.appendChild(input);
    }
    
    // DON'T call updateExtraSkillsDisplay - it breaks the onclick handler
    // Instead, manually update just the text content
    var posSelect = row.getElementsByClassName('position-select')[0];
    var skillsCell = row.getElementsByClassName('skills-cell')[0];
    if (skillsCell && posSelect && posSelect.value) {
        var baseSkillsDisplay = skillsCell.getElementsByClassName('base-skills-display')[0];
        if (baseSkillsDisplay) {
            var position = posSelect.value;
            var isStar = position.indexOf('STAR:') === 0;
            var baseSkills = isStar ? starBaseSkills[position.substring(5)] : raceBaseSkills[currentRaceId][position];
            
            var extraSkillsHTML = [];
            for (var i = 0; i < selectedSkills.length; i++) {
                var skillId = parseInt(selectedSkills[i]);
                if (skillId && allSkills[skillId]) {
                    var skillName = allSkills[skillId];
                    var isPrimary = false;
                    
                    if (!isStar) {
                        var primaryCats = raceSkillCats[currentRaceId][position].primary;
                        var skillCat = skillCategories[skillId];
                        isPrimary = primaryCats.indexOf(skillCat) >= 0;
                    }
                    
                    var color = isPrimary ? '#0000ff' : '#ff0000';
                    extraSkillsHTML.push('<span style="color: ' + color + ';">' + skillName + '</span>');
                }
            }
            
            var displayHTML = baseSkills;
            if (extraSkillsHTML.length > 0) {
                displayHTML += ', ' + extraSkillsHTML.join(', ');
            }
            
            baseSkillsDisplay.innerHTML = displayHTML;
        }
    }
    
    // Update stat display cells with green highlighting
    var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
    if (statIncreasesInput) {
        var statIncreases = JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}');
        
        if (posSelect && posSelect.value.indexOf('STAR:') !== 0) {
            var position = posSelect.value;
            if (currentRaceId >= 0 && raceStats[currentRaceId] && raceStats[currentRaceId][position]) {
                var baseStats = raceStats[currentRaceId][position];
                
                var maCell = row.getElementsByClassName('ma-cell')[0];
                var stCell = row.getElementsByClassName('st-cell')[0];
                var agCell = row.getElementsByClassName('ag-cell')[0];
                var paCell = row.getElementsByClassName('pa-cell')[0];
                var avCell = row.getElementsByClassName('av-cell')[0];
                
                if (maCell) {
                    var ma = baseStats.ma + statIncreases.ma;
                    maCell.textContent = ma;
                    maCell.style.cssText = 'text-align: center;' + (statIncreases.ma > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
                }
                
                if (stCell) {
                    var st = baseStats.st + statIncreases.st;
                    stCell.textContent = st;
                    stCell.style.cssText = 'text-align: center;' + (statIncreases.st > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
                }
                
                if (agCell) {
                    var ag = baseStats.ag - statIncreases.ag;
                    var agDisplay = (ag > 0 && ag < 7) ? ag + '+' : ag;
                    agCell.textContent = agDisplay;
                    agCell.style.cssText = 'text-align: center;' + (statIncreases.ag > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
                }
                
                if (paCell) {
                    var pa = baseStats.pa - statIncreases.pa;
                    var paDisplay = (pa > 0 && pa < 7) ? pa + '+' : pa;
                    paCell.textContent = paDisplay;
                    paCell.style.cssText = 'text-align: center;' + (statIncreases.pa > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
                }
                
                if (avCell) {
                    var av = baseStats.av + statIncreases.av;
                    var avDisplay = (av > 0) ? av + '+' : av;
                    avCell.textContent = avDisplay;
                    avCell.style.cssText = 'text-align: center;' + (statIncreases.av > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
                }
            }
        }
    }
    
    updateCounters();
    closeSkillPopup();
}

function closeSkillPopup() {
    var popup = document.getElementById('skill-popup');
    var overlay = document.getElementById('skill-popup-overlay');
    
    if (popup) document.body.removeChild(popup);
    if (overlay) document.body.removeChild(overlay);
}

function addStatIncrease(statType, rowIdx) {
    // Get current stat increases
    var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
    var currentStatIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value) : {ma:0, st:0, ag:0, pa:0, av:0};
    
    // Check if multiples are allowed
    var allowMultiples = document.getElementById('allow_multiple_same_stat');
    var currentIncrease = currentStatIncreases[statType] || 0;
    
    if (!allowMultiples || !allowMultiples.checked) {
        if (currentIncrease >= 1) {
            showStatMessage('Cannot add multiple improvements to the same stat. Enable "Allow multiples of same Stat on a player" to allow this.', 'error');
            return false;
        }
    } else {
        // Even with multiples allowed, maximum is 2
        if (currentIncrease >= 2) {
            showStatMessage('Cannot add more than 2 improvements to the same stat.', 'error');
            return false;
        }
    }
    
    // Check stat limits BEFORE adding
    var playerData = teamPlayers[rowIdx] || {};
    var baseStat = 0;
    
    if (statType === 'ma') baseStat = playerData.ma || 0;
    else if (statType === 'st') baseStat = playerData.st || 0;
    else if (statType === 'ag') baseStat = playerData.ag || 0;
    else if (statType === 'pa') baseStat = playerData.pa || 0;
    else if (statType === 'av') baseStat = playerData.av || 0;
    
    // Calculate what the new stat would be
    var newStatValue = 0;
    if (statType === 'ag' || statType === 'pa') {
        // AG and PA decrease (lower is better)
        newStatValue = baseStat - (currentIncrease + 1);
        if (newStatValue < 1) {
            showStatMessage('Cannot improve ' + statType.toUpperCase() + ' below 1.', 'error');
            return false;
        }
    } else {
        // MA, ST, AV increase
        newStatValue = baseStat + (currentIncrease + 1);
        if ((statType === 'ma' || statType === 'av') && newStatValue > 11) {
            showStatMessage('Cannot improve ' + statType.toUpperCase() + ' above 11.', 'error');
            return false;
        }
        if (statType === 'st' && newStatValue > 6) {
            showStatMessage('Cannot improve ST above 6.', 'error');
            return false;
        }
    }
    
    // Get team-wide stat increase counts
    var teamStatCounts = getTeamStatIncreaseCounts();
    
    // Check max for this specific stat (team-wide)
    var maxStatField = document.getElementById('max_' + statType + '_increases');
    var maxStat = maxStatField ? parseInt(maxStatField.value) || 0 : 0;
    if (maxStat > 0) {
        var currentTeamStat = teamStatCounts[statType] || 0;
        
        if (currentTeamStat >= maxStat) {
            showStatMessage('Cannot add +' + statType.toUpperCase() + '. Team has reached max ' + statType.toUpperCase() + ' improvements (' + maxStat + ').', 'error');
            return false;
        }
    }
    
    // Check max total stat increases (team-wide)
    var maxTotalField = document.getElementById('max_total_stat_increases');
    var maxTotal = maxTotalField ? parseInt(maxTotalField.value) || 0 : 0;
    if (maxTotal > 0) {
        var currentTeamTotal = teamStatCounts.total || 0;
        
        if (currentTeamTotal >= maxTotal) {
            showStatMessage('Cannot add stat inprovement. Team has reached max total stat inprovements (' + maxTotal + ').', 'error');
            return false;
        }
    }
    
	// Check if stat+skill stacking is allowed
    var extraSkillsInput = document.getElementById('player-' + rowIdx + '-extra-skills');
    var extraSkills = extraSkillsInput ? JSON.parse(extraSkillsInput.value || '[]') : [];
    var totalExtraSkills = extraSkills.length;
    
    var allowStatSkillStacking = document.getElementById('allow_stat_skill_stacking');
    if (allowStatSkillStacking && !allowStatSkillStacking.checked) {
        if (totalExtraSkills > 0) {
            showStatMessage('Cannot add stat improvement. This player has extra skills and the tournament does not allow mixing stats with skills.', 'error');
            return false;
        }
    }
	
	// Check if secondary skills can be stacked (when stats are involved)
    var allowSecondaryStacking = document.getElementById('allow_secondary_stacking');
    if (allowSecondaryStacking && !allowSecondaryStacking.checked) {
        // Count secondary skills
        var secondarySkillCount = 0;
        var row = document.getElementsByClassName('player-row')[rowIdx];
        var posSelect = row.getElementsByClassName('position-select')[0];
        var position = posSelect.value;
        
        if (position && position.indexOf('STAR:') !== 0 && currentRaceId >= 0) {
            var primaryCats = raceSkillCats[currentRaceId][position].primary;
            
            for (var i = 0; i < extraSkills.length; i++) {
                var skillId = extraSkills[i];
                var skillCat = skillCategories[skillId];
                var isPrimary = primaryCats.indexOf(skillCat) >= 0;
                
                if (!isPrimary) {
                    secondarySkillCount++;
                }
            }
        }
        
        // Calculate what total would be after adding this stat
        var currentTotalStats = (currentStatIncreases.ma || 0) + (currentStatIncreases.st || 0) + (currentStatIncreases.ag || 0) + (currentStatIncreases.pa || 0) + (currentStatIncreases.av || 0);
        var futureTotal = totalExtraSkills + currentTotalStats + 1; // +1 for the stat we're adding
        
        // If player has secondary skill(s) and would have total > 1, block
        if (secondarySkillCount >= 1 && futureTotal > 1) {
            showStatMessage('Cannot add stat improvement. Player has ' + secondarySkillCount + ' secondary skill(s) and secondary stacking is disabled.', 'error');
            return false;
        }
    }
    
    // Calculate what total enhancements would be after adding this stat
    var currentTotalStats = (currentStatIncreases.ma || 0) + (currentStatIncreases.st || 0) + (currentStatIncreases.ag || 0) + (currentStatIncreases.pa || 0) + (currentStatIncreases.av || 0);
    var newTotalEnhancements = totalExtraSkills + currentTotalStats + 1; // +1 for the stat we're about to add
    
    // Check max stacked per player
    var maxStackedField = document.getElementById('max_stacked_skills_count');
    var maxStacked = maxStackedField ? parseInt(maxStackedField.value) || 0 : 0;
    
    if (maxStacked > 0 && newTotalEnhancements > maxStacked) {
        showStatMessage('Cannot add stat improvement. Player would have ' + newTotalEnhancements + ' total enhancements (limit: ' + maxStacked + ').', 'error');
        return false;
    }
    
    // Check max players with stacked (only if this would make them stacked)
    var maxPlayersStackedField = document.getElementById('max_players_multi_skills');
    var maxPlayersStacked = maxPlayersStackedField ? parseInt(maxPlayersStackedField.value) || 0 : 0;
    
    if (maxPlayersStacked > 0 && newTotalEnhancements >= 2) {
        // Count OTHER players with stacking
        var playersWithStacking = 0;
        var playerRows = document.getElementsByClassName('player-row');
        var currentPlayerAlreadyStacked = (totalExtraSkills + currentTotalStats) >= 2;
        
        for (var i = 0; i < playerRows.length; i++) {
            if (i === rowIdx) continue;
            
            var otherExtraSkillsInput = document.getElementById('player-' + i + '-extra-skills');
            var otherExtraSkills = otherExtraSkillsInput ? JSON.parse(otherExtraSkillsInput.value || '[]') : [];
            
            var otherStatIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
            var otherStatIncreases = otherStatIncreasesInput ? JSON.parse(otherStatIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
            var otherTotalStats = (otherStatIncreases.ma || 0) + (otherStatIncreases.st || 0) + (otherStatIncreases.ag || 0) + (otherStatIncreases.pa || 0) + (otherStatIncreases.av || 0);
            
            if ((otherExtraSkills.length + otherTotalStats) >= 2) {
                playersWithStacking++;
            }
        }
        
        // If current player is NOT already stacked, this would be a new stacked player
        if (!currentPlayerAlreadyStacked && playersWithStacking >= maxPlayersStacked) {
            showStatMessage('Cannot add stat improvement. Team already has ' + playersWithStacking + ' players with stacked skills/stats (limit: ' + maxPlayersStacked + ').', 'error');
            return false;
        }
    }
	
    // All checks passed - add the increase
    currentStatIncreases[statType] = currentIncrease + 1;
    statIncreasesInput.value = JSON.stringify(currentStatIncreases);
    
    // Update teamPlayers
    if (window.teamPlayers && window.teamPlayers[rowIdx]) {
        window.teamPlayers[rowIdx].stat_increases = currentStatIncreases;
    }
    
    // Update the display in the popup dynamically
    var statOption = document.querySelector('.stat-increase-option[data-stat="' + statType + '"]');
    if (statOption) {
        var baseDisplay = statOption.querySelector('div');
        if (baseDisplay) {
            // Get the current base stat
            var playerData = teamPlayers[rowIdx] || {};
            var baseStat = playerData[statType] || 0;
            
            // Rebuild the display text
            baseDisplay.innerHTML = 'Base: ' + baseStat + ' <span style="color: green; font-weight: bold;">(+' + currentStatIncreases[statType] + ')</span>';
        }
    }
    
    showStatMessage('Improved ' + statType.toUpperCase() + '.', 'success');
    
    return true;
}

function removeStatIncrease(statType, rowIdx) {
    // Get current stat increases
    var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
    var currentStatIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value) : {ma:0, st:0, ag:0, pa:0, av:0};
    var currentIncrease = currentStatIncreases[statType] || 0;
    
    if (currentIncrease <= 0) {
        return false;
    }
    
    // Remove one increase
    currentStatIncreases[statType] = currentIncrease - 1;
    statIncreasesInput.value = JSON.stringify(currentStatIncreases);
    
    // Update teamPlayers
    if (window.teamPlayers && window.teamPlayers[rowIdx]) {
        window.teamPlayers[rowIdx].stat_increases = currentStatIncreases;
    }
    
    // Update the display in the popup dynamically
    var statOption = document.querySelector('.stat-increase-option[data-stat="' + statType + '"]');
    if (statOption) {
        var baseDisplay = statOption.querySelector('div');
        if (baseDisplay) {
            // Get the current base stat
            var playerData = teamPlayers[rowIdx] || {};
            var baseStat = playerData[statType] || 0;
            
            // Rebuild the display text
            if (currentStatIncreases[statType] > 0) {
                baseDisplay.innerHTML = 'Base: ' + baseStat + ' <span style="color: green; font-weight: bold;">(+' + currentStatIncreases[statType] + ')</span>';
            } else {
                baseDisplay.innerHTML = 'Base: ' + baseStat;
            }
        }
    }
    
    showStatMessage('Removed ' + statType.toUpperCase() + ' improvement.', 'success');
    
    return true;
}

function showStatMessage(message, type) {
    var msgDiv = document.getElementById('stat-increase-message');
    if (!msgDiv) return;
    
    msgDiv.textContent = message;
    msgDiv.style.display = 'block';
    
    if (type === 'error') {
        msgDiv.style.background = '#ffdddd';
        msgDiv.style.borderColor = '#cc0000';
        msgDiv.style.color = '#cc0000';
    } else {
        msgDiv.style.background = '#ddffdd';
        msgDiv.style.borderColor = '#00cc00';
        msgDiv.style.color = '#00aa00';
    }
    
    // Hide message after 3 seconds
    setTimeout(function() {
        msgDiv.style.display = 'none';
    }, 3000);
}

function getTeamStatIncreaseCounts() {
    var counts = {
        ma: 0,
        st: 0,
        ag: 0,
        pa: 0,
        av: 0,
        total: 0
    };
    
    // Count stat increases across all players
    var playerRows = document.getElementsByClassName('player-row');
    for (var i = 0; i < playerRows.length; i++) {
        var statIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
        if (statIncreasesInput) {
            var statIncreases = JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}');
            counts.ma += statIncreases.ma || 0;
            counts.st += statIncreases.st || 0;
            counts.ag += statIncreases.ag || 0;
            counts.pa += statIncreases.pa || 0;
            counts.av += statIncreases.av || 0;
        }
    }
    
    counts.total = counts.ma + counts.st + counts.ag + counts.pa + counts.av;
    
    return counts;
}

function updatePlayerStatsDisplay(rowIdx) {
    var row = document.getElementsByClassName('player-row')[rowIdx];
    if (!row) return;
    
    var statIncreasesInput = document.getElementById('player-' + rowIdx + '-stat-increases');
    var statIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
    
    var posSelect = row.getElementsByClassName('position-select')[0];
    if (!posSelect) return;
    
    var position = posSelect.value;
    var isStar = position.indexOf('STAR:') === 0;
    
    if (isStar) return; // Don't update stars
    
    // Get base stats
    var baseStats = {ma: 0, st: 0, ag: 0, pa: 0, av: 0};
    if (currentRaceId >= 0 && raceStats[currentRaceId] && raceStats[currentRaceId][position]) {
        baseStats = raceStats[currentRaceId][position];
    }
    
    // Update each stat cell
    var maCell = row.getElementsByClassName('ma-cell')[0];
    var stCell = row.getElementsByClassName('st-cell')[0];
    var agCell = row.getElementsByClassName('ag-cell')[0];
    var paCell = row.getElementsByClassName('pa-cell')[0];
    var avCell = row.getElementsByClassName('av-cell')[0];
    
    if (maCell) {
        var ma = baseStats.ma + statIncreases.ma;
        maCell.textContent = ma;
        maCell.style.cssText = 'text-align: center;' + (statIncreases.ma > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
    }
    
    if (stCell) {
        var st = baseStats.st + statIncreases.st;
        stCell.textContent = st;
        stCell.style.cssText = 'text-align: center;' + (statIncreases.st > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
    }
    
    if (agCell) {
        var ag = baseStats.ag - statIncreases.ag;
        var agDisplay = (ag > 0 && ag < 7) ? ag + '+' : ag;
        agCell.textContent = agDisplay;
        agCell.style.cssText = 'text-align: center;' + (statIncreases.ag > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
    }
    
    if (paCell) {
        var pa = baseStats.pa - statIncreases.pa;
        var paDisplay = (pa > 0 && pa < 7) ? pa + '+' : pa;
        paCell.textContent = paDisplay;
        paCell.style.cssText = 'text-align: center;' + (statIncreases.pa > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
    }
    
    if (avCell) {
        var av = baseStats.av + statIncreases.av;
        var avDisplay = (av > 0) ? av + '+' : av;
        avCell.textContent = avDisplay;
        avCell.style.cssText = 'text-align: center;' + (statIncreases.av > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '');
    }
}

function updateCounters() {
    var playerRows = document.getElementsByClassName('player-row');
    var totalValue = 0;
    var primarySkills = 0;
    var secondarySkills = 0;
    var eliteSkills = 0;
    var totalPlayers = 0;
    var regularPlayers = 0;
    var starCount = 0;
    var pairedStarsFound = {};
    
    // Get tournament settings for skill costs
    var ts = <?php echo json_encode(isset($team_data['tournament_settings']) ? $team_data['tournament_settings'] : array()); ?>;
    var skillsCostGold = ts.skills_cost_gold ? true : false;
    var skillsAddValue = ts.skills_add_player_value ? true : false;
    var primarySkillCost = parseInt(ts.primary_skill_cost) || 20;
    var secondarySkillCost = parseInt(ts.secondary_skill_cost) || 40;
    var eliteSkillCost = parseInt(ts.elite_skill_cost) || 10;
    
    for (var i = 0; i < playerRows.length; i++) {
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        var valueCell = playerRows[i].getElementsByClassName('value-cell')[0];
        
        if (posSelect && posSelect.value) {
            totalPlayers++;
            var position = posSelect.value;
            var isStar = position.indexOf('STAR:') === 0;
            
            // ALWAYS get base value from race/star data, NOT from the cell display
            var baseValue = 0;
            if (isStar) {
                var starName = position.substring(5);
                baseValue = starPrices[starName];
                
                // Check if this star is part of a pair
                if (starPairsByName[starName] || starPairsReverse[starName]) {
                    var pairName = starPairsByName[starName] || starPairsReverse[starName];
                    var pairKey = [starName, pairName].sort().join('|');
                    
                    if (!pairedStarsFound[pairKey]) {
                        starCount++;
                        pairedStarsFound[pairKey] = true;
                    }
                } else {
                    starCount++;
                }
                
                totalValue += baseValue;
            } else {
                regularPlayers++;
                baseValue = racePrices[currentRaceId][position];
                
                var playerSkillCost = 0;
                var extraSkillsInput = document.getElementById('player-' + i + '-extra-skills');
                var extraSkillIds = extraSkillsInput ? JSON.parse(extraSkillsInput.value || '[]') : [];
                
                for (var j = 0; j < extraSkillIds.length; j++) {
                    var skillId = parseInt(extraSkillIds[j]);
                    if (skillId) {
                        // Check if elite
                        var isElite = false;
                        if (eliteSkillIds.indexOf(skillId) >= 0) {
                            eliteSkills++;
                            isElite = true;
                        }
                        
                        // Check if primary or secondary
                        var primaryCats = raceSkillCats[currentRaceId][position].primary;
                        var skillCat = skillCategories[skillId];
                        
                        var isPrimary = primaryCats.indexOf(skillCat) >= 0;
                        
                        if (isPrimary) {
                            primarySkills++;
                        } else {
                            secondarySkills++;
                        }
                        
                        // Calculate skill cost if applicable
                        if (skillsCostGold || skillsAddValue) {
                            var skillCost = isPrimary ? primarySkillCost : secondarySkillCost;
                            if (isElite) {
                                skillCost += eliteSkillCost;
                            }
                            playerSkillCost += skillCost;
                        }
                    }
                }
                
                // Update player value display if "add to player value" is checked
                if (skillsAddValue && playerSkillCost > 0) {
                    var newPlayerValue = baseValue + playerSkillCost;
                    valueCell.textContent = newPlayerValue + 'k';
                    totalValue += newPlayerValue;
                } else {
                    // Show base value only
                    valueCell.textContent = baseValue + 'k';
                    totalValue += baseValue;
                }
                
                // Add skill cost to team total if "cost gold" is checked (and not already added via player value)
                if (skillsCostGold && !skillsAddValue && playerSkillCost > 0) {
                    totalValue += playerSkillCost;
                }
            }
        }
    }
    
    // Add sideline costs to total value
	if (currentRaceId >= 0) {
		// Get tournament settings for overrides
		var ts = tournamentSettings;
		
		var rrSelect = document.getElementById('sideline_rerolls');
		if (rrSelect) {
			var rrQty = parseInt(rrSelect.value) || 0;
			var rrCost = raceRerollCost[currentRaceId] || 0;
			var rrMultiplier = 1;
			
			if (ts.override_sideline_costs && ts.reroll_cost_multiplier) {
				rrMultiplier = parseFloat(ts.reroll_cost_multiplier) || 1;
			}
			
			totalValue += Math.round(rrQty * rrCost * rrMultiplier);
		}
			
        var fansSelect = document.getElementById('sideline_fans');
		var freeFirstDFCheck = document.getElementById('sideline_free_first_df');
		if (fansSelect && freeFirstDFCheck) {
			var fansQty = parseInt(fansSelect.value) || 0;
			var freeFirstDF = freeFirstDFCheck.checked;
			var fanCost = gamePrices.fan_factor / 1000;
			
			if (ts.override_sideline_costs && ts.cost_dedicated_fans) {
				fanCost = parseInt(ts.cost_dedicated_fans) || fanCost;
			}
			
			var fan_cost = freeFirstDF ? ((fansQty - 1) * fanCost) : (fansQty * fanCost);
			totalValue += fan_cost;
		}
        
        var coachesSelect = document.getElementById('sideline_coaches');
		if (coachesSelect) {
			var coachesQty = parseInt(coachesSelect.value) || 0;
			var coachCost = gamePrices.ass_coaches / 1000;
			
			if (ts.override_sideline_costs && ts.cost_ass_coaches) {
				coachCost = parseInt(ts.cost_ass_coaches) || coachCost;
			}
			
			totalValue += coachesQty * coachCost;
		}
        
        var cheerSelect = document.getElementById('sideline_cheerleaders');
		if (cheerSelect) {
			var cheerQty = parseInt(cheerSelect.value) || 0;
			var cheerCost = gamePrices.cheerleaders / 1000;
			
			if (ts.override_sideline_costs && ts.cost_cheerleaders) {
				cheerCost = parseInt(ts.cost_cheerleaders) || cheerCost;
			}
			
			totalValue += cheerQty * cheerCost;
		}
        
        var apothCheck = document.getElementById('sideline_apothecary');
		if (apothCheck && apothCheck.checked) {
			var apothCost = gamePrices.apothecary / 1000;
			
			if (ts.override_sideline_costs && ts.cost_apothecary) {
				apothCost = parseInt(ts.cost_apothecary) || apothCost;
			}
			
			totalValue += apothCost;
		}
    }
    
    // Add inducement costs
    var inducementSelects = document.querySelectorAll('select[name^="inducements"]');
    for (var i = 0; i < inducementSelects.length; i++) {
        var select = inducementSelects[i];
        var qty = parseInt(select.value) || 0;
        if (qty > 0) {
            var indName = select.name.replace('inducements[', '').replace(']', '').replace(/_/g, ' ');
            if (inducements[indName]) {
                var cost = inducements[indName].cost;
                var hasReduction = false;
                var teamRules = currentTeamRules || [];
                
                if (inducements[indName].reducedCostRules.length > 0) {
                    for (var j = 0; j < teamRules.length; j++) {
                        if (inducements[indName].reducedCostRules.indexOf(teamRules[j]) >= 0) {
                            cost = inducements[indName].reducedCost;
                            hasReduction = true;
                            break;
                        }
                    }
                }
                
                if (!hasReduction && inducements[indName].reducedCostRaces.length > 0) {
                    if (inducements[indName].reducedCostRaces.indexOf(currentRaceId) >= 0) {
                        cost = inducements[indName].reducedCost;
                    }
                }
                
                totalValue += (cost / 1000) * qty;
            }
        }
    }
    
    document.getElementById('counter-value').textContent = Math.round(totalValue) + 'k';
    document.getElementById('counter-players').textContent = totalPlayers + ' / 16';
    document.getElementById('counter-regular').textContent = regularPlayers;
    document.getElementById('counter-stars').textContent = starCount + ' / 2';
    document.getElementById('counter-primary').textContent = primarySkills;
    document.getElementById('counter-secondary').textContent = secondarySkills;
    document.getElementById('counter-elite').textContent = eliteSkills;
    
    var playerColor = '#000';
    if (totalPlayers > 16 || regularPlayers < 11) {
        playerColor = '#cc0000';
    }
    document.getElementById('counter-players').style.color = playerColor;
    
    // Calculate player-only total for the table footer
    var playerOnlyTotal = 0;
    for (var i = 0; i < playerRows.length; i++) {
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        var valueCell = playerRows[i].getElementsByClassName('value-cell')[0];
        
        if (posSelect && posSelect.value && valueCell) {
            var valueText = valueCell.textContent;
            if (valueText && valueText !== '-') {
                var value = parseFloat(valueText.replace('k', ''));
                playerOnlyTotal += value;
            }
        }
    }
    
    updatePlayerValueTotal(playerOnlyTotal);
    
    // CALL COMPLIANCE VALIDATION HERE
	validateInsignificantRule();
    validateTournamentCompliance();
}

var INSIGNIFICANT_SKILL_ID = 134; 

function validateInsignificantRule() {
    var playerRows = document.getElementsByClassName('player-row');
    var playersWithInsignificant = 0;
    var playersWithoutInsignificant = 0;
    var totalPlayers = 0;
    
    for (var i = 0; i < playerRows.length; i++) {
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        
        if (posSelect && posSelect.value) {
            totalPlayers++;
            var position = posSelect.value;
            var isStar = position.indexOf('STAR:') === 0;
            
            var hasInsignificant = false;
            
            // Check base skills for Insignificant
            if (isStar) {
                var starName = position.substring(5);
                if (starBaseSkillIds && starBaseSkillIds[starName]) {
                    var baseSkills = starBaseSkillIds[starName];
                    if (baseSkills.indexOf(INSIGNIFICANT_SKILL_ID) >= 0) {
                        hasInsignificant = true;
                    }
                }
            } else {
                if (currentRaceId >= 0 && raceBaseSkillIds[currentRaceId] && raceBaseSkillIds[currentRaceId][position]) {
                    var baseSkills = raceBaseSkillIds[currentRaceId][position];
                    if (baseSkills.indexOf(INSIGNIFICANT_SKILL_ID) >= 0) {
                        hasInsignificant = true;
                    }
                }
            }
            
            if (hasInsignificant) {
                playersWithInsignificant++;
            } else {
                playersWithoutInsignificant++;
            }
        }
    }
    
    // The rule: Cannot have MORE players with Insignificant than WITHOUT
    var isValid = playersWithInsignificant <= playersWithoutInsignificant;
    
    // Update display in Team Summary
    var summaryBox = document.querySelector('.summary-box h3');
    if (summaryBox) {
        // Remove old insignificant warning if exists
        var oldWarning = document.getElementById('insignificant-warning');
        if (oldWarning) {
            oldWarning.remove();
        }
        
        // Add warning if rule is violated
        if (!isValid && playersWithInsignificant > 0) {
            var warning = document.createElement('div');
            warning.id = 'insignificant-warning';
            warning.style.cssText = 'margin-top: 10px; padding: 8px; border-radius: 4px; background-color: #f8d7da; color: #721c24; border: 2px solid #dc3545; font-weight: bold; font-size: 12px;';
            warning.innerHTML = '⚠ Insignificant Rule Violation: You have ' + playersWithInsignificant + 
                               ' player(s) with Insignificant but only ' + playersWithoutInsignificant + 
                               ' without. Maximum currently allowed: ' + playersWithoutInsignificant;
            
            summaryBox.parentNode.insertBefore(warning, summaryBox.nextSibling);
        }
    }
    
    return isValid;
}

function validateTournamentCompliance() {
    // Get tournament settings from PHP
    var ts = <?php echo json_encode(isset($team_data['tournament_settings']) ? $team_data['tournament_settings'] : array()); ?>;
    
    // Check if any tournament settings are actually active
    var hasActiveSettings = false;
    if (ts && Object.keys(ts).length > 0) {
        // Check if any non-zero/non-false settings exist
        for (var key in ts) {
            if (ts[key] && ts[key] !== '0' && ts[key] !== 0 && ts[key] !== false) {
                hasActiveSettings = true;
                break;
            }
        }
    }
    
    if (!hasActiveSettings) {
		return; // No tournament settings active - don't show any validation
	}
    
    // Get current team stats
    var playerRows = document.getElementsByClassName('player-row');
    var totalValue = 0;
    var skillsBudgetSpent = 0;
    var sppBudgetSpent = 0;
    var primarySkills = 0;
    var secondarySkills = 0;
    var eliteSkills = 0;
    var totalExtraSkills = 0;
    var totalStatImprovements = 0;
    var playersWithStacking = 0;
    var maxEnhancementsOnAnyPlayer = 0;
    var hasSecondaryWithStacking = false;
    var hasStatWithSkill = false;
    
    // Stat improvement counts
    var statCounts = {ma: 0, st: 0, ag: 0, pa: 0, av: 0, total: 0};
    
    // Calculate totals for all players
    for (var i = 0; i < playerRows.length; i++) {
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        var valueCell = playerRows[i].getElementsByClassName('value-cell')[0];
        
        if (posSelect && posSelect.value) {
            var position = posSelect.value;
            var isStar = position.indexOf('STAR:') === 0;
            
            // Add to total value
            if (valueCell) {
                var valueText = valueCell.textContent;
                if (valueText && valueText !== '-') {
                    var value = parseFloat(valueText.replace('k', ''));
                    totalValue += value;
                }
            }
            
            // Count skills and stats for non-stars
            if (!isStar) {
                var extraSkillsInput = document.getElementById('player-' + i + '-extra-skills');
                var extraSkillIds = extraSkillsInput ? JSON.parse(extraSkillsInput.value || '[]') : [];
                
                var statIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
                var statIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
                
                // Count stat improvements for this player
                var playerStatTotal = (statIncreases.ma || 0) + (statIncreases.st || 0) + (statIncreases.ag || 0) + (statIncreases.pa || 0) + (statIncreases.av || 0);
                totalStatImprovements += playerStatTotal;
                
                // Update team-wide stat counts
                statCounts.ma += statIncreases.ma || 0;
                statCounts.st += statIncreases.st || 0;
                statCounts.ag += statIncreases.ag || 0;
                statCounts.pa += statIncreases.pa || 0;
                statCounts.av += statIncreases.av || 0;
                statCounts.total += playerStatTotal;
                
                // Count total enhancements for this player
                var playerTotalEnhancements = extraSkillIds.length + playerStatTotal;
                totalExtraSkills += extraSkillIds.length;
                
                if (playerTotalEnhancements > maxEnhancementsOnAnyPlayer) {
                    maxEnhancementsOnAnyPlayer = playerTotalEnhancements;
                }
                
                // Check if player has stacking (2+ enhancements)
                if (playerTotalEnhancements >= 2) {
                    playersWithStacking++;
                }
                
                // Check for stat+skill stacking violation
                if (extraSkillIds.length > 0 && playerStatTotal > 0) {
                    hasStatWithSkill = true;
                }
                
                // Count secondary skills and check for secondary stacking violation
                var secondaryCount = 0;
                var primaryCats = raceSkillCats[currentRaceId][position].primary;
                
                for (var j = 0; j < extraSkillIds.length; j++) {
                    var skillId = parseInt(extraSkillIds[j]);
                    if (skillId) {
                        // Check if elite
                        if (eliteSkillIds.indexOf(skillId) >= 0) {
                            eliteSkills++;
                        }
                        
                        // Check if primary or secondary
                        var skillCat = skillCategories[skillId];
                        var isPrimary = primaryCats.indexOf(skillCat) >= 0;
                        
                        if (isPrimary) {
                            primarySkills++;
                        } else {
                            secondarySkills++;
                            secondaryCount++;
                        }
                        
                        // Calculate skills budget spent (if applicable)
                        if (ts.skills_cost_gold || ts.skills_add_player_value) {
                            var skillCost = isPrimary ? (parseInt(ts.primary_skill_cost) || 20) : (parseInt(ts.secondary_skill_cost) || 40);
                            if (eliteSkillIds.indexOf(skillId) >= 0) {
                                skillCost += parseInt(ts.elite_skill_cost) || 10;
                            }
                            skillsBudgetSpent += skillCost;
                        }
                        
                        // Calculate SPP spent (if applicable)
                        if (ts.skills_cost_spp) {
                            // SPP costs would be calculated here based on stacking rules
                            // For now, using simplified calculation
                            var sppCost = 6; // Simplified - would need full stacking logic
                            sppBudgetSpent += sppCost;
                        }
                    }
                }
                
                // Check for secondary stacking violation
                if (secondaryCount >= 1 && playerTotalEnhancements > 1) {
                    hasSecondaryWithStacking = true;
                }
            }
        }
    }
    
    // Add sideline costs to total value
	if (currentRaceId >= 0) {
		var rrSelect = document.getElementById('sideline_rerolls');
		if (rrSelect) {
			var rrQty = parseInt(rrSelect.value) || 0;
			var rrCost = raceRerollCost[currentRaceId] || 0;
			var rrMultiplier = 1;
			
			if (ts.override_sideline_costs && ts.reroll_cost_multiplier) {
				rrMultiplier = parseFloat(ts.reroll_cost_multiplier) || 1;
			}
			
			totalValue += Math.round(rrQty * rrCost * rrMultiplier);
		}
		
		var fansSelect = document.getElementById('sideline_fans');
		var freeFirstDFCheck = document.getElementById('sideline_free_first_df');
		if (fansSelect && freeFirstDFCheck) {
			var fansQty = parseInt(fansSelect.value) || 0;
			var freeFirstDF = freeFirstDFCheck.checked;
			var fanCost = gamePrices.fan_factor / 1000;
			
			if (ts.override_sideline_costs && ts.cost_dedicated_fans) {
				fanCost = parseInt(ts.cost_dedicated_fans) || fanCost;
			}
			
			var fan_cost = freeFirstDF ? ((fansQty - 1) * fanCost) : (fansQty * fanCost);
			totalValue += fan_cost;
		}
		
		var coachesSelect = document.getElementById('sideline_coaches');
		if (coachesSelect) {
			var coachesQty = parseInt(coachesSelect.value) || 0;
			var coachCost = gamePrices.ass_coaches / 1000;
			
			if (ts.override_sideline_costs && ts.cost_ass_coaches) {
				coachCost = parseInt(ts.cost_ass_coaches) || coachCost;
			}
			
			totalValue += coachesQty * coachCost;
		}
		
		var cheerSelect = document.getElementById('sideline_cheerleaders');
		if (cheerSelect) {
			var cheerQty = parseInt(cheerSelect.value) || 0;
			var cheerCost = gamePrices.cheerleaders / 1000;
			
			if (ts.override_sideline_costs && ts.cost_cheerleaders) {
				cheerCost = parseInt(ts.cost_cheerleaders) || cheerCost;
			}
			
			totalValue += cheerQty * cheerCost;
		}
		
		var apothCheck = document.getElementById('sideline_apothecary');
		if (apothCheck && apothCheck.checked) {
			var apothCost = gamePrices.apothecary / 1000;
			
			if (ts.override_sideline_costs && ts.cost_apothecary) {
				apothCost = parseInt(ts.cost_apothecary) || apothCost;
			}
			
			totalValue += apothCost;
		}
	}
    
    // Add inducement costs
    var inducementSelects = document.querySelectorAll('select[name^="inducements"]');
    for (var i = 0; i < inducementSelects.length; i++) {
        var select = inducementSelects[i];
        var qty = parseInt(select.value) || 0;
        if (qty > 0) {
            var indName = select.name.replace('inducements[', '').replace(']', '').replace(/_/g, ' ');
            if (inducements[indName]) {
                var cost = inducements[indName].cost;
                var hasReduction = false;
                var teamRules = currentTeamRules || [];
                
                if (inducements[indName].reducedCostRules.length > 0) {
                    for (var j = 0; j < teamRules.length; j++) {
                        if (inducements[indName].reducedCostRules.indexOf(teamRules[j]) >= 0) {
                            cost = inducements[indName].reducedCost;
                            hasReduction = true;
                            break;
                        }
                    }
                }
                
                if (!hasReduction && inducements[indName].reducedCostRaces.length > 0) {
                    if (inducements[indName].reducedCostRaces.indexOf(currentRaceId) >= 0) {
                        cost = inducements[indName].reducedCost;
                    }
                }
                
                totalValue += (cost / 1000) * qty;
            }
        }
    }
    
    // Count players for basic validation
	var playerRows = document.getElementsByClassName('player-row');
	var totalPlayersCount = 0;
	var regularPlayersCount = 0;
	var starsCount = 0;

	for (var i = 0; i < playerRows.length; i++) {
		var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
		if (posSelect && posSelect.value) {
			totalPlayersCount++;
			if (posSelect.value.indexOf('STAR:') === 0) {
				starsCount++;
			} else {
				regularPlayersCount++;
			}
		}
	}

	// NOW VALIDATE AGAINST ALL TOURNAMENT SETTINGS AND UPDATE EXISTING INDICATORS
	var allValid = true;

	// FIRST: Check basic 11-player requirement (ALWAYS enforced)
	if (ts.enforce_11_regular_players) {
		// Must have 11 regular players (excluding stars)
		if (regularPlayersCount < 11) {
			allValid = false;
		}
	} else {
		// Must have 11 total players (can include stars)
		if (totalPlayersCount < 11) {
			allValid = false;
		}
	}
    
    // 1. Max Treasury
    var treasuryIcon = document.getElementById('treasury-icon');
    var treasuryStatus = document.getElementById('tournament-treasury-status');
    if (treasuryIcon && treasuryStatus && ts.max_treasury && parseInt(ts.max_treasury) > 0) {
        var maxTreasury = parseInt(ts.max_treasury);
        if (totalValue <= maxTreasury) {
            treasuryIcon.textContent = '✓';
            treasuryIcon.style.color = '#00cc00';
            treasuryStatus.style.color = '#000';
            treasuryStatus.title = '';
        } else {
            treasuryIcon.textContent = '✗';
            treasuryIcon.style.color = '#cc0000';
            treasuryStatus.style.color = '#cc0000';
            treasuryStatus.title = 'Total: ' + Math.round(totalValue) + 'k / Max: ' + maxTreasury + 'k';
            allValid = false;
        }
    }
    
    // 2. Skills Budget
    var budgetIcon = document.getElementById('skills-budget-icon');
    var budgetStatus = document.getElementById('tournament-skills-budget-status');
    if (budgetIcon && budgetStatus && ts.skills_separate_budget && ts.skills_budget && parseInt(ts.skills_budget) > 0) {
        var maxSkillsBudget = parseInt(ts.skills_budget);
        if (skillsBudgetSpent <= maxSkillsBudget) {
            budgetIcon.textContent = '✓';
            budgetIcon.style.color = '#00cc00';
            budgetStatus.style.color = '#000';
            budgetStatus.title = '';
        } else {
            budgetIcon.textContent = '✗';
            budgetIcon.style.color = '#cc0000';
            budgetStatus.style.color = '#cc0000';
            budgetStatus.title = 'Spent: ' + skillsBudgetSpent + 'k / Max: ' + maxSkillsBudget + 'k';
            allValid = false;
        }
    }
    
    // 3. Max Primary Skills
    var primaryIcon = document.getElementById('max-primary-icon');
    var primaryStatus = document.getElementById('tournament-max-primary-status');
    if (primaryIcon && primaryStatus && ts.max_primary_skills && parseInt(ts.max_primary_skills) > 0) {
        var maxPrimary = parseInt(ts.max_primary_skills);
        if (primarySkills <= maxPrimary) {
            primaryIcon.textContent = '✓';
            primaryIcon.style.color = '#00cc00';
            primaryStatus.style.color = '#000';
            primaryStatus.title = '';
        } else {
            primaryIcon.textContent = '✗';
            primaryIcon.style.color = '#cc0000';
            primaryStatus.style.color = '#cc0000';
            primaryStatus.title = 'Current: ' + primarySkills + ' / Max: ' + maxPrimary;
            allValid = false;
        }
    }
    
    // 4. Max Secondary Skills
    var secondaryIcon = document.getElementById('max-secondary-icon');
    var secondaryStatus = document.getElementById('tournament-max-secondary-status');
    if (secondaryIcon && secondaryStatus && ts.max_secondary_skills && parseInt(ts.max_secondary_skills) > 0) {
        var maxSecondary = parseInt(ts.max_secondary_skills);
        if (secondarySkills <= maxSecondary) {
            secondaryIcon.textContent = '✓';
            secondaryIcon.style.color = '#00cc00';
            secondaryStatus.style.color = '#000';
            secondaryStatus.title = '';
        } else {
            secondaryIcon.textContent = '✗';
            secondaryIcon.style.color = '#cc0000';
            secondaryStatus.style.color = '#cc0000';
            secondaryStatus.title = 'Current: ' + secondarySkills + ' / Max: ' + maxSecondary;
            allValid = false;
        }
    }
    
    // 5. Max Elite Skills
    var eliteIcon = document.getElementById('max-elite-icon');
    var eliteStatus = document.getElementById('tournament-max-elite-status');
    if (eliteIcon && eliteStatus && ts.max_elite_skills && parseInt(ts.max_elite_skills) > 0) {
        var maxElite = parseInt(ts.max_elite_skills);
        if (eliteSkills <= maxElite) {
            eliteIcon.textContent = '✓';
            eliteIcon.style.color = '#00cc00';
            eliteStatus.style.color = '#000';
            eliteStatus.title = '';
        } else {
            eliteIcon.textContent = '✗';
            eliteIcon.style.color = '#cc0000';
            eliteStatus.style.color = '#cc0000';
            eliteStatus.title = 'Current: ' + eliteSkills + ' / Max: ' + maxElite;
            allValid = false;
        }
    }
    
    // 6. Max Players with Stacked Skills
    var stackedPlayersIcon = document.getElementById('max-stacked-players-icon');
    var stackedPlayersStatus = document.getElementById('tournament-max-stacked-players-status');
    if (stackedPlayersIcon && stackedPlayersStatus && ts.max_players_multi_skills && parseInt(ts.max_players_multi_skills) > 0) {
        var maxPlayersStacked = parseInt(ts.max_players_multi_skills);
        if (playersWithStacking <= maxPlayersStacked) {
            stackedPlayersIcon.textContent = '✓';
            stackedPlayersIcon.style.color = '#00cc00';
            stackedPlayersStatus.style.color = '#000';
            stackedPlayersStatus.title = '';
        } else {
            stackedPlayersIcon.textContent = '✗';
            stackedPlayersIcon.style.color = '#cc0000';
            stackedPlayersStatus.style.color = '#cc0000';
            stackedPlayersStatus.title = 'Current: ' + playersWithStacking + ' / Max: ' + maxPlayersStacked;
            allValid = false;
        }
    }
    
    // 7. Max Stacked per Player
    var stackedCountIcon = document.getElementById('max-stacked-count-icon');
    var stackedCountStatus = document.getElementById('tournament-max-stacked-count-status');
    if (stackedCountIcon && stackedCountStatus && ts.max_stacked_skills_count && parseInt(ts.max_stacked_skills_count) > 0) {
        var maxStacked = parseInt(ts.max_stacked_skills_count);
        if (maxEnhancementsOnAnyPlayer <= maxStacked) {
            stackedCountIcon.textContent = '✓';
            stackedCountIcon.style.color = '#00cc00';
            stackedCountStatus.style.color = '#000';
            stackedCountStatus.title = '';
        } else {
            stackedCountIcon.textContent = '✗';
            stackedCountIcon.style.color = '#cc0000';
            stackedCountStatus.style.color = '#cc0000';
            stackedCountStatus.title = 'Max on any player: ' + maxEnhancementsOnAnyPlayer + ' / Limit: ' + maxStacked;
            allValid = false;
        }
    }
    
    // 8. Secondary Stacking Rule
    var secondaryStackIcon = document.getElementById('secondary-stack-icon');
    var secondaryStackStatus = document.getElementById('tournament-secondary-stack-status');
    if (secondaryStackIcon && secondaryStackStatus && ts.allow_secondary_stacking !== undefined && !ts.allow_secondary_stacking) {
        if (!hasSecondaryWithStacking) {
            secondaryStackIcon.textContent = '✓';
            secondaryStackIcon.style.color = '#00cc00';
            secondaryStackStatus.style.color = '#000';
            secondaryStackStatus.title = '';
        } else {
            secondaryStackIcon.textContent = '✗';
            secondaryStackIcon.style.color = '#cc0000';
            secondaryStackStatus.style.color = '#cc0000';
            secondaryStackStatus.title = 'Player has secondary skill with other enhancements';
            allValid = false;
        }
    }
    
    // 9. Stat+Skill Stacking Rule
    var statSkillStackIcon = document.getElementById('stat-skill-stack-icon');
    var statSkillStackStatus = document.getElementById('tournament-stat-skill-stack-status');
    if (statSkillStackIcon && statSkillStackStatus && ts.allow_stat_increases && ts.allow_stat_skill_stacking !== undefined && !ts.allow_stat_skill_stacking) {
        if (!hasStatWithSkill) {
            statSkillStackIcon.textContent = '✓';
            statSkillStackIcon.style.color = '#00cc00';
            statSkillStackStatus.style.color = '#000';
            statSkillStackStatus.title = '';
        } else {
            statSkillStackIcon.textContent = '✗';
            statSkillStackIcon.style.color = '#cc0000';
            statSkillStackStatus.style.color = '#cc0000';
            statSkillStackStatus.title = 'Player has both stat improvements and skills';
            allValid = false;
        }
    }
	
	// Check enforce 11 regular players rule
	var enforce11RegularIcon = document.getElementById('enforce-11-regular-icon');
	var enforce11RegularStatus = document.getElementById('tournament-enforce-11-regular-status');
	if (enforce11RegularIcon && enforce11RegularStatus && ts.enforce_11_regular_players) {
		// Count regular players (non-stars)
		var regularPlayerCount = 0;
		for (var i = 0; i < playerRows.length; i++) {
			var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
			if (posSelect && posSelect.value && posSelect.value.indexOf('STAR:') !== 0) {
				regularPlayerCount++;
			}
		}
		
		if (regularPlayerCount >= 11) {
			enforce11RegularIcon.textContent = '✓';
			enforce11RegularIcon.style.color = '#00cc00';
			enforce11RegularStatus.style.color = '#000';
			enforce11RegularStatus.title = '';
		} else {
			enforce11RegularIcon.textContent = '✗';
			enforce11RegularIcon.style.color = '#cc0000';
			enforce11RegularStatus.style.color = '#cc0000';
			enforce11RegularStatus.title = 'Regular players: ' + regularPlayerCount + ' / Min: 11 (excluding stars)';
			allValid = false;
		}
	}

// 10. Stat Improvements - Individual Stats
    
    // 10. Stat Improvements - Individual Stats
	if (ts.allow_stat_increases) {
        var statChecks = [
            {id: 'ma', name: 'MA', max: parseInt(ts.max_ma_increases) || 0},
            {id: 'st', name: 'ST', max: parseInt(ts.max_st_increases) || 0},
            {id: 'ag', name: 'AG', max: parseInt(ts.max_ag_increases) || 0},
            {id: 'pa', name: 'PA', max: parseInt(ts.max_pa_increases) || 0},
            {id: 'av', name: 'AV', max: parseInt(ts.max_av_increases) || 0}
        ];
        
        for (var i = 0; i < statChecks.length; i++) {
            var check = statChecks[i];
            var icon = document.getElementById('stat-' + check.id + '-icon');
            var status = document.getElementById('tournament-stat-' + check.id + '-status');
            
            if (icon && status && check.max > 0) {
                if (statCounts[check.id] <= check.max) {
                    icon.textContent = '✓';
                    icon.style.color = '#00cc00';
                    status.style.color = '#000';
                    status.title = '';
                } else {
                    icon.textContent = '✗';
                    icon.style.color = '#cc0000';
                    status.style.color = '#cc0000';
                    status.title = 'Current: ' + statCounts[check.id] + ' / Max: ' + check.max;
                    allValid = false;
                }
            }
        }
        
        // 11. Total Stat Improvements
        var totalStatIcon = document.getElementById('total-stat-icon');
        var totalStatStatus = document.getElementById('tournament-total-stat-status');
        if (totalStatIcon && totalStatStatus && ts.max_total_stat_increases && parseInt(ts.max_total_stat_increases) > 0) {
            var maxTotal = parseInt(ts.max_total_stat_increases);
            if (statCounts.total <= maxTotal) {
                totalStatIcon.textContent = '✓';
                totalStatIcon.style.color = '#00cc00';
                totalStatStatus.style.color = '#000';
                totalStatStatus.title = '';
            } else {
                totalStatIcon.textContent = '✗';
                totalStatIcon.style.color = '#cc0000';
                totalStatStatus.style.color = '#cc0000';
                totalStatStatus.title = 'Current: ' + statCounts.total + ' / Max: ' + maxTotal;
                allValid = false;
            }
        }
    }
    
    // Update overall Team Valid/Invalid display if it exists
    var overallIcon = document.getElementById('team-valid-icon');
    var overallStatus = document.getElementById('team-valid-status');
    if (overallIcon && overallStatus) {
        if (allValid) {
            overallIcon.textContent = '✓';
            overallIcon.style.color = '#00cc00';
            overallStatus.textContent = 'Team Valid';
            overallStatus.style.color = '#00cc00';
        } else {
            overallIcon.textContent = '✗';
            overallIcon.style.color = '#cc0000';
            overallStatus.textContent = 'Team Invalid';
            overallStatus.style.color = '#cc0000';
        }
    }
}

function updatePlayerValueTotal(totalValue) {
    var totalCell = document.getElementById('player-value-total');
    if (totalCell) {
        totalCell.textContent = Math.round(totalValue) + 'k';
    }
}

function updateInducementsDisplay() {
    var container = document.getElementById('inducements-container');
    if (!container || currentRaceId < 0) return;
    
    var teamRules = currentTeamRules || [];
    var html = '<div class="tableResponsive"><table class="builder-table"><tr><th>Inducement</th><th>Cost</th><th>Quantity</th></tr>';
    
    for (var indName in inducements) {
        var ind = inducements[indName];
        
        // Check if available for this team
        if (ind.teamrules.length > 0 && ind.teamrules.indexOf(0) < 0) {
            var hasMatch = false;
            for (var i = 0; i < teamRules.length; i++) {
                if (ind.teamrules.indexOf(teamRules[i]) >= 0) {
                    hasMatch = true;
                    break;
                }
            }
            if (!hasMatch) continue;
        }
        
        // Determine cost and max
        var cost = ind.cost;
        var hasReduction = false;
        
        if (ind.reducedCostRules.length > 0) {
            for (var i = 0; i < teamRules.length; i++) {
                if (ind.reducedCostRules.indexOf(teamRules[i]) >= 0) {
                    cost = ind.reducedCost;
                    hasReduction = true;
                    break;
                }
            }
        }
        
        if (!hasReduction && ind.reducedCostRaces.length > 0) {
            if (ind.reducedCostRaces.indexOf(currentRaceId) >= 0) {
                cost = ind.reducedCost;
                hasReduction = true;
            }
        }
        
        var maxQty = ind.max;
        if (hasReduction && ind.reducedMax) {
            maxQty = ind.reducedMax;
        }
        
        var costDisplay = (cost / 1000) + 'k';
        var safeIndName = indName.replace(/ /g, '_');
        
        // Get saved value
        var savedQty = savedInducements[safeIndName] || 0;
        
        html += '<tr><td>' + indName + ' (0-' + maxQty + ')</td><td>' + costDisplay + '</td><td>';
        html += '<select name="inducements[' + safeIndName + ']" onchange="updateCounters()">';
        for (var i = 0; i <= maxQty; i++) {
            var sel = (i == savedQty) ? ' selected' : '';
            html += '<option value="' + i + '"' + sel + '>' + i + '</option>';
        }
        html += '</select></td></tr>';
    }
    
    html += '</table></div>';
    container.innerHTML = html;
}

function updateSidelineDisplay() {
    if (currentRaceId < 0) {
        document.getElementById('sideline-content').innerHTML = '<p style="color: #666;">Select a race to configure sideline staff and equipment.</p>';
        return;
    }
    
    var teamRules = currentTeamRules || [];
    var hasApoth = teamRules.indexOf(20) < 0 || currentRaceId === 18;
    
    // Get tournament settings for overrides
    var ts = <?php echo json_encode(isset($team_data['tournament_settings']) ? $team_data['tournament_settings'] : array()); ?>;
    
    // Determine costs based on overrides
	var rrCost = raceRerollCost[currentRaceId] || 0;
	var rrMultiplier = 1;
	if (ts.override_sideline_costs) {
		if (ts.reroll_cost_multiplier) {
			rrMultiplier = parseFloat(ts.reroll_cost_multiplier) || 1;
		}
	}
    
    var fanCost = gamePrices.fan_factor / 1000;
	if (ts.override_sideline_costs && ts.cost_dedicated_fans) {
		fanCost = parseInt(ts.cost_dedicated_fans) || fanCost;
	}

	var coachCost = gamePrices.ass_coaches / 1000;
	if (ts.override_sideline_costs && ts.cost_ass_coaches) {
		coachCost = parseInt(ts.cost_ass_coaches) || coachCost;
	}

	var cheerCost = gamePrices.cheerleaders / 1000;
	if (ts.override_sideline_costs && ts.cost_cheerleaders) {
		cheerCost = parseInt(ts.cost_cheerleaders) || cheerCost;
	}

	var apothCost = gamePrices.apothecary / 1000;
	if (ts.override_sideline_costs && ts.cost_apothecary) {
		apothCost = parseInt(ts.cost_apothecary) || apothCost;
	}
    
    var html = '<div class="tableResponsive"><table style="width: 100%; border-collapse: collapse;"><tr>';
    html += '<td style="vertical-align: top; width: 50%;">';
    html += '<div class="tableResponsive"><table class="sideline-table" style="border-collapse: collapse;">';
    
    // LEFT COLUMN
    // Re-rolls
    var maxRerolls = gameMaxValues.max_rerolls || 8;
    html += '<tr style="height: 25px;"><td style="padding: 2px 5px;"><b>Re-rolls (0-' + maxRerolls + '):</b></td><td style="padding: 2px 5px;">';
    html += '<select name="sideline_rerolls" id="sideline_rerolls" onchange="updateCountersFromSideline()">';
    for (var i = 0; i <= maxRerolls; i++) {
        var cost = Math.round(i * rrCost * rrMultiplier);
        var sel = (savedSideline.rerolls == i) ? ' selected' : '';
        html += '<option value="' + i + '"' + sel + '>' + i + ' (' + cost + 'k)</option>';
    }
    html += '</select></td></tr>';
    
    // Dedicated Fans
    var freeFirstDFCheck = document.getElementById('sideline_free_first_df');
    var freeFirstDF = freeFirstDFCheck ? freeFirstDFCheck.checked : (savedSideline.free_first_df == 1);
    var minFans = freeFirstDF ? 1 : 0;
    var initialFF = gameMaxValues.initial_fan_factor || 1;
    var maxIniFF = gameMaxValues.max_ini_fan_factor || 2;
    var maxFans = initialFF + maxIniFF;

    html += '<tr style="height: 25px;"><td style="padding: 2px 5px;"><b>Dedicated Fans (' + minFans + '-' + maxFans + '):</b></td><td style="padding: 2px 5px;">';
    html += '<select name="sideline_fans" id="sideline_fans" onchange="updateCountersFromSideline()">';
    for (var i = minFans; i <= maxFans; i++) {
        var cost = freeFirstDF ? ((i - 1) * fanCost) : (i * fanCost);
        var sel = (savedSideline.dedicated_fans == i) ? ' selected' : '';
        html += '<option value="' + i + '"' + sel + '>' + i + ' (' + cost + 'k)</option>';
    }
    html += '</select> ';
    html += '<input type="checkbox" name="sideline_free_first_df" id="sideline_free_first_df" value="1"' + (freeFirstDF ? ' checked' : '') + ' onchange="toggleFreeFirstDF()" /> ';
    html += '<small>Free first DF</small>';
    html += '</td></tr>';
    
    // Apothecary
    if (hasApoth) {
        var checked = (savedSideline.apothecary == 1) ? ' checked' : '';
        html += '<tr id="apothecary-section" style="height: 25px;"><td style="padding: 2px 5px;"><b>Apothecary (0-1):</b></td><td style="padding: 2px 5px;">';
        html += '<input type="checkbox" name="sideline_apothecary" id="sideline_apothecary" value="1"' + checked + ' onchange="updateCountersFromSideline()" /> (' + apothCost + 'k)';
        html += '</td></tr>';
    }
    
    html += '</table></dic></td>';
    
    // RIGHT COLUMN
    html += '<td style="vertical-align: top; width: 50%; padding-left: 20px;">';
    html += '<table class="sideline-table" style="border-collapse: collapse;">';
    
    // Assistant Coaches
    var maxCoaches = gameMaxValues.max_ass_coaches || 6;
    html += '<tr style="height: 25px;"><td style="padding: 2px 5px;"><b>Assistant Coaches (0-' + maxCoaches + '):</b></td><td style="padding: 2px 5px;">';
    html += '<select name="sideline_coaches" id="sideline_coaches" onchange="updateCountersFromSideline()">';
    for (var i = 0; i <= maxCoaches; i++) {
        var cost = i * coachCost;
        var sel = (savedSideline.ass_coaches == i) ? ' selected' : '';
        html += '<option value="' + i + '"' + sel + '>' + i + ' (' + cost + 'k)</option>';
    }
    html += '</select></td></tr>';
    
    // Cheerleaders
    var maxCheerleaders = gameMaxValues.max_cheerleaders || 6;
    html += '<tr style="height: 25px;"><td style="padding: 2px 5px;"><b>Cheerleaders (0-' + maxCheerleaders + '):</b></td><td style="padding: 2px 5px;">';
    html += '<select name="sideline_cheerleaders" id="sideline_cheerleaders" onchange="updateCountersFromSideline()">';
    for (var i = 0; i <= maxCheerleaders; i++) {
        var cost = i * cheerCost;
        var sel = (savedSideline.cheerleaders == i) ? ' selected' : '';
        html += '<option value="' + i + '"' + sel + '>' + i + ' (' + cost + 'k)</option>';
    }
    html += '</select></td></tr>';
    
    html += '</table></dic></td></tr></table></dic>';
    
    document.getElementById('sideline-content').innerHTML = html;
}

function toggleFreeFirstDF() {
    var checkbox = document.getElementById('sideline_free_first_df');
    var fansSelect = document.getElementById('sideline_fans');
    var currentFans = parseInt(fansSelect.value) || 1;
    
    // Preserve the checkbox state by storing it temporarily
    var isChecked = checkbox.checked;
    
    // Update the display
    updateSidelineDisplay();
    
    // Restore checkbox state and adjust fans dropdown if needed
    var newCheckbox = document.getElementById('sideline_free_first_df');
    newCheckbox.checked = isChecked;
    
    var newFansSelect = document.getElementById('sideline_fans');
    if (isChecked && currentFans == 0) {
        // When checking the box, set to 1 (free)
        newFansSelect.value = 1;
    } else if (!isChecked) {
        // When unchecking the box, reset to 0
        newFansSelect.value = 0;
    } else {
        // Try to maintain the current value when already checked
        if (newFansSelect.querySelector('option[value="' + currentFans + '"]')) {
            newFansSelect.value = currentFans;
        }
    }
    
    updateCountersFromSideline();
}

function updateCountersFromSideline() {
    updateCounters();
}

function toggleInducements() {
    var content = document.getElementById('inducements-container');
    var link = document.getElementById('inducements-toggle');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        link.textContent = '[Collapse]';
        // Save expanded state
        document.getElementById('inducements-expanded').value = '1';
    } else {
        content.style.display = 'none';
        link.textContent = '[Expand]';
        // Save collapsed state
        document.getElementById('inducements-expanded').value = '0';
    }
}

function toggleRoster() {
    var content = document.getElementById('roster-container');
    var link = document.getElementById('roster-toggle');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        link.textContent = '[Collapse]';
        // Save expanded state
        document.getElementById('roster-expanded').value = '1';
    } else {
        content.style.display = 'none';
        link.textContent = '[Expand]';
        // Save collapsed state
        document.getElementById('roster-expanded').value = '0';
    }
}

function updateLeagueDropdown() {
    var container = document.getElementById('league-dropdown-container');
    if (!container || currentRaceId < 0) return;
    
    var leagues = raceLeagues[currentRaceId] || [];
    
    if (leagues.length <= 1) {
        container.style.display = 'none';
        document.getElementById('selected_league').value = leagues.length == 1 ? leagues[0] : -1;
    } else {
        container.style.display = 'inline';
        var select = document.getElementById('selected_league');
        select.innerHTML = '<option value="-1">-- Select League --</option>';
        
        for (var i = 0; i < leagues.length; i++) {
            var opt = document.createElement('option');
            opt.value = leagues[i];
            opt.textContent = leagueNames[leagues[i]] || ('League ' + leagues[i]);
            if (leagues[i] == savedLeague) {
                opt.selected = true;
            }
            select.appendChild(opt);
        }
    }
}

function updateFavRulesDropdown() {
    var container = document.getElementById('fav-rules-dropdown-container');
    if (!container || currentRaceId < 0) return;
    
    // Special case: Norse (14) + Chaos Clash (1) = auto Favoured of Khorne (15)
    var selectedLeague = parseInt(document.getElementById('selected_league').value);
    if (currentRaceId === 14 && selectedLeague === 1) {
        container.style.display = 'none';
        document.getElementById('selected_fav_rule').value = 15; // Auto-select Khorne
        return;
    }
    
    var specialRules = raceSpecialRules[currentRaceId] || [];
    var hasFavouredOf = specialRules.indexOf(12) >= 0; // 12 is generic "Favoured of..."
    
    if (!hasFavouredOf) {
        container.style.display = 'none';
        document.getElementById('selected_fav_rule').value = -1;
    } else {
        var favRules = raceFavRules[currentRaceId] || [];
        if (favRules.length == 0) {
            container.style.display = 'none';
            document.getElementById('selected_fav_rule').value = -1;
        } else {
            container.style.display = 'inline';
            var select = document.getElementById('selected_fav_rule');
            select.innerHTML = '<option value="-1">-- Select Favoured Rule --</option>';
            
            for (var i = 0; i < favRules.length; i++) {
                var opt = document.createElement('option');
                opt.value = favRules[i];
                // Strip "Favoured of " prefix for cleaner display
                var ruleName = specialRuleNames[favRules[i]] || ('Rule ' + favRules[i]);
                ruleName = ruleName.replace('Favoured of ', '');
                opt.textContent = ruleName;
                if (favRules[i] == savedFavRule) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            }
        }
    }
}

function updateEffectiveRules() {
    
    if (currentRaceId < 0) {
        currentTeamRules = [];
        return;
    }
    
    // Start with base special rules
    currentTeamRules = (raceSpecialRules[currentRaceId] || []).slice();
    
    // Check if team has leagues
    var teamLeagues = raceLeagues[currentRaceId] || [];
    
    // If team has exactly one league, automatically add it
    if (teamLeagues.length === 1) {
        if (currentTeamRules.indexOf(teamLeagues[0]) < 0) {
            currentTeamRules.push(teamLeagues[0]);
        }
    } else if (teamLeagues.length > 1) {
        // Multiple leagues - use selected value
        var selectedLeague = parseInt(document.getElementById('selected_league').value);
        if (selectedLeague >= 0) {
            if (currentTeamRules.indexOf(selectedLeague) < 0) {
                currentTeamRules.push(selectedLeague);
            }
        }
    }
    
    // Special case: Norse (14) + Chaos Clash (1) = auto Favoured of Khorne (15)
    var selectedLeague = parseInt(document.getElementById('selected_league').value);
    if (currentRaceId === 14 && (selectedLeague === 1 || teamLeagues.length === 1 && teamLeagues[0] === 1)) {
        // Remove generic Favoured of... (12) if present
        var idx = currentTeamRules.indexOf(12);
        if (idx >= 0) {
            currentTeamRules.splice(idx, 1);
        }
        // Add Favoured of Khorne (15)
        if (currentTeamRules.indexOf(15) < 0) {
            currentTeamRules.push(15);
        }
        return;
    }
    
    // Handle favoured rules
    var favRules = raceFavRules[currentRaceId] || [];
    
    // If team has exactly one favoured rule, automatically add it
    if (favRules.length === 1) {
        // Remove generic Favoured of... (12)
        var idx = currentTeamRules.indexOf(12);
        if (idx >= 0) {
            currentTeamRules.splice(idx, 1);
        }
        // Add the specific favoured rule
        if (currentTeamRules.indexOf(favRules[0]) < 0) {
            currentTeamRules.push(favRules[0]);
        }
    } else if (favRules.length > 1) {
        // Multiple favoured rules - use selected value
        var selectedFavRule = parseInt(document.getElementById('selected_fav_rule').value);
        if (selectedFavRule > 0) {
            // Remove generic Favoured of... (12)
            var idx = currentTeamRules.indexOf(12);
            if (idx >= 0) {
                currentTeamRules.splice(idx, 1);
            }
            // Add the specific favoured rule
            if (currentTeamRules.indexOf(selectedFavRule) < 0) {
                currentTeamRules.push(selectedFavRule);
            }
        }
    }
}

function onLeagueChange() {
    // Special case: Norse (14) + Chaos Clash (1) = auto Favoured of Khorne (15)
    var selectedLeague = parseInt(document.getElementById('selected_league').value);
    if (currentRaceId === 14 && selectedLeague === 1) {
        document.getElementById('selected_fav_rule').value = 15;
        var favContainer = document.getElementById('fav-rules-dropdown-container');
        if (favContainer) {
            favContainer.style.display = 'none';
        }
    } else if (currentRaceId === 14) {
        // Norse but not Chaos Clash - show fav rules dropdown
        updateFavRulesDropdown();
    }
    
    updateEffectiveRules();
    updateAllPlayerRows(); 
    updateInducementsDisplay();
	updateTeamDetailsDisplay();
}

function onFavRuleChange() {
    updateEffectiveRules();
    updateAllPlayerRows(); 
    updateInducementsDisplay();
	updateTeamDetailsDisplay();
}

function updateTeamDetailsDisplay() {
    var detailsRow = document.getElementById('team-details-row');
    
    if (currentRaceId < 0) {
        detailsRow.style.display = 'none';
        return;
    }
    
    detailsRow.style.display = '';
    
    // Display Tier
    var tierDisplay = document.getElementById('team-tier-display');
    var tier = raceTiers[currentRaceId] || 0;
    tierDisplay.innerHTML = '<b>Tier:</b> ' + tier;
    
    // Display League
    var leagueDisplay = document.getElementById('team-league-display');
    var teamLeagues = raceLeagues[currentRaceId] || [];
    
    if (teamLeagues.length === 0) {
        leagueDisplay.innerHTML = '';
    } else if (teamLeagues.length === 1) {
        var leagueName = leagueNames[teamLeagues[0]] || ('League ' + teamLeagues[0]);
        leagueDisplay.innerHTML = '<b>League:</b> ' + leagueName;
    } else {
        var selectedLeague = parseInt(document.getElementById('selected_league').value);
        if (selectedLeague >= 0) {
            var leagueName = leagueNames[selectedLeague] || ('League ' + selectedLeague);
            leagueDisplay.innerHTML = '<b>League:</b> ' + leagueName;
        } else {
            leagueDisplay.innerHTML = '<b>League:</b> (Select League)';
        }
    }
    
    // Display Special Rules
    var rulesDisplay = document.getElementById('team-special-rules-display');
    var effectiveRules = currentTeamRules || [];
    
    if (effectiveRules.length === 0) {
        rulesDisplay.innerHTML = '';
    } else {
        var ruleNames = [];
        for (var i = 0; i < effectiveRules.length; i++) {
            var ruleId = effectiveRules[i];
            var ruleName = 'Unknown Rule';
            
            // Try to find the rule name
            if (specialRuleNames && typeof specialRuleNames === 'object') {
                if (specialRuleNames[ruleId]) {
                    ruleName = specialRuleNames[ruleId];
                } else if (specialRuleNames[ruleId.toString()]) {
                    ruleName = specialRuleNames[ruleId.toString()];
                }
            }
            
            // Fallback: check if it's a league (leagues are also in the effective rules)
            if (ruleName === 'Unknown Rule' && leagueNames && leagueNames[ruleId]) {
                continue; // Skip leagues in special rules display since we show them separately
            }
            
            if (ruleName !== 'Unknown Rule') {
                ruleNames.push(ruleName);
            }
        }
        
        if (ruleNames.length > 0) {
            rulesDisplay.innerHTML = '<b>Special Rules:</b> ' + ruleNames.join(', ');
        } else {
            rulesDisplay.innerHTML = '<b>Special Rules:</b> None';
        }
    }
}

function updateRosterDisplay() {
    var rosterBox = document.getElementById('roster-box');
    var rosterContent = document.getElementById('roster-content');
    
    if (currentRaceId < 0) {
        rosterBox.style.display = 'none';
        return;
    }
    
    rosterBox.style.display = '';
    
    var html = '<div class="tableResponsive"><table class="builder-table" style="font-size: 11px;">';
    html += '<thead><tr>';
    html += '<th style="width: 30px;">Max Qty</th>';
    html += '<th style="width: 150px;">Position</th>';
    html += '<th style="width: 170px;">Keywords</th>';
    html += '<th style="width: 20px;">MA</th>';
    html += '<th style="width: 20px;">ST</th>';
    html += '<th style="width: 20px;">AG</th>';
    html += '<th style="width: 20px;">PA</th>';
    html += '<th style="width: 20px;">AV</th>';
    html += '<th>Skills & Traits</th>';
    html += '<th style="width: 50px;">Primary</th>';
    html += '<th style="width: 55px;">Secondary</th>';
    html += '<th style="width: 32px;">Price</th>';
    html += '</tr></thead>';
    html += '<tbody>';
    
    for (var i = 0; i < racePositions[currentRaceId].length; i++) {
        var pos = racePositions[currentRaceId][i];
        var posName = pos.name;
        var posData = raceStats[currentRaceId][posName];
        
        // Get max qty
        var maxQty = raceMaxQty[currentRaceId][posName];
        
        // Get keywords
        var keywords = '';
        if (raceKeywords[currentRaceId] && raceKeywords[currentRaceId][posName]) {
            var keywordIds = raceKeywords[currentRaceId][posName];
            var keywordNames = [];
            for (var k = 0; k < keywordIds.length; k++) {
                if (playerkeywordsididx[keywordIds[k]]) {
                    keywordNames.push(playerkeywordsididx[keywordIds[k]]);
                }
            }
            if (keywordNames.length > 0) {
                keywords = '(' + keywordNames.join(', ') + ')';
            }
        }
        
        // Get stats
        var ma = posData ? posData.ma : '-';
        var st = posData ? posData.st : '-';
        var ag = posData ? posData.ag : '-';
        var pa = posData ? posData.pa : '-';
        var av = posData ? posData.av : '-';
        
        // Format AG/PA/AV with +
        var agDisplay = (ag > 0 && ag < 7) ? ag + '+' : ag;
        var paDisplay = (pa > 0 && pa < 7) ? pa + '+' : pa;
        var avDisplay = (av > 0) ? av + '+' : av;
        
        // Get skills
        var skills = raceBaseSkills[currentRaceId][posName] || '-';
        
        // Get skill categories
        var skillCats = raceSkillCats[currentRaceId][posName];
        var primary = skillCats ? skillCats.primary.join(', ') : '';
        var secondary = skillCats ? skillCats.secondary.join(', ') : '';
        
        // Get price
        var price = racePrices[currentRaceId][posName];
        
        html += '<tr>';
        html += '<td style="text-align: center;">' + maxQty + '</td>';
        html += '<td>' + posName + '</td>';
        html += '<td style="font-size: 10px;">' + keywords + '</td>';
        html += '<td style="text-align: center;">' + ma + '</td>';
        html += '<td style="text-align: center;">' + st + '</td>';
        html += '<td style="text-align: center;">' + agDisplay + '</td>';
        html += '<td style="text-align: center;">' + paDisplay + '</td>';
        html += '<td style="text-align: center;">' + avDisplay + '</td>';
        html += '<td style="font-size: 10px;">' + skills + '</td>';
        html += '<td style="text-align: center; font-size: 10px;">' + primary + '</td>';
        html += '<td style="text-align: center; font-size: 10px;">' + secondary + '</td>';
        html += '<td style="text-align: right;">' + price + 'k</td>';
        html += '</tr>';
    }
    
    html += '</tbody></table></dic>';
    
    rosterContent.innerHTML = html;
}

function resetTeam() {
    if (confirm('Are you sure you want to reset the team? This will clear all data.')) {
        document.getElementById('inducements-expanded').value = '1';
		document.getElementById('action').value = 'reset';
        document.getElementById('team-form').submit();
    }
}

function saveTeam() {
    // This function is called when saving - add stat increases to form
    var playerRows = document.getElementsByClassName('player-row');
    var form = document.getElementById('team-form');
    
    // Remove old stat increase inputs
    var oldStatInputs = form.querySelectorAll('input[name^="player_stat_increases"]');
    for (var i = 0; i < oldStatInputs.length; i++) {
        oldStatInputs[i].parentNode.removeChild(oldStatInputs[i]);
    }
    
    // Add current stat increases
    for (var i = 0; i < playerRows.length; i++) {
        var statIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
        if (statIncreasesInput) {
            var statIncreases = JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}');
            
            var stats = ['ma', 'st', 'ag', 'pa', 'av'];
            for (var s = 0; s < stats.length; s++) {
                var stat = stats[s];
                var increase = statIncreases[stat] || 0;
                if (increase > 0) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'player_stat_increases[' + i + '][' + stat + ']';
                    input.value = increase;
                    form.appendChild(input);
                }
            }
        }
    }
}

function generatePDF() {
    
    var teamName = document.getElementById('team_name').value;
    var raceId = document.getElementById('race_id').value;
    
    if (!teamName) {
        alert('Please enter a team name');
        return false;
    }
    
    if (raceId < 0) {
        alert('Please select a race');
        return false;
    }
    
    var playerRows = document.getElementsByClassName('player-row');
    var regularPlayers = 0;
    
    for (var i = 0; i < playerRows.length; i++) {
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        if (posSelect && posSelect.value && posSelect.value.indexOf('STAR:') !== 0) {
            regularPlayers++;
        }
    }
    
    if (regularPlayers < 11) {
        alert('You must have at least 11 regular players.');
        return false;
    }
    
    // First save the team to session
    var form = document.getElementById('team-form');
    document.getElementById('action').value = 'save_team';
    
    // Submit form to save (stay on same page)
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            // After successful save, open PDF in new tab
            window.open('handler.php?type=tournamentteam_pdf', '_blank');
        }
    };
    
    // Serialize form data
    var formData = new FormData(form);
    var params = new URLSearchParams(formData).toString();
    xhr.send(params);
    
    return false;
}

function restorePairedStars() {
    var playerRows = document.getElementsByClassName('player-row');
    var processedRows = {};
    
    for (var i = 0; i < playerRows.length; i++) {
        if (processedRows[i]) continue;
        
        var posSelect = playerRows[i].getElementsByClassName('position-select')[0];
        if (!posSelect || !posSelect.value) continue;
        
        if (posSelect.value.indexOf('STAR:') === 0) {
            var starName = posSelect.value.substring(5);
            var pairedStarName = starPairsByName[starName];
            
            if (pairedStarName) {
                // This is a parent - find and lock the child
                var childStarValue = 'STAR:' + pairedStarName;
                
                for (var j = i + 1; j < playerRows.length; j++) {
                    var otherPosSelect = playerRows[j].getElementsByClassName('position-select')[0];
                    if (otherPosSelect && otherPosSelect.value === childStarValue) {
                        // Found the child - mark it
                        otherPosSelect.setAttribute('data-old-value', childStarValue);
                        otherPosSelect.setAttribute('data-is-paired-child', '1');
                        otherPosSelect.style.backgroundColor = '#f0f0f0';
                        otherPosSelect.style.cursor = 'not-allowed';
                        posSelect.setAttribute('data-old-value', posSelect.value);
                        processedRows[j] = true;
						updatePlayerDetails(j);
                        break;
                    }
                }
            } else if (starPairsReverse[starName]) {
                // This is a child - mark it
                posSelect.setAttribute('data-old-value', posSelect.value);
                posSelect.setAttribute('data-is-paired-child', '1');
                posSelect.style.backgroundColor = '#f0f0f0';
                posSelect.style.cursor = 'not-allowed';
                processedRows[i] = true;
				updatePlayerDetails(i);
            }
        }
    }
}

window.onload = function() {
    // Show/hide tournament settings based on whether race is selected
	var settingsPlaceholder = document.getElementById('tournament-settings-placeholder');
	var settingsToggle = document.getElementById('tournament-settings-toggle');

	if (currentRaceId < 0) {
		// No race selected - show placeholder, hide toggle
		if (settingsPlaceholder) settingsPlaceholder.style.display = 'block';
		if (settingsToggle) settingsToggle.style.display = 'none';
	} else {
		// Race is selected - hide placeholder, show toggle
		if (settingsPlaceholder) settingsPlaceholder.style.display = 'none';
		if (settingsToggle) settingsToggle.style.display = 'inline';
	}
	
    // Initialize teamPlayers object with current player data
    var playerRows = document.getElementsByClassName('player-row');
    window.teamPlayers = {};
    
    for (var i = 0; i < playerRows.length; i++) {
        var row = playerRows[i];
        var posSelect = row.getElementsByClassName('position-select')[0];
        
        if (posSelect && posSelect.value) {
            var position = posSelect.value;
            var isStar = position.indexOf('STAR:') === 0;
            
            // Get stats
            var stats = {ma: 0, st: 0, ag: 0, pa: 0, av: 0};
            
            if (isStar) {
                var starName = position.substring(5);
                if (starStats[starName]) {
                    stats = starStats[starName];
                }
            } else if (currentRaceId >= 0 && raceStats[currentRaceId] && raceStats[currentRaceId][position]) {
                stats = raceStats[currentRaceId][position];
            }
            
            // Get extra skills
            var extraSkillsInput = document.getElementById('player-' + i + '-extra-skills');
            var extraSkills = extraSkillsInput ? JSON.parse(extraSkillsInput.value || '[]') : [];
            
            // Get stat increases
            var statIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
            var statIncreases = statIncreasesInput ? JSON.parse(statIncreasesInput.value || '{"ma":0,"st":0,"ag":0,"pa":0,"av":0}') : {ma:0, st:0, ag:0, pa:0, av:0};
            
            window.teamPlayers[i] = {
                position: position,
                is_star: isStar,
                ma: stats.ma,
                st: stats.st,
                ag: stats.ag,
                pa: stats.pa,
                av: stats.av,
                extra_skills: extraSkills,
                stat_increases: statIncreases
            };
        }
    }
	
	// Initialize team rules if a race is selected
    if (currentRaceId >= 0) {
        updateLeagueDropdown();
        updateFavRulesDropdown();
        updateEffectiveRules();  // <-- DO THIS BEFORE updating player positions
        updateInducementsDisplay();
        updateSidelineDisplay();
        updateTeamDetailsDisplay();
		updateRosterDisplay();
    }
    
    var playerCount = document.getElementsByClassName('player-row').length;
    if (playerCount == 0) {
        for (var i = 0; i < 11; i++) {
            addPlayer();
        }
    } else {
        // THEN restore paired star relationships BEFORE updating positions
        restorePairedStars();
        
        // THEN refresh the dropdowns (paired children will be skipped)
        for (var i = 0; i < playerCount; i++) {
            updatePlayerPosition(i);
        }
        
        // FIX HANDLERS FOR SAVED PLAYERS
        fixMoveButtonHandlers();
    }
    
    // Restore inducements section state
	var expandedState = document.getElementById('inducements-expanded').value;
	var content = document.getElementById('inducements-container');
	var link = document.getElementById('inducements-toggle');

	if (expandedState === '0') {
		content.style.display = 'none';
		link.textContent = '[Expand]';
	} else {
		content.style.display = 'block';
		link.textContent = '[Collapse]';
	}

	// Restore roster section state
	var rosterExpandedState = document.getElementById('roster-expanded').value;
	var rosterContent = document.getElementById('roster-container');
	var rosterLink = document.getElementById('roster-toggle');

	if (rosterExpandedState === '0') {
		rosterContent.style.display = 'none';
		rosterLink.textContent = '[Expand]';
	} else {
		rosterContent.style.display = 'block';
		rosterLink.textContent = '[Collapse]';
	}

	updateCounters();
	
	// Update all player stat displays
    for (var i = 0; i < playerRows.length; i++) {
        var statIncreasesInput = document.getElementById('player-' + i + '-stat-increases');
        if (statIncreasesInput) {
            updatePlayerStatsDisplay(i);
        }
    }
}

;
</script>

<style>
.tournament-builder {
    max-width: 1400px;
    margin: 20px auto;
    color: #000;
}
.summary-box {
    background: #ffffcc;
    border: 2px solid #ff9900;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    color: #000;
}
.summary-box h3 {
    margin-top: 0;
    color: #000;
}
.summary-item {
    display: inline-block;
    margin-right: 30px;
    font-weight: bold;
}
.section-box {
    background: #f5f5f5;
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    color: #000;
}
.section-title {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 2px solid #333;
    color: #000;
}
table.builder-table {
    width: 100%;
    border-collapse: collapse;
}
table.builder-table th {
    background: #ddd;
    padding: 8px;
    text-align: left;
    border: 1px solid #999;
    color: #000;
}
table.builder-table td {
    padding: 5px;
    border: 1px solid #ccc;
    color: #000;
}
.button-bar {
    margin: 15px 0;
    text-align: center;
}
.button-bar button {
    margin: 0 5px;
    padding: 8px 15px;
    font-size: 14px;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.button-bar button:hover {
    opacity: 0.85;
}

.button-save {
    background: #64B5F6;
}

.button-save:hover {
    background: #42A5F5;
}

.button-pdf {
    background: #81C784;
}

.button-pdf:hover {
    background: #66BB6A;
}

.button-reset {
    background: #E57373;
}

.button-reset:hover {
    background: #EF5350;
}

.reorder-button {
    padding: 2px 6px;
    font-size: 11px;
    margin-left: 5px;
    cursor: pointer;
    background: #FFD54F;
    color: #333;
    border: 1px solid #FFCA28;
    border-radius: 3px;
}

.reorder-button:hover {
    background: #FFCA28;
}

.player-button {
    padding: 2px 5px;
    font-size: 11px;
    min-width: 24px;
    height: 22px;
    vertical-align: middle;
    border-radius: 3px;
    border: none;
    color: white;
    cursor: pointer;
    font-weight: bold;
}

.button-move {
    background: #64B5F6;
}

.button-move:hover {
    background: #42A5F5;
}

.button-move:disabled {
    background: #BBDEFB;
    cursor: not-allowed;
    opacity: 0.5;
}

.button-copy {
    background: #81C784;
}

.button-copy:hover {
    background: #66BB6A;
}

.button-remove {
    background: #E57373;
}

.button-remove:hover {
    background: #EF5350;
}
.reorder-button {
    padding: 2px 6px;
    font-size: 11px;
    margin-left: 5px;
    cursor: pointer;
    background: #4CAF50;
    color: white;
    border: 1px solid #45a049;
    border-radius: 3px;
}
.reorder-button:hover {
    background: #45a049;
}
.sideline-table {
    width: 100%;
    max-width: 600px;
}
.sideline-table td {
    padding: 8px;
}
</style>

<div class="tournament-builder">

<form method="POST" id="team-form">
<input type="hidden" id="action" name="action" value="save_team" />
<input type="hidden" id="inducements-expanded" name="inducements_expanded" value="<?php echo isset($team_data['inducements_expanded']) ? $team_data['inducements_expanded'] : '1'; ?>" />
<input type="hidden" id="roster-expanded" name="roster_expanded" value="<?php echo isset($team_data['roster_expanded']) ? $team_data['roster_expanded'] : '1'; ?>" />
<input type="hidden" id="tournament_settings_expanded" name="tournament_settings_expanded" value="<?php echo isset($team_data['tournament_settings_expanded']) ? $team_data['tournament_settings_expanded'] : 0; ?>" />

<div class="section-box">
    <div class="section-title">Team Information</div>
    <div class='tableResponsive'>
    <table>
        <tr>
            <td><b>Team Name:</b></td>
            <td><input type="text" id="team_name" name="team_name" value="<?php echo $team_name_val; ?>" size="30" /></td>
            <td><b>Coach Name:</b></td>
            <td><input type="text" id="coach_name" name="coach_name" value="<?php echo $coach_name_val; ?>" size="30" /></td>
            <td><b>NAF Number:</b></td>
            <td><input type="text" id="naf_number" name="naf_number" value="<?php echo $naf_number_val; ?>" size="15" /></td>
        </tr>
        <tr>
			<td><b>Race:</b></td>
			<td colspan="5">
				<select id="race_id" name="race_id" onchange="changeRace()">
					<option value="-1">-- Select Race --</option>
		<?php
				foreach ($raceididx as $rid => $rname) {
					$selected = ($rid == $race_id) ? 'selected' : '';
					$race_trans = $lng->getTrn('race/'.strtolower(str_replace(' ','', $rname)));
					echo "<option value='$rid' $selected>$race_trans</option>\n";
				}
		?>
				</select>
				
				<span id="league-dropdown-container" style="display: none; margin-left: 20px;">
					<b>League:</b>
					<select id="selected_league" name="selected_league" onchange="onLeagueChange()">
						<option value="-1">-- Select League --</option>
					</select>
				</span>
				
				<span id="fav-rules-dropdown-container" style="display: none; margin-left: 20px;">
					<b>Favoured of:</b>
					<select id="selected_fav_rule" name="selected_fav_rule" onchange="onFavRuleChange()">
						<option value="-1">-- Select Favoured Rule --</option>
					</select>
				</span>
			</td>
    </table>
    </div>
</div>

<<div class="section-box">
    <div class="section-title">
        Tournament Settings 
        <a href="javascript:void(0)" id="tournament-settings-toggle" onclick="toggleTournamentSettings()" style="font-size: 12px; margin-left: 10px;">[Expand]</a>
    </div>
    <div id="tournament-settings-placeholder" style="display: block;">
        <p style="color: #666;">Select a race to configure tournament settings.</p>
    </div>
    <div id="tournament-settings-container" style="display: none;">
        <div class='tableResponsive'>
        <table style="width: 100%;">
            <tr>
                <td style="width: 28%; vertical-align: top; padding-right: 15px;">
                    <h4 style="margin: 10px 0 5px 0;">Treasury & Budget</h4>
                    <div class='tableResponsive'>
                    <table>
                        <tr>
                            <td><b>Max Treasury Spend:</b></td>
                            <td>
                                <input type="number" id="max_treasury" name="tournament_settings[max_treasury]" value="<?php echo isset($team_data['tournament_settings']['max_treasury']) ? $team_data['tournament_settings']['max_treasury'] : 0; ?>" style="width: 70px;" /> k
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><small>(0 = unlimited)</small></td>
                        </tr>
                        <tr>
							<td colspan="2" style="padding-top: 10px;">
								<input type="checkbox" id="skills_cost_gold" name="tournament_settings[skills_cost_gold]" value="1" <?php echo isset($team_data['tournament_settings']['skills_cost_gold']) && $team_data['tournament_settings']['skills_cost_gold'] ? 'checked' : ''; ?> onchange="toggleSkillsCostGold()" />
								<b>Extra Skills cost Gold</b>
							</td>
						</tr>
						<tr>
							<td colspan="2" style="padding-top: 10px; padding-left: 20px;">
								<input type="checkbox" id="skills_separate_budget" name="tournament_settings[skills_separate_budget]" value="1" <?php echo isset($team_data['tournament_settings']['skills_separate_budget']) && $team_data['tournament_settings']['skills_separate_budget'] ? 'checked' : ''; ?> onchange="toggleSkillsBudget()" />
								<b>Skills & Stats have Separate Budget</b>
							</td>
						</tr>
						<tr>
							<td style="padding-left: 40px;"><b>Skills & Stats Budget:</b></td>
							<td>
								<input type="number" id="skills_budget" name="tournament_settings[skills_budget]" value="<?php echo isset($team_data['tournament_settings']['skills_budget']) ? $team_data['tournament_settings']['skills_budget'] : 0; ?>" style="width: 60px;" /> k
							</td>
						</tr>
						<tr>
							<td colspan="2" style="padding-left: 40px;"><small>(Separate Pool)</small></td>
						</tr>
                        <tr>
                        <tr>
                            <td colspan="2" style="padding-top: 10px;">
                                <input type="checkbox" id="override_sideline_costs" name="tournament_settings[override_sideline_costs]" value="1" <?php echo isset($team_data['tournament_settings']['override_sideline_costs']) && $team_data['tournament_settings']['override_sideline_costs'] ? 'checked' : ''; ?> onchange="toggleSidelineCosts()" />
                                <b>Override Sideline Costs</b>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Team Re-roll Cost Multiplier:</b></td>
                            <td>
                                × <input type="number" id="reroll_cost_multiplier" name="tournament_settings[reroll_cost_multiplier]" value="<?php echo isset($team_data['tournament_settings']['reroll_cost_multiplier']) ? $team_data['tournament_settings']['reroll_cost_multiplier'] : 1; ?>" style="width: 50px;" step="0.1" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Cost of Assistant Coaches:</b></td>
                            <td>
                                <input type="number" id="cost_ass_coaches" name="tournament_settings[cost_ass_coaches]" value="<?php echo isset($team_data['tournament_settings']['cost_ass_coaches']) ? $team_data['tournament_settings']['cost_ass_coaches'] : ($rules['cost_ass_coaches'] / 1000); ?>" style="width: 70px;" /> k
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Cost of Cheerleaders:</b></td>
                            <td>
                                <input type="number" id="cost_cheerleaders" name="tournament_settings[cost_cheerleaders]" value="<?php echo isset($team_data['tournament_settings']['cost_cheerleaders']) ? $team_data['tournament_settings']['cost_cheerleaders'] : ($rules['cost_cheerleaders'] / 1000); ?>" style="width: 70px;" /> k
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Cost of Dedicated Fans:</b></td>
                            <td>
                                <input type="number" id="cost_dedicated_fans" name="tournament_settings[cost_dedicated_fans]" value="<?php echo isset($team_data['tournament_settings']['cost_dedicated_fans']) ? $team_data['tournament_settings']['cost_dedicated_fans'] : ($rules['cost_fan_factor'] / 1000); ?>" style="width: 70px;" /> k
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Cost of Apothecary:</b></td>
                            <td>
                                <input type="number" id="cost_apothecary" name="tournament_settings[cost_apothecary]" value="<?php echo isset($team_data['tournament_settings']['cost_apothecary']) ? $team_data['tournament_settings']['cost_apothecary'] : ($rules['cost_apothecary'] / 1000); ?>" style="width: 70px;" /> k
                            </td>
                        </tr>
                    </table>
                    </div>
                    
                    <h4 style="margin: 15px 0 5px 0;">General Rules</h4>
                    <div class='tableResponsive'>
                    <table>
                        <tr>
							<td colspan="2">
								<input type="checkbox" id="enforce_11_regular_players" name="tournament_settings[enforce_11_regular_players]" value="1" <?php echo isset($team_data['tournament_settings']['enforce_11_regular_players']) && $team_data['tournament_settings']['enforce_11_regular_players'] ? 'checked' : ''; ?> />
								<b>Enforce 11 regular players (excluding stars)</b>
							</td>
						</tr>
                    </table>
                    </div>
                </td>
                
                <td style="width: 27%; vertical-align: top; padding-left: 15px; padding-right: 15px; border-left: 1px solid #ccc;">
                    <h4 style="margin: 10px 0 5px 0;">Skills</h4>
                    <div class='tableResponsive'>
                    <table>
                        <tr>
                            <td colspan="2">
                                <input type="checkbox" id="skills_add_player_value" name="tournament_settings[skills_add_player_value]" value="1" <?php echo isset($team_data['tournament_settings']['skills_add_player_value']) && $team_data['tournament_settings']['skills_add_player_value'] ? 'checked' : ''; ?> onchange="toggleSkillsCost()" />
                                <b>Extra Skills add to Player Value</b>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Primary Skill Cost:</b></td>
                            <td>
                                <input type="number" id="primary_skill_cost" name="tournament_settings[primary_skill_cost]" value="<?php echo isset($team_data['tournament_settings']['primary_skill_cost']) ? $team_data['tournament_settings']['primary_skill_cost'] : 20; ?>" style="width: 60px;" /> k
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Secondary Skill Cost:</b></td>
                            <td>
                                <input type="number" id="secondary_skill_cost" name="tournament_settings[secondary_skill_cost]" value="<?php echo isset($team_data['tournament_settings']['secondary_skill_cost']) ? $team_data['tournament_settings']['secondary_skill_cost'] : 40; ?>" style="width: 60px;" /> k
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Elite Skill Extra Cost:</b></td>
                            <td>
                                <input type="number" id="elite_skill_cost" name="tournament_settings[elite_skill_cost]" value="<?php echo isset($team_data['tournament_settings']['elite_skill_cost']) ? $team_data['tournament_settings']['elite_skill_cost'] : 10; ?>" style="width: 60px;" /> k
                            </td>
                        </tr>
                        
                        <!-- REMOVED: Skills & Stats have Separate Budget (moved to left column) -->
                        
                        <tr>
                            <td colspan="2" style="padding-top: 10px;">
                                <input type="checkbox" id="skills_cost_spp" name="tournament_settings[skills_cost_spp]" value="1" <?php echo isset($team_data['tournament_settings']['skills_cost_spp']) && $team_data['tournament_settings']['skills_cost_spp'] ? 'checked' : ''; ?> onchange="toggleSPPBudget()" />
                                <b>Extra Skills cost SPP</b>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px;"><b>Total SPP Budget:</b></td>
                            <td>
                                <input type="number" id="total_spp" name="tournament_settings[total_spp]" value="<?php echo isset($team_data['tournament_settings']['total_spp']) ? $team_data['tournament_settings']['total_spp'] : 0; ?>" style="width: 60px;" />
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 10px;"><b>Skill Limitations: </b><small>(0 = unlimited)</small></td>
                        </tr>
                        <tr>
                            <td><b>Max Primary Skills (Team):</b></td>
                            <td>
                                <input type="number" id="max_primary_skills" name="tournament_settings[max_primary_skills]" value="<?php echo isset($team_data['tournament_settings']['max_primary_skills']) ? $team_data['tournament_settings']['max_primary_skills'] : 0; ?>" style="width: 60px;" />
                            </td>
                        </tr>
                        <tr>
                            <td><b>Max Secondary Skills (Team):</b></td>
                            <td>
                                <input type="number" id="max_secondary_skills" name="tournament_settings[max_secondary_skills]" value="<?php echo isset($team_data['tournament_settings']['max_secondary_skills']) ? $team_data['tournament_settings']['max_secondary_skills'] : 0; ?>" style="width: 60px;" />
                            </td>
                        </tr>
                        <tr>
                            <td><b>Max Elite Skills (Team):</b></td>
                            <td>
                                <input type="number" id="max_elite_skills" name="tournament_settings[max_elite_skills]" value="<?php echo isset($team_data['tournament_settings']['max_elite_skills']) ? $team_data['tournament_settings']['max_elite_skills'] : 0; ?>" style="width: 60px;" />
                            </td>
                        </tr>
                        <tr>
                            <td><b>Max Players with Stacked Skills:</b></td>
                            <td>
                                <input type="number" id="max_players_multi_skills" name="tournament_settings[max_players_multi_skills]" value="<?php echo isset($team_data['tournament_settings']['max_players_multi_skills']) ? $team_data['tournament_settings']['max_players_multi_skills'] : 0; ?>" style="width: 60px;" />
                            </td>
                        </tr>
                       <tr>
                            <td><b>Max Stacked Skills per Player:</b></td>
                            <td>
                                <input type="number" id="max_stacked_skills_count" name="tournament_settings[max_stacked_skills_count]" value="<?php echo isset($team_data['tournament_settings']['max_stacked_skills_count']) ? $team_data['tournament_settings']['max_stacked_skills_count'] : 0; ?>" style="width: 60px;" />
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><small>(Set to 1 to stop stacking)</small></td>
                        </tr>
						<tr>
                            <td colspan="2" style="padding-top: 10px;">
                                <input type="checkbox" id="allow_secondary_stacking" name="tournament_settings[allow_secondary_stacking]" value="1" <?php echo isset($team_data['tournament_settings']['allow_secondary_stacking']) && $team_data['tournament_settings']['allow_secondary_stacking'] ? 'checked' : ''; ?> />
                                <b>Allow Secondary Skills to be Stacked</b>
                            </td>
                        </tr>
                    </table>
                    </div>
                </td>
                
                <td style="width: 45%; vertical-align: top; padding-left: 15px; border-left: 1px solid #ccc;">
                    <h4 style="margin: 10px 0 5px 0;">Stat Improvements</h4>
                    <div class='tableResponsive'>
                    <table style="width: 100%;">
                        <tr>
                            <td colspan="3">
                                <input type="checkbox" id="allow_stat_increases" name="tournament_settings[allow_stat_increases]" value="1" <?php echo isset($team_data['tournament_settings']['allow_stat_increases']) && $team_data['tournament_settings']['allow_stat_increases'] ? 'checked' : ''; ?> onchange="toggleStatIncreases()" />
                                <b>Allow Stat Improvements </b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <input type="checkbox" id="stat_increases_cost_gold" name="tournament_settings[stat_increases_cost_gold]" value="1" <?php echo isset($team_data['tournament_settings']['stat_increases_cost_gold']) && $team_data['tournament_settings']['stat_increases_cost_gold'] ? 'checked' : ''; ?> onchange="toggleStatCosts()" />
                                <b>Stat Improvements cost Gold</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <input type="checkbox" id="stat_increases_cost_spp" name="tournament_settings[stat_increases_cost_spp]" value="1" <?php echo isset($team_data['tournament_settings']['stat_increases_cost_spp']) && $team_data['tournament_settings']['stat_increases_cost_spp'] ? 'checked' : ''; ?> onchange="toggleStatCosts()" />
                                <b>Stat Improvements cost SPP</b>
                            </td>
                        </tr>
                        
                        <!-- MA Row -->
                        <tr>
                            <td style="width: 40%;"><b>MA Improvements (per team):</b></td>
                            <td style="width: 20%;">
                                Max: <input type="number" id="max_ma_increases" name="tournament_settings[max_ma_increases]" value="<?php echo isset($team_data['tournament_settings']['max_ma_increases']) ? $team_data['tournament_settings']['max_ma_increases'] : 0; ?>" style="width: 50px;" />
                            </td>
                            <td style="width: 20%;">
                                Cost: <input type="number" id="ma_increase_cost" name="tournament_settings[ma_increase_cost]" value="<?php echo isset($team_data['tournament_settings']['ma_increase_cost']) ? $team_data['tournament_settings']['ma_increase_cost'] : 20; ?>" style="width: 40px;" />k
                            </td>
                            <td style="width: 20%;">
                                SPP: <input type="number" id="ma_increase_spp" name="tournament_settings[ma_increase_spp]" value="<?php echo isset($team_data['tournament_settings']['ma_increase_spp']) ? $team_data['tournament_settings']['ma_increase_spp'] : 14; ?>" style="width: 40px;" />
                            </td>
                        </tr>
                        
                        <!-- ST Row -->
                        <tr>
                            <td><b>ST Improvements (per team):</b></td>
                            <td>
                                Max: <input type="number" id="max_st_increases" name="tournament_settings[max_st_increases]" value="<?php echo isset($team_data['tournament_settings']['max_st_increases']) ? $team_data['tournament_settings']['max_st_increases'] : 0; ?>" style="width: 50px;" />
                            </td>
                            <td>
                                Cost: <input type="number" id="st_increase_cost" name="tournament_settings[st_increase_cost]" value="<?php echo isset($team_data['tournament_settings']['st_increase_cost']) ? $team_data['tournament_settings']['st_increase_cost'] : 60; ?>" style="width: 40px;" />k
                            </td>
                            <td>
                                SPP: <input type="number" id="st_increase_spp" name="tournament_settings[st_increase_spp]" value="<?php echo isset($team_data['tournament_settings']['st_increase_spp']) ? $team_data['tournament_settings']['st_increase_spp'] : 14; ?>" style="width: 40px;" />
                            </td>
                        </tr>
                        
                        <!-- AG Row -->
                        <tr>
                            <td><b>AG Improvements (per team):</b></td>
                            <td>
                                Max: <input type="number" id="max_ag_increases" name="tournament_settings[max_ag_increases]" value="<?php echo isset($team_data['tournament_settings']['max_ag_increases']) ? $team_data['tournament_settings']['max_ag_increases'] : 0; ?>" style="width: 50px;" />
                            </td>
                            <td>
                                Cost: <input type="number" id="ag_increase_cost" name="tournament_settings[ag_increase_cost]" value="<?php echo isset($team_data['tournament_settings']['ag_increase_cost']) ? $team_data['tournament_settings']['ag_increase_cost'] : 30; ?>" style="width: 40px;" />k
                            </td>
                            <td>
                                SPP: <input type="number" id="ag_increase_spp" name="tournament_settings[ag_increase_spp]" value="<?php echo isset($team_data['tournament_settings']['ag_increase_spp']) ? $team_data['tournament_settings']['ag_increase_spp'] : 14; ?>" style="width: 40px;" />
                            </td>
                        </tr>
                        
                        <!-- PA Row -->
                        <tr>
                            <td><b>PA Improvements (per team):</b></td>
                            <td>
                                Max: <input type="number" id="max_pa_increases" name="tournament_settings[max_pa_increases]" value="<?php echo isset($team_data['tournament_settings']['max_pa_increases']) ? $team_data['tournament_settings']['max_pa_increases'] : 0; ?>" style="width: 50px;" />
                            </td>
                            <td>
                                Cost: <input type="number" id="pa_increase_cost" name="tournament_settings[pa_increase_cost]" value="<?php echo isset($team_data['tournament_settings']['pa_increase_cost']) ? $team_data['tournament_settings']['pa_increase_cost'] : 20; ?>" style="width: 40px;" />k
                            </td>
                            <td>
                                SPP: <input type="number" id="pa_increase_spp" name="tournament_settings[pa_increase_spp]" value="<?php echo isset($team_data['tournament_settings']['pa_increase_spp']) ? $team_data['tournament_settings']['pa_increase_spp'] : 14; ?>" style="width: 40px;" />
                            </td>
                        </tr>
                        
                        <!-- AV Row -->
                        <tr>
                            <td><b>AV Improvements (per team):</b></td>
                            <td>
                                Max: <input type="number" id="max_av_increases" name="tournament_settings[max_av_increases]" value="<?php echo isset($team_data['tournament_settings']['max_av_increases']) ? $team_data['tournament_settings']['max_av_increases'] : 0; ?>" style="width: 50px;" />
                            </td>
                            <td>
                                Cost: <input type="number" id="av_increase_cost" name="tournament_settings[av_increase_cost]" value="<?php echo isset($team_data['tournament_settings']['av_increase_cost']) ? $team_data['tournament_settings']['av_increase_cost'] : 10; ?>" style="width: 40px;" />k
                            </td>
                            <td>
                                SPP: <input type="number" id="av_increase_spp" name="tournament_settings[av_increase_spp]" value="<?php echo isset($team_data['tournament_settings']['av_increase_spp']) ? $team_data['tournament_settings']['av_increase_spp'] : 14; ?>" style="width: 40px;" />
                            </td>
                        </tr>
                        
                        <tr>
                            <td colspan="4"><small>(0 = unlimited)</small></td>
                        </tr>
                        <tr>
                            <td><b>Max Total Stat Improvements:</b></td>
                            <td colspan="3">
                                <input type="number" id="max_total_stat_increases" name="tournament_settings[max_total_stat_increases]" value="<?php echo isset($team_data['tournament_settings']['max_total_stat_increases']) ? $team_data['tournament_settings']['max_total_stat_increases'] : 0; ?>" style="width: 50px;" />
                            </td>
                        </tr>
						<tr>
                            <td colspan="3">
                                <input type="checkbox" id="allow_multiple_same_stat" 
                                       name="tournament_settings[allow_multiple_same_stat]" value="1" 
                                       <?php echo !empty($team_data['tournament_settings']['allow_multiple_same_stat']) ? 'checked' : ''; ?> />
                                <label for="allow_multiple_same_stat">
                                    Allow multiples of same Stat on a player
                                </label>
                            </td>
                        </tr>
						<tr>
                            <td colspan="3">
                                <input type="checkbox" id="allow_stat_skill_stacking" 
                                       name="tournament_settings[allow_stat_skill_stacking]" value="1" 
                                       <?php echo !empty($team_data['tournament_settings']['allow_stat_skill_stacking']) ? 'checked' : ''; ?> />
                                <label for="allow_stat_skill_stacking">
                                    Allow Stat Improvements stacked with skills
                                </label>
                            </td>
                        </tr>
                    </table>
                    </div>
					<div style="margin-top: 20px; padding: 12px; background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 5px; font-size: 11px;">
						<b style="color: #856404;">⚠ Important:</b> 
						<ul style="margin: 5px 0; padding-left: 20px;">
							<li>Tournament settings must be <b>saved</b> before they take effect</li>
							<li>Click <b>"Save Team"</b> at the bottom after making changes</li>
						</ul>
					</div>
                </td>
            </tr>
        </table>
        </div>
    </div>
</div>

<script type="text/javascript">
function toggleSidelineCosts() {
    var overrideSidelineChecked = document.getElementById('override_sideline_costs').checked;
    
    document.getElementById('reroll_cost_multiplier').disabled = !overrideSidelineChecked;
    document.getElementById('cost_ass_coaches').disabled = !overrideSidelineChecked;
    document.getElementById('cost_cheerleaders').disabled = !overrideSidelineChecked;
    document.getElementById('cost_dedicated_fans').disabled = !overrideSidelineChecked;
    document.getElementById('cost_apothecary').disabled = !overrideSidelineChecked;
    
    if (!overrideSidelineChecked) {
        document.getElementById('reroll_cost_multiplier').style.backgroundColor = '#e0e0e0';
        document.getElementById('cost_ass_coaches').style.backgroundColor = '#e0e0e0';
        document.getElementById('cost_cheerleaders').style.backgroundColor = '#e0e0e0';
        document.getElementById('cost_dedicated_fans').style.backgroundColor = '#e0e0e0';
        document.getElementById('cost_apothecary').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('reroll_cost_multiplier').style.backgroundColor = '';
        document.getElementById('cost_ass_coaches').style.backgroundColor = '';
        document.getElementById('cost_cheerleaders').style.backgroundColor = '';
        document.getElementById('cost_dedicated_fans').style.backgroundColor = '';
        document.getElementById('cost_apothecary').style.backgroundColor = '';
    }
}

function toggleSkillsCost() {
    var skillsCostGoldChecked = document.getElementById('skills_cost_gold').checked;
    var skillsAddValueChecked = document.getElementById('skills_add_player_value').checked;
    
    // Active if EITHER checkbox is ticked
    var shouldEnable = skillsCostGoldChecked || skillsAddValueChecked;
    
    document.getElementById('primary_skill_cost').disabled = !shouldEnable;
    document.getElementById('secondary_skill_cost').disabled = !shouldEnable;
    document.getElementById('elite_skill_cost').disabled = !shouldEnable;
    
    if (!shouldEnable) {
        document.getElementById('primary_skill_cost').style.backgroundColor = '#e0e0e0';
        document.getElementById('secondary_skill_cost').style.backgroundColor = '#e0e0e0';
        document.getElementById('elite_skill_cost').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('primary_skill_cost').style.backgroundColor = '';
        document.getElementById('secondary_skill_cost').style.backgroundColor = '';
        document.getElementById('elite_skill_cost').style.backgroundColor = '';
    }
}

function toggleSkillsCostGold() {
    var skillsCostGoldChecked = document.getElementById('skills_cost_gold').checked;
    
    // Enable/disable the separate budget checkbox based on cost gold
    document.getElementById('skills_separate_budget').disabled = !skillsCostGoldChecked;
    
    // If cost gold is unchecked, also uncheck and disable separate budget
    if (!skillsCostGoldChecked) {
        document.getElementById('skills_separate_budget').checked = false;
        document.getElementById('skills_separate_budget').style.opacity = '0.5';
        document.getElementById('skills_separate_budget').style.cursor = 'not-allowed';
    } else {
        document.getElementById('skills_separate_budget').style.opacity = '1';
        document.getElementById('skills_separate_budget').style.cursor = 'pointer';
    }
    
    // Also call the budget toggle to update the value field
    toggleSkillsBudget();
    
    // Call the original function to update cost fields
    toggleSkillsCost();
}

function toggleSkillsBudget() {
    var separateBudgetChecked = document.getElementById('skills_separate_budget').checked;
    
    document.getElementById('skills_budget').disabled = !separateBudgetChecked;
    
    if (!separateBudgetChecked) {
        document.getElementById('skills_budget').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('skills_budget').style.backgroundColor = '';
    }
}

function toggleSPPBudget() {
    var sppChecked = document.getElementById('skills_cost_spp').checked;
    
    document.getElementById('total_spp').disabled = !sppChecked;
    
    if (!sppChecked) {
        document.getElementById('total_spp').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('total_spp').style.backgroundColor = '';
    }
}

function toggleStatIncreases() {
    var allowStatIncreases = document.getElementById('allow_stat_increases').checked;
    
    // Disable/enable the checkboxes
    document.getElementById('stat_increases_cost_gold').disabled = !allowStatIncreases;
    document.getElementById('stat_increases_cost_spp').disabled = !allowStatIncreases;
    document.getElementById('allow_multiple_same_stat').disabled = !allowStatIncreases;
    document.getElementById('allow_stat_skill_stacking').disabled = !allowStatIncreases;
    
    // Disable/enable all stat increase max fields
    document.getElementById('max_ma_increases').disabled = !allowStatIncreases;
    document.getElementById('max_st_increases').disabled = !allowStatIncreases;
    document.getElementById('max_ag_increases').disabled = !allowStatIncreases;
    document.getElementById('max_pa_increases').disabled = !allowStatIncreases;
    document.getElementById('max_av_increases').disabled = !allowStatIncreases;
    document.getElementById('max_total_stat_increases').disabled = !allowStatIncreases;
    
    if (!allowStatIncreases) {
		document.getElementById('max_ma_increases').style.backgroundColor = '#e0e0e0';
        document.getElementById('max_st_increases').style.backgroundColor = '#e0e0e0';
        document.getElementById('max_ag_increases').style.backgroundColor = '#e0e0e0';
        document.getElementById('max_pa_increases').style.backgroundColor = '#e0e0e0';
        document.getElementById('max_av_increases').style.backgroundColor = '#e0e0e0';
        document.getElementById('max_total_stat_increases').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('max_ma_increases').style.backgroundColor = '';
        document.getElementById('max_st_increases').style.backgroundColor = '';
        document.getElementById('max_ag_increases').style.backgroundColor = '';
        document.getElementById('max_pa_increases').style.backgroundColor = '';
        document.getElementById('max_av_increases').style.backgroundColor = '';
        document.getElementById('max_total_stat_increases').style.backgroundColor = '';
    }
    
    // Also toggle stat costs (which will handle Cost and SPP fields)
    toggleStatCosts();
}

function toggleStatCosts() {
    var allowStatIncreases = document.getElementById('allow_stat_increases').checked;
    var statCostsGoldChecked = document.getElementById('stat_increases_cost_gold').checked;
    var statCostsSPPChecked = document.getElementById('stat_increases_cost_spp').checked;
    
    // Cost fields only enabled if BOTH allow stat increases AND cost gold are checked
    var shouldEnableCost = allowStatIncreases && statCostsGoldChecked;
    
    document.getElementById('ma_increase_cost').disabled = !shouldEnableCost;
    document.getElementById('st_increase_cost').disabled = !shouldEnableCost;
    document.getElementById('ag_increase_cost').disabled = !shouldEnableCost;
    document.getElementById('pa_increase_cost').disabled = !shouldEnableCost;
    document.getElementById('av_increase_cost').disabled = !shouldEnableCost;
    
    if (!shouldEnableCost) {
        document.getElementById('ma_increase_cost').style.backgroundColor = '#e0e0e0';
        document.getElementById('st_increase_cost').style.backgroundColor = '#e0e0e0';
        document.getElementById('ag_increase_cost').style.backgroundColor = '#e0e0e0';
        document.getElementById('pa_increase_cost').style.backgroundColor = '#e0e0e0';
        document.getElementById('av_increase_cost').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('ma_increase_cost').style.backgroundColor = '';
        document.getElementById('st_increase_cost').style.backgroundColor = '';
        document.getElementById('ag_increase_cost').style.backgroundColor = '';
        document.getElementById('pa_increase_cost').style.backgroundColor = '';
        document.getElementById('av_increase_cost').style.backgroundColor = '';
    }
    
    // SPP fields only enabled if BOTH allow stat increases AND cost SPP are checked
    var shouldEnableSPP = allowStatIncreases && statCostsSPPChecked;
    
    document.getElementById('ma_increase_spp').disabled = !shouldEnableSPP;
    document.getElementById('st_increase_spp').disabled = !shouldEnableSPP;
    document.getElementById('ag_increase_spp').disabled = !shouldEnableSPP;
    document.getElementById('pa_increase_spp').disabled = !shouldEnableSPP;
    document.getElementById('av_increase_spp').disabled = !shouldEnableSPP;
    
    if (!shouldEnableSPP) {
        document.getElementById('ma_increase_spp').style.backgroundColor = '#e0e0e0';
        document.getElementById('st_increase_spp').style.backgroundColor = '#e0e0e0';
        document.getElementById('ag_increase_spp').style.backgroundColor = '#e0e0e0';
        document.getElementById('pa_increase_spp').style.backgroundColor = '#e0e0e0';
        document.getElementById('av_increase_spp').style.backgroundColor = '#e0e0e0';
    } else {
        document.getElementById('ma_increase_spp').style.backgroundColor = '';
        document.getElementById('st_increase_spp').style.backgroundColor = '';
        document.getElementById('ag_increase_spp').style.backgroundColor = '';
        document.getElementById('pa_increase_spp').style.backgroundColor = '';
        document.getElementById('av_increase_spp').style.backgroundColor = '';
    }
}

function toggleTournamentSettings() {
    var content = document.getElementById('tournament-settings-container');
    var link = document.getElementById('tournament-settings-toggle');
    var expandedInput = document.getElementById('tournament_settings_expanded');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        link.textContent = '[Collapse]';
        expandedInput.value = '1';
    } else {
        content.style.display = 'none';
        link.textContent = '[Expand]';
        expandedInput.value = '0';
    }
}

function validateTournamentSettings() {
    var errors = [];
    
    // Get max treasury
    var maxTreasury = parseInt(document.getElementById('max_treasury').value) || 0;
    
    // Skills budget validation
    var skillsSeparateBudget = document.getElementById('skills_separate_budget');
    if (skillsSeparateBudget && skillsSeparateBudget.checked) {
        var skillsBudget = parseInt(document.getElementById('skills_budget').value) || 0;
        if (maxTreasury > 0 && skillsBudget > maxTreasury) {
            errors.push('Skills Budget cannot exceed Max Treasury Spend');
        }
    }
    
    // Max stacked skills validation
    var maxPlayersStacked = parseInt(document.getElementById('max_players_multi_skills').value) || 0;
    var maxStackedSkills = parseInt(document.getElementById('max_stacked_skills_count').value) || 0;
    
    //if (maxPlayersStacked > 0 && maxStackedSkills === 0) {
    //    errors.push('If Max Players with Stacked Skills is set, Max Stacked Skills Allowed must be greater than 0');
    //}
    
    //if (maxStackedSkills > 0 && maxPlayersStacked === 0) {
    //    errors.push('If Max Stacked Skills Allowed is set, Max Players with Stacked Skills must be greater than 0');
    //}
    
    // Stat increases validation
    var allowStatIncreases = document.getElementById('allow_stat_increases');
    if (allowStatIncreases && allowStatIncreases.checked) {
        var maxTotal = parseInt(document.getElementById('max_total_stat_increases').value) || 0;
        var maxPerPlayer = parseInt(document.getElementById('max_stat_increases_per_player').value) || 0;
        
        if (maxTotal > 0 && maxPerPlayer > maxTotal) {
            errors.push('Max Stat Increases per Player cannot exceed Max Total Stat Increases');
        }
        
        // Check if any individual stat max exceeds per-player max
        var statMaxes = ['max_ma_increases', 'max_st_increases', 'max_ag_increases',
                         'max_pa_increases', 'max_av_increases'];
        for (var i = 0; i < statMaxes.length; i++) {
            var val = parseInt(document.getElementById(statMaxes[i]).value) || 0;
            if (maxPerPlayer > 0 && val > maxPerPlayer) {
                errors.push('Individual stat max cannot exceed Max Stat Increases per Player');
                break;
            }
        }
    }
    
    // Display errors if any
    if (errors.length > 0) {
        alert('Tournament Settings Validation Errors:\n\n' + errors.join('\n'));
        return false;
    }
    
    return true;
}

// Run on page load to set initial state
window.addEventListener('load', function() {
    toggleSidelineCosts();
	toggleSkillsCostGold();
    toggleSkillsCost();
    toggleSkillsBudget();
    toggleSPPBudget();
    toggleStatIncreases();
    toggleStatCosts();
    
    // Restore tournament settings collapse state
    var expandedState = document.getElementById('tournament_settings_expanded').value;
    if (expandedState === '0') {
        document.getElementById('tournament-settings-container').style.display = 'none';
        document.getElementById('tournament-settings-toggle').textContent = '[Expand]';
    } else {
        document.getElementById('tournament-settings-container').style.display = 'block';
        document.getElementById('tournament-settings-toggle').textContent = '[Collapse]';
    }
});
</script>

<div class="summary-box">
    <h3>Team Summary</h3>
    <div class="summary-item">Total Value: <span style="color: #cc0000;" id="counter-value"><?php echo number_format($team_value / 1000); ?>k</span></div>
    <div class="summary-item">Players: <span style="color: <?php echo $player_count > 16 || $regular_player_count < 11 ? '#cc0000' : '#000'; ?>" id="counter-players"><?php echo $player_count; ?> / 16</span> (<span id="counter-regular"><?php echo $regular_player_count; ?></span> regular, <span id="counter-stars"><?php echo $star_count; ?> / 2</span> stars)</div>
    <div class="summary-item">Primary Skills: <span style="color: #0000cc;" id="counter-primary"><?php echo $primary_skills_count; ?></span></div>
    <div class="summary-item">Secondary Skills: <span style="color: #ff0000;" id="counter-secondary"><?php echo $secondary_skills_count; ?></span></div>
    <div class="summary-item">Elite Skills: <span style="color: #ff6600;" id="counter-elite"><?php echo $elite_skills_count; ?></span></div>
    
    <?php
    // Tournament validation indicators (only show if tournament settings are active)
	$ts = isset($team_data['tournament_settings']) ? $team_data['tournament_settings'] : array();
	if (!empty($ts) && array_filter($ts)):
	?>
	<hr style="margin: 10px 0; border-color: #999;">
	<div style="font-size: 11px; color: #666; margin-bottom: 5px;">Tournament Compliance:</div>

	<!-- Overall Team Valid/Invalid -->
	<div class="summary-item">
		<span id="team-valid-icon" style="font-weight: bold;">✓</span>
		<span id="team-valid-status" style="font-weight: bold; color: #00cc00;">Team Valid</span>
	</div>

	<!-- Max Treasury validation -->
	<?php if (!empty($ts['max_treasury'])): ?>
	<div class="summary-item" id="tournament-treasury-status">
		<span id="treasury-icon">✓</span> Treasury Limit
	</div>
	<?php endif; ?>

	<!-- Skills budget validation -->
	<?php if (!empty($ts['skills_separate_budget']) && !empty($ts['skills_budget'])): ?>
	<div class="summary-item" id="tournament-skills-budget-status">
		<span id="skills-budget-icon">✓</span> Skills Budget
	</div>
	<?php endif; ?>

	<!-- Max primary skills validation -->
	<?php if (!empty($ts['max_primary_skills'])): ?>
	<div class="summary-item" id="tournament-max-primary-status">
		<span id="max-primary-icon">✓</span> Max Primary Skills
	</div>
	<?php endif; ?>

	<!-- Max secondary skills validation -->
	<?php if (!empty($ts['max_secondary_skills'])): ?>
	<div class="summary-item" id="tournament-max-secondary-status">
		<span id="max-secondary-icon">✓</span> Max Secondary Skills
	</div>
	<?php endif; ?>

	<!-- Max elite skills validation -->
	<?php if (!empty($ts['max_elite_skills'])): ?>
	<div class="summary-item" id="tournament-max-elite-status">
		<span id="max-elite-icon">✓</span> Max Elite Skills
	</div>
	<?php endif; ?>

	<!-- Max players with stacked skills validation -->
	<?php if (!empty($ts['max_players_multi_skills'])): ?>
	<div class="summary-item" id="tournament-max-stacked-players-status">
		<span id="max-stacked-players-icon">✓</span> Max Stacked Players
	</div>
	<?php endif; ?>

	<!-- Max stacked per player validation -->
	<?php if (!empty($ts['max_stacked_skills_count'])): ?>
	<div class="summary-item" id="tournament-max-stacked-count-status">
		<span id="max-stacked-count-icon">✓</span> Max Stacked Per Player
	</div>
	<?php endif; ?>

	<!-- Secondary stacking rule validation -->
	<?php if (isset($ts['allow_secondary_stacking']) && empty($ts['allow_secondary_stacking'])): ?>
	<div class="summary-item" id="tournament-secondary-stack-status">
		<span id="secondary-stack-icon">✓</span> Secondary Stacking
	</div>
	<?php endif; ?>

	<!-- Stat+Skill stacking rule validation -->
	<?php if (!empty($ts['allow_stat_increases']) && isset($ts['allow_stat_skill_stacking']) && empty($ts['allow_stat_skill_stacking'])): ?>
	<div class="summary-item" id="tournament-stat-skill-stack-status">
		<span id="stat-skill-stack-icon">✓</span> Stat+Skill Stacking
	</div>
	<?php endif; ?>
	
	<!-- Enforce 11 regular players validation -->
	<?php if (!empty($ts['enforce_11_regular_players'])): ?>
	<div class="summary-item" id="tournament-enforce-11-regular-status">
		<span id="enforce-11-regular-icon">✓</span> 11 Regular Players
	</div>
	<?php endif; ?>

	<!-- Stat increases validation -->
	<?php if (!empty($ts['allow_stat_increases'])): ?>
		<!-- Individual stat limits -->
		<?php if (!empty($ts['max_ma_increases'])): ?>
		<div class="summary-item" id="tournament-stat-ma-status">
			<span id="stat-ma-icon">✓</span> Max MA
		</div>
		<?php endif; ?>
		
		<?php if (!empty($ts['max_st_increases'])): ?>
		<div class="summary-item" id="tournament-stat-st-status">
			<span id="stat-st-icon">✓</span> Max ST
		</div>
		<?php endif; ?>
		
		<?php if (!empty($ts['max_ag_increases'])): ?>
		<div class="summary-item" id="tournament-stat-ag-status">
			<span id="stat-ag-icon">✓</span> Max AG
		</div>
		<?php endif; ?>
		
		<?php if (!empty($ts['max_pa_increases'])): ?>
		<div class="summary-item" id="tournament-stat-pa-status">
			<span id="stat-pa-icon">✓</span> Max PA
		</div>
		<?php endif; ?>
		
		<?php if (!empty($ts['max_av_increases'])): ?>
		<div class="summary-item" id="tournament-stat-av-status">
			<span id="stat-av-icon">✓</span> Max AV
		</div>
		<?php endif; ?>
		
		<!-- Total stat improvements -->
		<?php if (!empty($ts['max_total_stat_increases'])): ?>
		<div class="summary-item" id="tournament-total-stat-status">
			<span id="total-stat-icon">✓</span> Total Stats
		</div>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>
</div>
<?php
// Tournament Settings Summary
$ts = isset($team_data['tournament_settings']) ? $team_data['tournament_settings'] : array();
if (!empty($ts) && array_filter($ts)): // Only show if there are non-zero settings
?>
<div class="summary-box" style="background: #ffe6cc; border-color: #ff9900;">
    <h3>Tournament Settings Active</h3>
    <?php
    $hasSettings = false;
    
    // Max Treasury
    if (!empty($ts['max_treasury'])) {
        echo '<div class="summary-item">Max Treasury: ' . $ts['max_treasury'] . 'k</div>';
        $hasSettings = true;
    }
    
    // Skills cost settings
    if (!empty($ts['skills_cost_gold']) || !empty($ts['skills_add_player_value'])) {
        $modes = array();
        if (!empty($ts['skills_cost_gold'])) $modes[] = 'Cost Gold';
        if (!empty($ts['skills_add_player_value'])) $modes[] = 'Add to Value';
        echo '<div class="summary-item">Extra Skills: ' . implode(', ', $modes);
        if (!empty($ts['primary_skill_cost']) || !empty($ts['secondary_skill_cost'])) {
            echo ' (P:' . (isset($ts['primary_skill_cost']) ? $ts['primary_skill_cost'] : 20) . 'k';
            echo ' S:' . (isset($ts['secondary_skill_cost']) ? $ts['secondary_skill_cost'] : 40) . 'k)';
        }
        echo '</div>';
        $hasSettings = true;
    }
    
    // SPP Budget
    if (!empty($ts['skills_cost_spp']) && !empty($ts['total_spp'])) {
        echo '<div class="summary-item">SPP Budget: ' . $ts['total_spp'] . '</div>';
        $hasSettings = true;
    }
    
    // Separate Skills Budget
    if (!empty($ts['skills_separate_budget']) && !empty($ts['skills_budget'])) {
        echo '<div class="summary-item">Skills Budget: ' . $ts['skills_budget'] . 'k</div>';
        $hasSettings = true;
    }
    
    // Skill limitations
    $limits = array();
    if (!empty($ts['max_primary_skills'])) $limits[] = 'Primary: ' . $ts['max_primary_skills'];
    if (!empty($ts['max_secondary_skills'])) $limits[] = 'Secondary: ' . $ts['max_secondary_skills'];
    if (!empty($ts['max_elite_skills'])) $limits[] = 'Elite: ' . $ts['max_elite_skills'];
    if (!empty($limits)) {
        echo '<div class="summary-item">Skill Limits (Team): ' . implode(', ', $limits) . '</div>';
        $hasSettings = true;
    }
    
    // Stacking limitations
    $stacking = array();
    if (!empty($ts['max_players_multi_skills'])) $stacking[] = 'Max Players: ' . $ts['max_players_multi_skills'];
    if (!empty($ts['max_stacked_skills_count'])) $stacking[] = 'Max Per Player: ' . $ts['max_stacked_skills_count'];
    if (!empty($stacking)) {
        echo '<div class="summary-item">Stacking Limits: ' . implode(', ', $stacking) . '</div>';
        $hasSettings = true;
    }
    
    // Secondary stacking rule
    if (isset($ts['allow_secondary_stacking']) && empty($ts['allow_secondary_stacking'])) {
        echo '<div class="summary-item">Secondary Stacking: Disabled</div>';
        $hasSettings = true;
    }
    
    // Stat skill stacking rule
    if (!empty($ts['allow_stat_increases']) && isset($ts['allow_stat_skill_stacking']) && empty($ts['allow_stat_skill_stacking'])) {
        echo '<div class="summary-item">Stat+Skill Stacking: Disabled</div>';
        $hasSettings = true;
    }
    
    // Stat increases
    if (!empty($ts['allow_stat_increases'])) {
        echo '<div class="summary-item">Stat Improvements: Allowed';
        if (!empty($ts['max_total_stat_increases'])) {
            echo ' (Max Total: ' . $ts['max_total_stat_increases'] . ')';
        }
        echo '</div>';
        $hasSettings = true;
    }
    
    // Individual stat limits (only show if stat increases are allowed)
    if (!empty($ts['allow_stat_increases'])) {
        $stat_limits = array();
        if (!empty($ts['max_ma_increases'])) $stat_limits[] = 'MA: ' . $ts['max_ma_increases'];
        if (!empty($ts['max_st_increases'])) $stat_limits[] = 'ST: ' . $ts['max_st_increases'];
        if (!empty($ts['max_ag_increases'])) $stat_limits[] = 'AG: ' . $ts['max_ag_increases'];
        if (!empty($ts['max_pa_increases'])) $stat_limits[] = 'PA: ' . $ts['max_pa_increases'];
        if (!empty($ts['max_av_increases'])) $stat_limits[] = 'AV: ' . $ts['max_av_increases'];
        if (!empty($stat_limits)) {
            echo '<div class="summary-item">Stat Limits: ' . implode(', ', $stat_limits) . '</div>';
            $hasSettings = true;
        }
    }
    
    // Sideline cost overrides
    if (!empty($ts['override_sideline_costs'])) {
        echo '<div class="summary-item">Sideline Costs: Overridden</div>';
        $hasSettings = true;
    }
    
    // General rules
	if (!empty($ts['enforce_11_regular_players'])) {
		echo '<div class="summary-item">11 regular players required</div>';
		$hasSettings = true;
	}
    
    if (!$hasSettings) {
        echo '<div class="summary-item"><i>No restrictions set</i></div>';
    }
    ?>
</div>
<?php endif; ?>

<div class="section-box" id="roster-box" style="display: none;">
    <div class="section-title">
        Roster 
        <a href="javascript:void(0)" id="roster-toggle" onclick="toggleRoster()" style="font-size: 12px; margin-left: 10px;">[Collapse]</a>
    </div>
    <div id="roster-container">
        <div id="roster-content"></div>
        <br><div id="team-details-row">
            <span id="team-tier-display" style="margin-right: 20px;"></span>
            <span id="team-league-display" style="margin-right: 20px;"></span>
            <span id="team-special-rules-display"></span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Players (Maximum 16 including stars)</div>
    <div class='tableResponsive'>
    <table class="builder-table">
        <thead>
            <tr>
                <th style="width: 30px;">Nr <button type="button" onclick="reorderRowsByNumber()" class="reorder-button" title="Reorder rows by number">↕</button></th>
				<th style="width: 180px;">Position</th>
				<th style="width: 20px;">MA</th>
				<th style="width: 20px;">ST</th>
				<th style="width: 20px;">AG</th>
				<th style="width: 20px;">PA</th>
				<th style="width: 20px;">AV</th>
				<th>Skills & Traits</th>
				<th style="width: 35px;">Value</th>
				<th style="width: 105px;">Actions</th>
            </tr>
        </thead>
        <tbody id="players-tbody">
<?php
        if (isset($team_data['players']) && is_array($team_data['players'])) {
            foreach ($team_data['players'] as $idx => $player) {
                $nr = isset($player['nr']) ? $player['nr'] : ($idx + 1);
                $position = isset($player['position']) ? htmlspecialchars($player['position']) : '';
                
                $value = '-';
                $base_skills = '-';
                if ($position) {
                    if (strpos($position, 'STAR:') === 0) {
                        $star_name = substr($position, 5);
                        if (isset($stars[$star_name])) {
                            $value = ($stars[$star_name]['cost'] / 1000) . 'k';
                            $bs = skillsTrans($stars[$star_name]['def']);
                            $base_skills = is_array($bs) ? implode(', ', $bs) : $bs;
                        }
                    } else {
                        if (isset($DEA[$race_name]['players'][$position])) {
                            $value = ($DEA[$race_name]['players'][$position]['cost'] / 1000) . 'k';
                            $bs = skillsTrans($DEA[$race_name]['players'][$position]['def']);
                            $base_skills = is_array($bs) ? implode(', ', $bs) : $bs;
                        }
                    }
                }
                
                // Build extra skills array
                $extra_skills_json = isset($player['extra_skills']) ? json_encode($player['extra_skills']) : '[]';
				
				// Build stat increases array
                $stat_increases = isset($player['stat_increases']) ? $player['stat_increases'] : array('ma'=>0,'st'=>0,'ag'=>0,'pa'=>0,'av'=>0);
                $stat_increases_json = json_encode($stat_increases);
                
                // Get stats for this position
				$ma_val = '-';
				$st_val = '-';
				$ag_val = '-';
				$pa_val = '-';
				$av_val = '-';

				if ($position) {
					if (strpos($position, 'STAR:') === 0) {
						$star_name = substr($position, 5);
						if (isset($stars[$star_name])) {
							$ma_val = $stars[$star_name]['ma'];
							$st_val = $stars[$star_name]['st'];
							$ag_val = ($stars[$star_name]['ag'] > 0 && $stars[$star_name]['ag'] < 7) ? $stars[$star_name]['ag'] . '+' : $stars[$star_name]['ag'];
							$pa_val = (isset($stars[$star_name]['pa']) && $stars[$star_name]['pa'] > 0 && $stars[$star_name]['pa'] < 7) ? $stars[$star_name]['pa'] . '+' : (isset($stars[$star_name]['pa']) ? $stars[$star_name]['pa'] : '-');
							$av_val = ($stars[$star_name]['av'] > 0) ? $stars[$star_name]['av'] . '+' : $stars[$star_name]['av'];
						}
					} else {
						if (isset($DEA[$race_name]['players'][$position])) {
							$pdata = $DEA[$race_name]['players'][$position];
							$ma_val = $pdata['ma'];
							$st_val = $pdata['st'];
							$ag_val = ($pdata['ag'] > 0 && $pdata['ag'] < 7) ? $pdata['ag'] . '+' : $pdata['ag'];
							$pa_val = (isset($pdata['pa']) && $pdata['pa'] > 0 && $pdata['pa'] < 7) ? $pdata['pa'] . '+' : (isset($pdata['pa']) ? $pdata['pa'] : '-');
							$av_val = ($pdata['av'] > 0) ? $pdata['av'] . '+' : $pdata['av'];
						}
					}
				}

				echo "<tr class='player-row'>\n";
				echo "<td><input type='number' name='players[$idx][nr]' value='$nr' size='2' style='width: 40px;' /></td>\n";
				echo "<td><select name='players[$idx][position]' class='position-select' onchange='onPositionChange($idx)'>";
                echo "<option value=''>-- Select Position --</option>";
                // Display text should not include STAR: prefix
				$position_display = $position;
				if (strpos($position, 'STAR:') === 0) {
					$position_display = substr($position, 5) . ' (Star)';
				}
				echo "<option value='$position' selected>$position_display</option>";
                echo "</select></td>\n";               
				// Get stat increases for this player
				$stat_inc = isset($player['stat_increases']) ? $player['stat_increases'] : array('ma'=>0,'st'=>0,'ag'=>0,'pa'=>0,'av'=>0);
				
				// Create highlighting styles
				$ma_style = $stat_inc['ma'] > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '';
				$st_style = $stat_inc['st'] > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '';
				$ag_style = $stat_inc['ag'] > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '';
				$pa_style = $stat_inc['pa'] > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '';
				$av_style = $stat_inc['av'] > 0 ? ' background-color: #ccffcc; font-weight: bold;' : '';
				
				// Calculate increased stat values
				$is_star = strpos($position, 'STAR:') === 0;
				
				if (!$is_star && isset($DEA[$race_name]['players'][$position])) {
					$pos_data = $DEA[$race_name]['players'][$position];
					
					if ($stat_inc['ma'] > 0) {
						$ma_val = $pos_data['ma'] + $stat_inc['ma'];
					}
					if ($stat_inc['st'] > 0) {
						$st_val = $pos_data['st'] + $stat_inc['st'];
					}
					if ($stat_inc['ag'] > 0) {
						$ag_total = $pos_data['ag'] - $stat_inc['ag'];
						$ag_val = ($ag_total > 0 && $ag_total < 7) ? $ag_total . '+' : $ag_total;
					}
					if ($stat_inc['pa'] > 0 && isset($pos_data['pa'])) {
						$pa_total = $pos_data['pa'] - $stat_inc['pa'];
						$pa_val = ($pa_total > 0 && $pa_total < 7) ? $pa_total . '+' : $pa_total;
					}
					if ($stat_inc['av'] > 0) {
						$av_total = $pos_data['av'] + $stat_inc['av'];
						$av_val = ($av_total > 0) ? $av_total . '+' : $av_total;
					}
				}
				
				echo "<td class='ma-cell' style='text-align: center;$ma_style'>$ma_val</td>\n";
				echo "<td class='st-cell' style='text-align: center;$st_style'>$st_val</td>\n";
				echo "<td class='ag-cell' style='text-align: center;$ag_style'>$ag_val</td>\n";
				echo "<td class='pa-cell' style='text-align: center;$pa_style'>$pa_val</td>\n";
				echo "<td class='av-cell' style='text-align: center;$av_style'>$av_val</td>\n";
                echo "<td class='skills-cell'>";
                echo "<span style='color: #000; margin-bottom: 5px;' class='base-skills-display'>$base_skills";
                
                // Add extra skills inline
                if (isset($player['extra_skills']) && is_array($player['extra_skills']) && count($player['extra_skills']) > 0) {
                    $extra_skills_html = array();
                    $is_star = strpos($position, 'STAR:') === 0;
                    
                    if (!$is_star && isset($DEA[$race_name]['players'][$position])) {
                        $primary_cats = isset($DEA[$race_name]['players'][$position]['norm']) ? $DEA[$race_name]['players'][$position]['norm'] : array();
                        
                        foreach ($player['extra_skills'] as $skill_id) {
                            $skill_name = skillsTrans($skill_id);
                            $display = is_array($skill_name) ? implode(', ', $skill_name) : $skill_name;
                            
                            $is_primary = false;
                            foreach ($primary_cats as $cat) {
                                if (isset($skillarray[$cat][$skill_id])) {
                                    $is_primary = true;
                                    break;
                                }
                            }
                            
                            $color = $is_primary ? '#0000ff' : '#ff0000';
                            $extra_skills_html[] = "<span style='color: $color;'>$display</span>";
                        }
                    }
                    
                    if (count($extra_skills_html) > 0) {
                        echo ', ' . implode(', ', $extra_skills_html);
                    }
                }
                
                echo "</span>\n";
                echo "<input type='hidden' id='player-$idx-extra-skills' value='" . htmlspecialchars($extra_skills_json) . "' />";
				echo "<input type='hidden' id='player-$idx-stat-increases' value='" . htmlspecialchars($stat_increases_json) . "' />";
                
                // Hidden inputs for form submission
                echo "<div class='skills-hidden-inputs' style='display:none;'>";
                if (isset($player['extra_skills']) && is_array($player['extra_skills'])) {
                    foreach ($player['extra_skills'] as $skill_id) {
                        echo "<input type='hidden' name='players[$idx][skills][]' value='$skill_id' />";
                    }
                }
                echo "</div>";
                
                echo "<a href='javascript:void(0)' onclick='openSkillPopup($idx)' style='font-size: 11px; margin-left: 10px;'>Manage Skills</a>";
                echo "</td>\n";
                
                echo "<td class='value-cell'>$value</td>\n";
                echo "<td>";
				echo "<button type='button' class='player-button button-move' onclick='movePlayerUp($idx)' title='Move Up'>↑</button> ";
				echo "<button type='button' class='player-button button-move' onclick='movePlayerDown($idx)' title='Move Down'>↓</button> ";
				echo "<button type='button' class='player-button button-copy' onclick='copyPlayer($idx)' title='Copy this player' style='font-size: 15px;'>+</button> ";
				echo "<button type='button' class='player-button button-remove' onclick='removePlayer(this)' title='Remove'>✕</button>";
				echo "</td>\n";
                echo "</tr>\n";
            }
        }
?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f0f0f0;">
                <td colspan="7" style="text-align: left; padding-left: 5px;">
                    <button type="button" class="button-save" onclick="addPlayer()">+ Add Player Row</button>
                </td>
                <td style="text-align: right; padding-right: 10px;">Player Total:</td>
                <td id="player-value-total"><?php 
                    $player_total = 0;
                    if (isset($team_data['players'])) {
                        foreach ($team_data['players'] as $p) {
                            if (strpos($p['position'], 'STAR:') === 0) {
                                $sn = substr($p['position'], 5);
                                if (isset($stars[$sn])) $player_total += $stars[$sn]['cost'];
                            } else if (isset($DEA[$race_name]['players'][$p['position']])) {
                                $player_total += $DEA[$race_name]['players'][$p['position']]['cost'];
                            }
                        }
                    }
                    echo ($player_total / 1000) . 'k';
                ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Team Sideline Staff</div>
    <div id="sideline-content">
        <p style="color: #666;">Select a race to configure sideline staff and equipment.</p>
    </div>
</div>

<div class="section-box">
    <div class="section-title">
        Inducements 
        <a href="javascript:void(0)" id="inducements-toggle" onclick="toggleInducements()" style="font-size: 12px; margin-left: 10px;">[Collapse]</a>
    </div>
    <div id="inducements-container">
<?php if ($race_id >= 0): ?>
        <div class='tableResponsive'>
        <table class="builder-table">
            <tr>
                <th>Inducement</th>
                <th>Cost</th>
                <th>Quantity</th>
            </tr>
<?php
        foreach ($inducements as $ind_name => $ind_data) {
            // Only show source=1
            if (!isset($ind_data['source']) || $ind_data['source'] != 1) continue;
            
            // Check if available for this team
            $available = false;
            if (isset($ind_data['teamrules']) && is_array($ind_data['teamrules'])) {
                if (in_array(0, $ind_data['teamrules'])) {
                    $available = true;
                } else {
                    foreach ($team_special_rules as $rule) {
                        if (in_array($rule, $ind_data['teamrules'])) {
                            $available = true;
                            break;
                        }
                    }
                }
            }
            
            if (!$available) continue;
            
            // Check for reduced cost (by rules OR race)
            $cost = $ind_data['cost'];
            $has_reduction = false;
            
            // Check reduced cost by rules
            if (isset($ind_data['reduced_cost_rules']) && is_array($ind_data['reduced_cost_rules'])) {
                foreach ($team_special_rules as $rule) {
                    if (in_array($rule, $ind_data['reduced_cost_rules'])) {
                        $cost = $ind_data['reduced_cost'];
                        $has_reduction = true;
                        break;
                    }
                }
            }
            
            // Check reduced cost by race
            if (!$has_reduction && isset($ind_data['reduced_cost_races']) && is_array($ind_data['reduced_cost_races'])) {
                if (in_array($race_id, $ind_data['reduced_cost_races'])) {
                    $cost = $ind_data['reduced_cost'];
                    $has_reduction = true;
                }
            }
            
            // Determine max quantity (use reduced max if team has reduction)
            $max = isset($ind_data['max']) ? $ind_data['max'] : 10;
            if ($has_reduction && isset($ind_data['reduced_max'])) {
                $max = $ind_data['reduced_max'];
            }
            
            $current_qty = isset($team_data['inducements'][$ind_name]) ? $team_data['inducements'][$ind_name] : 0;
            
            echo "<tr>\n";
            echo "<td>$ind_name</td>\n";
            echo "<td>" . ($cost / 1000) . "k</td>\n";
            echo "<td><select name='inducements[" . str_replace(' ', '_', $ind_name) . "]'>\n";
            for ($i = 0; $i <= $max; $i++) {
                $sel = ($i == $current_qty) ? 'selected' : '';
                echo "<option value='$i' $sel>$i</option>\n";
            }
            echo "</select></td>\n";
            echo "</tr>\n";
        }
?>
        </table>
        </div>
<?php else: ?>
        <p style="color: #666;">Select a race to see available inducements.</p>
<?php endif; ?>
    </div>
</div>

<div class="button-bar">
    <button type="submit" class="button-save" onclick="if (validateTournamentSettings()) { saveTeam(); document.getElementById('action').value='save_team'; } else { return false; }">Save Team</button>
    <button type="button" class="button-pdf" onclick="generatePDF()">Generate PDF</button>
    <button type="button" class="button-reset" onclick="resetTeam()">Reset Team</button>
</div>

</form>
</div>
<?php
     }
    
} // <-- TournamentTeam class ends here

// NEW PDF class starts here
class TournamentTeamPDF implements ModuleInterface
{
    public static function getModuleAttributes()
    {
        return array(
            'author' => 'Val Catella',
            'moduleName' => 'Tournament Team PDF',
            'date' => '2025',
            'setCanvas' => false,
        );
    }
    
    public static function getModuleTables() { return array(); }
    public static function getModuleUpgradeSQL() { return array(); }
    public static function triggerHandler($type, $argv) {}
    
    public static function main($argv)
	{
		global $DEA, $skillarray, $rules, $inducements, $lng, $stars, $raceididx;
		
		// Clean output buffers
		while (ob_get_level()) {
			ob_end_clean();
		}
		
		// Start session
		if (!session_id()) {
			session_start();
		}
		
		// Check for team data in session
		if (!isset($_SESSION['tournament_team'])) {
			die("No team data found. Please save a team before generating the PDF.");
		}
		
		$team_data = $_SESSION['tournament_team'];
		$race_name = $team_data['race_name'];
		
		// Load PDF libraries
		require_once('modules/pdf/fpdf.php');
		require_once('modules/pdf/bb_pdf_class.php');
		
		// PDF Constants
		define("MARGINX", 20);
		define("MARGINY", 20);
		define("DEFLINECOLOR", '#000000');
		define("HEADLINEBGCOLOR", '#c3c3c3');
		define('COLOR_ROSTER_NORMAL', '#FFFFFF');
		
		// Create PDF
		$pdf = new BB_PDF('L', 'pt', 'A4');
		$pdf->SetAutoPageBreak(false, 20);
		$pdf->SetAuthor('Val Catella');
		$pdf->SetCreator('NAFLM Tournament Team Builder');
		$pdf->SetTitle('Tournament Team: ' . $team_data['team_name']);
		
		$pdf->AddFont('Tahoma', '', 'tahoma.php');
		$pdf->AddFont('Tahoma', 'B', 'tahomabd.php');
		
		$pdf->AddPage();
		$pdf->SetFont('Tahoma', 'B', 14);
		$pdf->SetLineWidth(1.5);
		
		$currentx = MARGINX;
		$currenty = MARGINY;
		
		// Header
		$pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
		$pdf->RoundedRect($currentx, $currenty, 802, 20, 6, 'DF');
		$pdf->SetDrawColorBB($pdf->hex2cmyk(DEFLINECOLOR));
		
		$pdf->SetXY($currentx + 8, $currenty);
		$pdf->Cell(200, 20, utf8_decode($team_data['team_name']), 0, 0, 'L', false);
		
		$pdf->SetFont('Tahoma', '', 12);
		$pdf->Cell(30, 20, "Race:", 0, 0, 'R', false);
		$pdf->Cell(110, 20, $race_name, 0, 0, 'L', false);

		$pdf->Cell(200, 20, "Coach: " . utf8_decode($team_data['coach_name']), 0, 0, 'R', false);

		// Add NAF Number if present
		if (!empty($team_data['naf_number'])) {
			$pdf->Cell(200, 20, "NAF No: " . utf8_decode($team_data['naf_number']), 0, 0, 'R', false);
		}

		$currenty += 25;
		$currentx += 6;
		
		// Player table header
		$pdf->SetXY($currentx, $currenty);
		$pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
		$pdf->SetFont('Tahoma', 'B', 8);
		$h = 14;
		
		$pdf->Cell(17, $h, 'Nr', 1, 0, 'C', true);
		$pdf->Cell(100, $h, 'Position', 1, 0, 'L', true);
		$pdf->Cell(17, $h, 'MA', 1, 0, 'C', true);
		$pdf->Cell(17, $h, 'ST', 1, 0, 'C', true);
		$pdf->Cell(17, $h, 'AG', 1, 0, 'C', true);
		$pdf->Cell(17, $h, 'PA', 1, 0, 'C', true);
		$pdf->Cell(17, $h, 'AV', 1, 0, 'C', true);
		$pdf->Cell(530, $h, 'Skills & Traits', 1, 0, 'L', true);
		$pdf->Cell(50, $h, 'Value', 1, 0, 'C', true);
		
		$currenty += 17;
		$pdf->SetFont('Tahoma', '', 8);
		$pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_NORMAL));
		
		// Players
		$total_value = 0;
		foreach ($team_data['players'] as $idx => $player) {
			$nr = $player['nr'];
			$position = $player['position'];
			$is_star = $player['is_star'];
			
			// Strip STAR: prefix for display in PDF
			$position_display = $position;
			if ($is_star && strpos($position, 'STAR:') === 0) {
				$position_display = substr($position, 5); // Remove "STAR:" prefix
			}
			
			// Get stats, skills and value
			if ($is_star) {
				$star_name = substr($position, 5);
				$star_data = $stars[$star_name];
				
				$ma = $star_data['ma'];
				$st = $star_data['st'];
				$ag = $star_data['ag'];
				$pa = isset($star_data['pa']) ? $star_data['pa'] : '-';
				$av = $star_data['av'];
				
				$base_skills = isset($star_data['def']) ? skillsTrans($star_data['def']) : '';
				$base_skills_str = is_array($base_skills) ? implode(', ', $base_skills) : $base_skills;
				$value = $star_data['cost'] / 1000;
			} else {
				$pos_data = $DEA[$race_name]['players'][$position];
				
				$ma = $pos_data['ma'];
				$st = $pos_data['st'];
				$ag = $pos_data['ag'];
				$pa = isset($pos_data['pa']) ? $pos_data['pa'] : '-';
				$av = $pos_data['av'];
				
				$base_skills = isset($pos_data['def']) ? skillsTrans($pos_data['def']) : '';
				$base_skills_str = is_array($base_skills) ? implode(', ', $base_skills) : $base_skills;
				$value = $pos_data['cost'] / 1000;
			}
			
			$total_value += $value;
			
			// Format AG/PA with + if they exist
			$ag_display = $ag > 0 ? $ag . '+' : '-';
			$pa_display = ($pa > 0 && $pa < 7) ? $pa . '+' : '-';
			$av_display = $av > 0 ? $av . '+' : '-';
			
			// Draw cells up to skills column
			$pdf->SetXY($currentx, $currenty);
			$pdf->Cell(17, $h, $nr, 1, 0, 'C', true);
			$pdf->Cell(100, $h, utf8_decode($position_display), 1, 0, 'L', true);
			$pdf->Cell(17, $h, $ma, 1, 0, 'C', true);
			$pdf->Cell(17, $h, $st, 1, 0, 'C', true);
			$pdf->Cell(17, $h, $ag_display, 1, 0, 'C', true);
			$pdf->Cell(17, $h, $pa_display, 1, 0, 'C', true);
			$pdf->Cell(17, $h, $av_display, 1, 0, 'C', true);
			
			// Skills column - draw border first, then content with colors
			$skills_x = $currentx + 17 + 100 + 17 + 17 + 17 + 17 + 17;
			$pdf->SetXY($skills_x, $currenty);
			$pdf->Cell(530, $h, '', 1, 0, 'L', true); // Empty cell with border
			
			// Now write skills with colors
			$pdf->SetXY($skills_x + 2, $currenty);
			
			// Base skills in black
			$pdf->SetTextColorBB(false);
			$pdf->Write($h, utf8_decode($base_skills_str));
			
			// Add extra skills with colors
			if (!$is_star && !empty($player['extra_skills'])) {
				$primary_cats = isset($pos_data['norm']) ? $pos_data['norm'] : array();
				
				foreach ($player['extra_skills'] as $skill_id) {
					$skill_trans = skillsTrans($skill_id);
					$skill_name = is_array($skill_trans) ? implode(', ', $skill_trans) : $skill_trans;
					
					// Check if primary or secondary
					$is_primary = false;
					foreach ($primary_cats as $cat) {
						if (isset($skillarray[$cat][$skill_id])) {
							$is_primary = true;
							break;
						}
					}
					
					// Set color based on primary/secondary
					if ($is_primary) {
						$pdf->SetTextColorBB($pdf->hex2cmyk('#0000ff')); // Blue for primary
					} else {
						$pdf->SetTextColorBB($pdf->hex2cmyk('#ff0000')); // Red for secondary
					}
					
					$pdf->Write($h, ', ' . utf8_decode($skill_name));
				}
				
				// Reset to black
				$pdf->SetTextColorBB(false);
			}
			
			// Value cell
			$pdf->SetXY($skills_x + 530, $currenty);
			$pdf->Cell(50, $h, $value . 'k', 1, 0, 'R', true);
			
			$currenty += $h;
		}
		
		// Add player total row
		$pdf->SetFont('Tahoma', 'B', 9);
		$pdf->SetXY($currentx, $currenty);
		$pdf->Cell(732, $h, 'PLAYER TOTAL:', 1, 0, 'R', true);
		$pdf->Cell(50, $h, round($total_value) . 'k', 1, 0, 'R', true);
		$currenty += $h;
		
		$pdf->SetFont('Tahoma', '', 8);
		
		/// Calculate skill counts and player stats
		$primary_count = 0;
		$secondary_count = 0;
		$elite_count = 0;
		$player_count = 0;
		$star_player_count = 0;
		$regular_player_count = 0;
		$elite_skill_ids = array(1, 23, 52, 54);
		global $starpairs;
		$paired_stars_found = array();

		foreach ($team_data['players'] as $player) {
			$player_count++;
			$position = $player['position'];
			$is_star = $player['is_star'];
			
			if ($is_star) {
				$star_name = substr($position, 5); // Remove "STAR:" prefix
				
				// Check if this star is part of a pair
				$is_paired = false;
				$pair_key = '';
				
				foreach ($starpairs as $parent_id => $child_id) {
					foreach ($stars as $sname => $sdata) {
						if ($sdata['id'] == $parent_id && $sname == $star_name) {
							// This is a parent
							foreach ($stars as $sname2 => $sdata2) {
								if ($sdata2['id'] == $child_id) {
									$pair_names = array($sname, $sname2);
									sort($pair_names);
									$pair_key = implode('|', $pair_names);
									$is_paired = true;
									break 3;
								}
							}
						}
						if ($sdata['id'] == $child_id && $sname == $star_name) {
							// This is a child
							foreach ($stars as $sname2 => $sdata2) {
								if ($sdata2['id'] == $parent_id) {
									$pair_names = array($sname, $sname2);
									sort($pair_names);
									$pair_key = implode('|', $pair_names);
									$is_paired = true;
									break 3;
								}
							}
						}
					}
				}
				
				if ($is_paired) {
					if (!isset($paired_stars_found[$pair_key])) {
						// First time seeing this pair - count as 1 star
						$star_player_count++;
						$paired_stars_found[$pair_key] = true;
					}
				} else {
					// Regular star without a pair
					$star_player_count++;
				}
			} else {
				$regular_player_count++;
				
				if (!empty($player['extra_skills'])) {
					$pos_data = $DEA[$race_name]['players'][$position];
					$primary_cats = isset($pos_data['norm']) ? $pos_data['norm'] : array();
					
					foreach ($player['extra_skills'] as $skill_id) {
						// Check if elite
						if (in_array($skill_id, $elite_skill_ids)) {
							$elite_count++;
						}
						
						// Check if primary or secondary
						$is_primary = false;
						foreach ($primary_cats as $cat) {
							if (isset($skillarray[$cat][$skill_id])) {
								$is_primary = true;
								break;
							}
						}
						
						if ($is_primary) {
							$primary_count++;
						} else {
							$secondary_count++;
						}
					}
				}
			}
		}

		// Add League and Special Rule section - ALWAYS SHOW, matching builder logic
		global $specialrulesarray;
		$currenty += 20;

		$pdf->SetXY($currentx, $currenty);
		$pdf->SetFont('Tahoma', '', 10);

		// Get race data for leagues and special rules
		$race_leagues = isset($DEA[$race_name]['other']['team_league']) ? $DEA[$race_name]['other']['team_league'] : array();
		$race_special_rules = isset($DEA[$race_name]['other']['special_rules']) ? $DEA[$race_name]['other']['special_rules'] : array();
		$race_fav_rules = isset($DEA[$race_name]['other']['fav_rules']) ? $DEA[$race_name]['other']['fav_rules'] : array();

		// TEAM LEAGUE - Match builder logic
		$league_text = "Team League: ";
		if (count($race_leagues) === 0) {
			$league_text .= "None";
		} elseif (count($race_leagues) === 1) {
			// Auto-display if only one league (no selection needed)
			$league_id = $race_leagues[0];
			$league_name = isset($specialrulesarray['L'][$league_id]) ? $specialrulesarray['L'][$league_id] : 'Unknown League';
			$league_text .= $league_name;
		} else {
			// Multiple leagues - use selected value
			if (isset($team_data['selected_league']) && $team_data['selected_league'] >= 0) {
				$league_id = $team_data['selected_league'];
				$league_name = isset($specialrulesarray['L'][$league_id]) ? $specialrulesarray['L'][$league_id] : 'Unknown League';
				$league_text .= $league_name;
			} else {
				$league_text .= "(Select League)";
			}
		}
		$pdf->Cell(400, 12, $league_text, 0, 0, 'L', false);
		$currenty += 12;

		// SPECIAL RULE - Match builder logic (show effective rules, exclude leagues)
		$pdf->SetXY($currentx, $currenty);
		$rule_text = "Special Rules: ";

		// Build effective rules array (matching JavaScript updateEffectiveRules logic)
		$effective_rules = $race_special_rules;

		// Add league to effective rules if applicable
		if (count($race_leagues) === 1) {
			// Auto-add single league
			if (!in_array($race_leagues[0], $effective_rules)) {
				$effective_rules[] = $race_leagues[0];
			}
		} elseif (count($race_leagues) > 1 && isset($team_data['selected_league']) && $team_data['selected_league'] >= 0) {
			// Add selected league
			if (!in_array($team_data['selected_league'], $effective_rules)) {
				$effective_rules[] = $team_data['selected_league'];
			}
		}

		// Handle favoured rules
		if (count($race_fav_rules) === 1) {
			// Auto-add single favoured rule and remove generic "Favoured of..." (12)
			$key = array_search(12, $effective_rules);
			if ($key !== false) {
				unset($effective_rules[$key]);
			}
			if (!in_array($race_fav_rules[0], $effective_rules)) {
				$effective_rules[] = $race_fav_rules[0];
			}
		} elseif (count($race_fav_rules) > 1 && isset($team_data['selected_fav_rule']) && $team_data['selected_fav_rule'] > 0) {
			// Add selected favoured rule and remove generic "Favoured of..." (12)
			$key = array_search(12, $effective_rules);
			if ($key !== false) {
				unset($effective_rules[$key]);
			}
			if (!in_array($team_data['selected_fav_rule'], $effective_rules)) {
				$effective_rules[] = $team_data['selected_fav_rule'];
			}
		}

		// Special case: Norse (race_id 14) + Chaos Clash (league 1) = auto Favoured of Khorne (15)
		if ($team_data['race_id'] === 14) {
			$current_league = (count($race_leagues) === 1) ? $race_leagues[0] : (isset($team_data['selected_league']) ? $team_data['selected_league'] : -1);
			if ($current_league === 1) {
				// Remove generic Favoured of... (12) and add Favoured of Khorne (15)
				$key = array_search(12, $effective_rules);
				if ($key !== false) {
					unset($effective_rules[$key]);
				}
				if (!in_array(15, $effective_rules)) {
					$effective_rules[] = 15;
				}
			}
		}

		// Display special rules (excluding leagues, just like builder)
		$rule_names = array();
		foreach ($effective_rules as $rule_id) {
			// Skip if this is a league ID (leagues shown separately)
			if (isset($specialrulesarray['L'][$rule_id])) {
				continue;
			}
			
			// Get rule name
			if (isset($specialrulesarray['R'][$rule_id])) {
				$rule_names[] = $specialrulesarray['R'][$rule_id];
			}
		}

		if (count($rule_names) > 0) {
			$rule_text .= implode(', ', $rule_names);
		} else {
			$rule_text .= "None";
		}
		$pdf->Cell(400, 12, $rule_text, 0, 0, 'L', false);
		$currenty += 20;

		// LEFT SIDE: Team Statistics
		$left_x = $currentx;
		$left_y = $currenty + 15;
		
		$pdf->SetFont('Tahoma', 'B', 10);
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(200, $h, 'TEAM STATISTICS', 0, 0, 'L', false);
		$left_y += 15;
		
		$pdf->SetFont('Tahoma', '', 8);
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(100, 10, 'Total Players:', 0, 0, 'L', false);
		$pdf->Cell(50, 10, $player_count . ' / 16', 0, 0, 'R', false);
		$left_y += 10;
		
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(100, 10, 'Regular Players:', 0, 0, 'L', false);
		$pdf->Cell(50, 10, $regular_player_count, 0, 0, 'R', false);
		$left_y += 10;
		
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(100, 10, 'Star Players:', 0, 0, 'L', false);
		$pdf->Cell(50, 10, $star_player_count . ' / 2', 0, 0, 'R', false);
		$left_y += 10;
		
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(100, 10, 'Primary Skills:', 0, 0, 'L', false);
		$pdf->SetTextColorBB($pdf->hex2cmyk('#0000ff'));
		$pdf->Cell(50, 10, $primary_count, 0, 0, 'R', false);
		$pdf->SetTextColorBB(false);
		$left_y += 10;
		
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(100, 10, 'Secondary Skills:', 0, 0, 'L', false);
		$pdf->SetTextColorBB($pdf->hex2cmyk('#ff0000'));
		$pdf->Cell(50, 10, $secondary_count, 0, 0, 'R', false);
		$pdf->SetTextColorBB(false);
		$left_y += 10;
		
		$pdf->SetXY($left_x, $left_y);
		$pdf->Cell(100, 10, 'Elite Skills:', 0, 0, 'L', false);
		$pdf->SetTextColorBB($pdf->hex2cmyk('#ff6600'));
		$pdf->Cell(50, 10, $elite_count, 0, 0, 'R', false);
		$pdf->SetTextColorBB(false);
		
		// RIGHT SIDE: Team Goods and Inducements
		$right_x = $currentx + 400;
		$right_y = $currenty + 15;
		$value_column_x = $currentx + 17 + 100 + 17 + 17 + 17 + 17 + 17 + 530; // Align with Value column
		
		$sl = isset($team_data['sideline']) ? $team_data['sideline'] : array();
		$sideline_total = 0;
		
		$pdf->SetFont('Tahoma', 'B', 10);
		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(200, $h, 'TEAM GOODS:', 0, 0, 'L', false);
		$right_y += 15;
		
		$pdf->SetFont('Tahoma', '', 8);
		
		// Re-rolls
		$rr_qty = isset($sl['rerolls']) ? $sl['rerolls'] : 0;
		$rr_cost = $DEA[$race_name]['other']['rr_cost'] / 1000;
		$rr_total = $rr_cost * $rr_qty;
		$sideline_total += $rr_total;
		if ($rr_qty > 0) $total_value += $rr_total;
		
		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(150, $h, 'Re-rolls:', 0, 0, 'L', false);
		$pdf->Cell(30, $h, $rr_qty . ' x ' . $rr_cost . 'k', 0, 0, 'L', false);
		$pdf->Cell(50, $h, ' = ' . $rr_total . 'k', 0, 0, 'L', false);
		$right_y += 12;
		
		// Dedicated Fans
		$fan_qty = isset($sl['dedicated_fans']) ? $sl['dedicated_fans'] : 1;
		$free_first_df = isset($sl['free_first_df']) ? $sl['free_first_df'] : 1;
		$fan_cost = $free_first_df ? (($fan_qty - 1) * ($rules['cost_fan_factor'] / 1000)) : ($fan_qty * ($rules['cost_fan_factor'] / 1000));
		$sideline_total += $fan_cost;
		if ($fan_cost > 0) $total_value += $fan_cost;

		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(150, $h, 'Dedicated Fans:', 0, 0, 'L', false);
		$pdf->Cell(30, $h, $fan_qty . ($free_first_df ? ' (1st free)' : ''), 0, 0, 'L', false);
		$pdf->Cell(50, $h, ' = ' . $fan_cost . 'k', 0, 0, 'L', false);
		$right_y += 12;

		// Assistant Coaches
		$coach_qty = isset($sl['ass_coaches']) ? $sl['ass_coaches'] : 0;
		$coach_cost = $coach_qty * ($rules['cost_ass_coaches'] / 1000);
		$sideline_total += $coach_cost;
		if ($coach_cost > 0) $total_value += $coach_cost;

		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(150, $h, 'Assistant Coaches:', 0, 0, 'L', false);
		$pdf->Cell(30, $h, $coach_qty . ' x ' . ($rules['cost_ass_coaches'] / 1000) . 'k', 0, 0, 'L', false);
		$pdf->Cell(50, $h, ' = ' . $coach_cost . 'k', 0, 0, 'L', false);
		$right_y += 12;

		// Cheerleaders
		$cheer_qty = isset($sl['cheerleaders']) ? $sl['cheerleaders'] : 0;
		$cheer_cost = $cheer_qty * ($rules['cost_cheerleaders'] / 1000);
		$sideline_total += $cheer_cost;
		if ($cheer_cost > 0) $total_value += $cheer_cost;

		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(150, $h, 'Cheerleaders:', 0, 0, 'L', false);
		$pdf->Cell(30, $h, $cheer_qty . ' x ' . ($rules['cost_cheerleaders'] / 1000) . 'k', 0, 0, 'L', false);
		$pdf->Cell(50, $h, ' = ' . $cheer_cost . 'k', 0, 0, 'L', false);
		$right_y += 12;

		// Apothecary
		$apoth_qty = isset($sl['apothecary']) ? $sl['apothecary'] : 0;
		$apoth_cost = $apoth_qty * ($rules['cost_apothecary'] / 1000);
		$sideline_total += $apoth_cost;
		if ($apoth_qty > 0) $total_value += $apoth_cost;

		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(150, $h, 'Apothecary:', 0, 0, 'L', false);
		$pdf->Cell(30, $h, $apoth_qty . ' x ' . ($rules['cost_apothecary'] / 1000) . 'k', 0, 0, 'L', false);
		$pdf->Cell(50, $h, ' = ' . $apoth_cost . 'k', 0, 0, 'L', false);
		$right_y += 12;
				
		// Team Goods Subtotal (aligned with Value column)
		$pdf->SetFont('Tahoma', 'B', 9);
		$pdf->SetXY($value_column_x, $right_y);
		$pdf->Cell(50, $h, round($sideline_total) . 'k', 0, 0, 'R', false);
		$right_y += 15;
		
		$pdf->SetFont('Tahoma', '', 8);
		
		// Inducements section
		$inducements_total = 0;
		
		if (isset($team_data['inducements']) && !empty($team_data['inducements'])) {
		$pdf->SetFont('Tahoma', 'B', 10);
		$pdf->SetXY($right_x, $right_y);
		$pdf->Cell(200, $h, 'INDUCEMENTS:', 0, 0, 'L', false);
		$right_y += 15;
		
		$pdf->SetFont('Tahoma', '', 8);
		
		// Get team special rules for pricing
		$team_special_rules = array();
		if (isset($DEA[$race_name]['other']['special_rules'])) {
			$team_special_rules = $DEA[$race_name]['other']['special_rules'];
		}
		
		foreach ($team_data['inducements'] as $ind_name => $qty) {
			if ($qty > 0) {
				$ind_name_display = str_replace('_', ' ', $ind_name);
				
				if (isset($inducements[$ind_name_display])) {
					// Start with base cost
					$ind_cost = $inducements[$ind_name_display]['cost'];
					$has_reduction = false;
					
					// Check for reduced cost by rules
					if (isset($inducements[$ind_name_display]['reduced_cost_rules']) && is_array($inducements[$ind_name_display]['reduced_cost_rules'])) {
						foreach ($team_special_rules as $rule) {
							if (in_array($rule, $inducements[$ind_name_display]['reduced_cost_rules'])) {
								$ind_cost = $inducements[$ind_name_display]['reduced_cost'];
								$has_reduction = true;
								break;
							}
						}
					}
					
					// Check for reduced cost by race
					if (!$has_reduction && isset($inducements[$ind_name_display]['reduced_cost_races']) && is_array($inducements[$ind_name_display]['reduced_cost_races'])) {
						if (in_array($team_data['race_id'], $inducements[$ind_name_display]['reduced_cost_races'])) {
							$ind_cost = $inducements[$ind_name_display]['reduced_cost'];
							$has_reduction = true;
						}
					}
					
					$ind_cost = $ind_cost / 1000;
					$ind_total = $ind_cost * $qty;
					$inducements_total += $ind_total;
					$total_value += $ind_total;
						
						$pdf->SetXY($right_x, $right_y);
						$pdf->Cell(150, $h, $ind_name_display . ':', 0, 0, 'L', false);
						$pdf->Cell(30, $h, $qty . ' x ' . $ind_cost . 'k', 0, 0, 'L', false);
						$pdf->Cell(50, $h, ' = ' . $ind_total . 'k', 0, 0, 'L', false);
						$right_y += 12;
					}
				}
			}
			
			// Inducements Subtotal (aligned with Value column)
			$pdf->SetFont('Tahoma', 'B', 9);
			$pdf->SetXY($value_column_x, $right_y);
			$pdf->Cell(50, $h, round($inducements_total) . 'k', 0, 0, 'R', false);
			$right_y += 15;
		}
		
		// Total Team Value - aligned under Value column
		$final_y = max($left_y, $right_y) + 10;
		$pdf->SetFont('Tahoma', 'B', 12);
		
		$pdf->SetXY($value_column_x - 180, $final_y);
		$pdf->Cell(180, $h + 4, 'TOTAL TEAM VALUE:', 0, 0, 'R', false);
		
		$pdf->SetXY($value_column_x, $final_y);
		$pdf->Cell(50, $h + 4, round($total_value) . 'k', 0, 0, 'R', false);
		
		// Output
		$pdf->Output('tournament_team.pdf', 'I');
		exit;
	}
}

