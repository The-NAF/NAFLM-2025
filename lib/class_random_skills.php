<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once('../settings.php');

header('Content-Type: application/json');

class BloodBowlSkillRoller {
    private $conn;
    
    // Skill incompatibility rules (mutually exclusive skills)
    private $incompatibleSkills = array(
        'Ball & Chain' => array(
            'Diving Tackle', 'Eye Gouge', 'Frenzy', 'Grab', 'Hit and Run', 
            'Leap', 'Multiple Block', 'On the Ball', 'Shadowing', 'Steady Footing'
        ),
        'Hit and Run' => array('Frenzy'),
        'Frenzy' => array('Grab', 'Hit and Run', 'Multiple Block'),
        'Grab' => array('Frenzy'),
        'Multiple Block' => array('Frenzy'),
        'Leap' => array('Pogo'),
        'Pogo' => array('Leap'),
        'Secret Weapon' => array('Saboteur'),
        'Saboteur' => array('Secret Weapon')
    );
    
    // Skill prerequisites (must have one of these skills first)
    private $skillPrerequisites = array(
        'Bullseye' => array('Throw Team-Mate'),
        'Strong Arm' => array('Throw Team-Mate'),
        'Lethal Flight' => array('Right Stuff'),
        'Violent Innovator' => array('Bombardier', 'Ball & Chain', 'Breathe Fire', 'Chainsaw', 'Stab', 'Projectile Vomit')
    );
    
    // Blood Bowl Random Skill Table Mappings
    private $skillMappings = array(
        'A' => array( // Agility
            1 => 'Catch', 2 => 'Diving Catch', 3 => 'Diving Tackle', 
            4 => 'Dodge', 5 => 'Defensive', 6 => 'Hit and Run',
            7 => 'Jump Up', 8 => 'Leap', 9 => 'Safe Pair of Hands',
            10 => 'Sidestep', 11 => 'Sprint', 12 => 'Sure Feet'
        ),
        'D' => array( // Devious
            1 => 'Dirty Player', 2 => 'Eye Gouge', 3 => 'Fumblerooski',
            4 => 'Lethal Flight', 5 => 'Lone Fouler', 6 => 'Pile Driver',
            7 => 'Put the Boot In', 8 => 'Quick Foul', 9 => 'Saboteur',
            10 => 'Shadowing', 11 => 'Sneaky Git', 12 => 'Violent Innovator'
        ),
        'G' => array( // General
            1 => 'Block', 2 => 'Dauntless', 3 => 'Fend',
            4 => 'Frenzy', 5 => 'Kick', 6 => 'Pro',
            7 => 'Steady Footing', 8 => 'Strip Ball', 9 => 'Sure Hands',
            10 => 'Tackle', 11 => 'Taunt', 12 => 'Wrestle'
        ),
        'M' => array( // Mutation
            1 => 'Big Hand', 2 => 'Claws', 3 => 'Disturbing Presence',
            4 => 'Extra Arms', 5 => 'Foul Appearance', 6 => 'Horns',
            7 => 'Iron Hard Skin', 8 => 'Monstrous Mouth', 9 => 'Prehensile Tail',
            10 => 'Tentacles', 11 => 'Two Heads', 12 => 'Very Long Legs'
        ),
        'P' => array( // Passing
            1 => 'Accurate', 2 => 'Cannoneer', 3 => 'Cloud Burster',
            4 => 'Dump-off', 5 => 'Give and Go', 6 => 'Hail Mary Pass',
            7 => 'Leader', 8 => 'Nerves of Steel', 9 => 'On the Ball',
            10 => 'Pass', 11 => 'Punt', 12 => 'Safe Pass'
        ),
        'S' => array( // Strength
            1 => 'Arm Bar', 2 => 'Brawler', 3 => 'Break Tackle',
            4 => 'Bullseye', 5 => 'Grab', 6 => 'Guard',
            7 => 'Juggernaut', 8 => 'Mighty Blow', 9 => 'Multiple Block',
            10 => 'Stand Firm', 11 => 'Strong Arm', 12 => 'Thick Skull'
        )
    );
    
    public function __construct($host, $user, $pass, $db) {
        $this->conn = mysqli_connect($host, $user, $pass, $db);
        
        if (!$this->conn) {
            throw new Exception("Database connection failed");
        }
        
        mysqli_set_charset($this->conn, 'utf8');
    }
    
    /**
     * Get player's current SPP (earned + extra)
     */
    private function getPlayerSPP($playerId) {
        $playerId = mysqli_real_escape_string($this->conn, $playerId);
        
        // Get earned SPP from mv_players
        $earnedQuery = "
            SELECT COALESCE(SUM(spp), 0) AS earned_spp
            FROM mv_players
            WHERE f_pid = '$playerId'
        ";
        
        $earnedResult = mysqli_query($this->conn, $earnedQuery);
        
        if (!$earnedResult) {
            throw new Exception("Failed to get earned SPP");
        }
        
        $earnedRow = mysqli_fetch_assoc($earnedResult);
        if (!$earnedRow) {
            $earnedSPP = 0;
        } else {
            $earnedSPP = (int)$earnedRow['earned_spp'];
        }
        
        // Get extra SPP from players table
        $extraQuery = "
            SELECT COALESCE(extra_spp, 0) AS extra_spp
            FROM players
            WHERE player_id = '$playerId'
        ";
        
        $extraResult = mysqli_query($this->conn, $extraQuery);
        
        if (!$extraResult) {
            throw new Exception("Failed to get extra SPP");
        }
        
        $extraRow = mysqli_fetch_assoc($extraResult);
        if (!$extraRow) {
            throw new Exception("Player not found");
        }
        
        $extraSPP = (int)$extraRow['extra_spp'];
        
        // Total SPP = earned + extra
        $totalSPP = $earnedSPP + $extraSPP;
        
        return $totalSPP;
    }
    
    /**
     * Get number of achieved skills for a player
     */
    private function getPlayerAchievedSkillCount($playerId) {
        $playerId = mysqli_real_escape_string($this->conn, $playerId);
        
        // Count skills from players_skills table where it's an achieved skill (not extra)
        $query = "
            SELECT COUNT(*) as skill_count
            FROM players_skills
            WHERE f_pid = '$playerId'
            AND type IN ('N', 'D', 'C')
        ";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            throw new Exception("Failed to get player skill count");
        }
        
        $row = mysqli_fetch_assoc($result);
        if (!$row) {
            return 0;
        }
        
        $skillCount = (int)$row['skill_count'];
        
        return $skillCount;
    }
    
    /**
     * Get skills the player already has (both default position skills and earned skills)
     */
    private function getPlayerExistingSkills($playerId) {
        $playerId = mysqli_real_escape_string($this->conn, $playerId);
        
        $existingSkills = array();
        
        // First, get the player's position and default skills
        $positionQuery = "
            SELECT p.f_pos_id, gp.skills
            FROM players p
            JOIN game_data_players gp ON p.f_pos_id = gp.pos_id
            WHERE p.player_id = '$playerId'
        ";
        
        $posResult = mysqli_query($this->conn, $positionQuery);
        
        if (!$posResult) {
            throw new Exception("Failed to get player skills");
        }
        
        // Get default position skills (comma-separated skill IDs)
        if ($posRow = mysqli_fetch_assoc($posResult)) {
            if (!empty($posRow['skills'])) {
                $defaultSkillIds = explode(',', $posRow['skills']);
                
                // Get skill names for these IDs
                if (!empty($defaultSkillIds)) {
                    $escapedIds = array();
                    foreach ($defaultSkillIds as $skillId) {
                        $skillId = trim($skillId);
                        if (!empty($skillId)) {
                            $escapedIds[] = "'" . mysqli_real_escape_string($this->conn, $skillId) . "'";
                        }
                    }
                    
                    if (!empty($escapedIds)) {
                        $skillIdsList = implode(',', $escapedIds);
                        $skillNamesQuery = "
                            SELECT name
                            FROM game_data_skills
                            WHERE skill_id IN ($skillIdsList)
                        ";
                        
                        $skillNamesResult = mysqli_query($this->conn, $skillNamesQuery);
                        
                        if ($skillNamesResult) {
                            while ($skillRow = mysqli_fetch_assoc($skillNamesResult)) {
                                $existingSkills[] = $skillRow['name'];
                            }
                        }
                    }
                }
            }
        }
        
        // Second, get earned skills from players_skills table
        $earnedQuery = "
            SELECT s.name
            FROM players_skills ps
            JOIN game_data_skills s ON ps.f_skill_id = s.skill_id
            WHERE ps.f_pid = '$playerId'
        ";
        
        $earnedResult = mysqli_query($this->conn, $earnedQuery);
        
        if (!$earnedResult) {
            throw new Exception("Failed to get player skills");
        }
        
        while ($row = mysqli_fetch_assoc($earnedResult)) {
            $existingSkills[] = $row['name'];
        }
        
        // Remove duplicates (in case a skill appears in both default and earned)
        $existingSkills = array_unique($existingSkills);
        
        return $existingSkills;
    }
    
    /**
     * Roll 2d6 to determine skill number (1-12)
     */
    private function rollForSkillNumber() {
        // First roll: determine range (1-3 = skills 1-6, 4-6 = skills 7-12)
        $firstRoll = mt_rand(1, 6);
        
        // Second roll: determine which skill within range
        $secondRoll = mt_rand(1, 6);
        
        // Calculate skill number (1-12)
        $skillNumber = ($firstRoll <= 3) ? $secondRoll : (6 + $secondRoll);
        
        return array(
            'skill_number' => $skillNumber,
            'rolls' => array('first' => $firstRoll, 'second' => $secondRoll)
        );
    }
    
    /**
     * Check if a skill is compatible with player's existing skills
     */
    private function isSkillCompatible($skillName, $existingSkills) {
        // Check incompatibility rules
        foreach ($this->incompatibleSkills as $baseSkill => $incompatibleList) {
            // If player has the base skill, check if new skill is in its incompatible list
            if (in_array($baseSkill, $existingSkills) && in_array($skillName, $incompatibleList)) {
                return false;
            }
            
            // If new skill is the base skill, check if player has any incompatible skills
            if ($skillName === $baseSkill) {
                foreach ($incompatibleList as $incompatible) {
                    if (in_array($incompatible, $existingSkills)) {
                        return false;
                    }
                }
            }
        }
        
        // Check prerequisite rules
        if (isset($this->skillPrerequisites[$skillName])) {
            $prerequisites = $this->skillPrerequisites[$skillName];
            $hasPrerequisite = false;
            
            foreach ($prerequisites as $prereq) {
                if (in_array($prereq, $existingSkills)) {
                    $hasPrerequisite = true;
                    break;
                }
            }
            
            // If skill requires a prerequisite and player doesn't have any, it's not compatible
            if (!$hasPrerequisite) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Roll for a skill, rerolling only if player already has it
     */
    private function rollForNewSkill($skillCat, $existingSkills, $maxAttempts = 20) {
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $roll = $this->rollForSkillNumber();
            $skillName = $this->skillMappings[$skillCat][$roll['skill_number']];
            
            // Check if player already has this skill
            if (in_array($skillName, $existingSkills)) {
                $attempts++;
                continue;
            }
            
            // Check if skill is compatible with player's existing skills
            if (!$this->isSkillCompatible($skillName, $existingSkills)) {
                $attempts++;
                continue;
            }
            
            return array(
                'skill_name' => $skillName,
                'roll' => $roll,
                'rerolled' => $attempts > 0,
                'reroll_count' => $attempts
            );
        }
        
        // If we've exhausted attempts, return the last roll anyway
        // (this handles edge case where player might have all skills in category)
        return array(
            'skill_name' => $skillName,
            'roll' => $roll,
            'rerolled' => true,
            'reroll_count' => $attempts,
            'warning' => 'Maximum reroll attempts reached - player may already have this skill'
        );
    }
    
    /**
     * Get skill details from database
     */
    private function getSkillDetails($skillNames) {
        if (empty($skillNames)) {
            return array();
        }
        
        // Escape all skill names
        $escapedNames = array();
        foreach ($skillNames as $name) {
            $escapedNames[] = "'" . mysqli_real_escape_string($this->conn, $name) . "'";
        }
        
        $namesList = implode(',', $escapedNames);
        
        $query = "
            SELECT skill_id, name, cat 
            FROM game_data_skills 
            WHERE name IN ($namesList)
        ";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            throw new Exception("Database query failed");
        }
        
        $skillMap = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $skillMap[$row['name']] = $row;
        }
        
        return $skillMap;
    }
    
    /**
	 * Roll for random skills
	 */
	public function rollSkills($playerId, $skillType, $skillCat) {
		// Validate category
		if (!isset($this->skillMappings[$skillCat])) {
			throw new Exception("Invalid skill category: $skillCat");
		}
		
		// Third Season Rules: Only Primary random skills are allowed
		if ($skillType !== 'P') {
			throw new Exception("Only Primary random skills are allowed. Secondary random skills are not permitted in Third Season rules.");
		}
		
		// Get player's current SPP and skill count
		$currentSPP = $this->getPlayerSPP($playerId);
		$numSkills = $this->getPlayerAchievedSkillCount($playerId);
		
		// SPP costs for random PRIMARY skills based on number of achieved skills
		$sppCosts = array(
			0 => 3,
			1 => 4,
			2 => 6,
			3 => 8,
			4 => 10,
			5 => 15
		);
		
		// Elite skills list
		$eliteSkills = array('Block', 'Dodge', 'Guard', 'Mighty Blow');
		
		// Get the required SPP for this skill level
		if (!isset($sppCosts[$numSkills])) {
			throw new Exception("Player has too many skills ($numSkills) to roll for random skills");
		}
		
		$requiredSPP = $sppCosts[$numSkills];
		
		// Check if player has enough SPP
		if ($currentSPP < $requiredSPP) {
			throw new Exception("Insufficient SPP. Player has $currentSPP SPP but needs $requiredSPP SPP (Player has $numSkills earned skills)");
		}
		
		// Get skills player already has
		$existingSkills = $this->getPlayerExistingSkills($playerId);
		
		// Roll for two skills
		$skill1 = $this->rollForNewSkill($skillCat, $existingSkills);
		$skill2 = $this->rollForNewSkill($skillCat, $existingSkills);
		
		// Get skill details from database
		$skillNames = array($skill1['skill_name'], $skill2['skill_name']);
		$skillMap = $this->getSkillDetails($skillNames);
		
		// Build response
		$result = array();
		
		foreach (array($skill1, $skill2) as $skillData) {
			$skillName = $skillData['skill_name'];
			
			if (!isset($skillMap[$skillName])) {
				throw new Exception("Skill '$skillName' not found in database");
			}
			
			$dbSkill = $skillMap[$skillName];
			
			// Check if this is an Elite skill
			$isElite = in_array($skillName, $eliteSkills);
			
			// Calculate value increase: 20k for primary, +10k if Elite
			$valueIncrease = $isElite ? 30000 : 20000;
			
			// Format skill name with asterisk if Elite
			$displayName = $isElite ? $skillName . ' *' : $skillName;
			
			$skillResult = array(
				'id' => $dbSkill['skill_id'],
				'name' => $displayName,
				'category' => $dbSkill['cat'],
				'description' => '', // Will be loaded by translation system
				'roll_info' => sprintf(
					"Rolled %d, %d",
					$skillData['roll']['rolls']['first'],
					$skillData['roll']['rolls']['second']
				),
				'spp_cost' => $requiredSPP,
				'value_increase' => $valueIncrease,
				'is_elite' => $isElite
			);
			
			// Add reroll information if applicable
			if ($skillData['rerolled']) {
				$skillResult['rerolled'] = true;
				$skillResult['reroll_count'] = $skillData['reroll_count'];
			}
			
			if (isset($skillData['warning'])) {
				$skillResult['warning'] = $skillData['warning'];
			}
			
			$result[] = $skillResult;
		}
		
		return array(
			'skills' => $result,
			'player_existing_skills_count' => count($existingSkills),
			'debug' => array(
				'player_id' => $playerId,
				'current_spp' => $currentSPP,
				'num_skills' => $numSkills,
				'skill_type' => $skillType,
				'required_spp' => $requiredSPP
			)
		);
	}
    
    public function __destruct() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
}

// Main execution
try {
    // Get and validate input
    $playerId = isset($_POST['player_id']) ? $_POST['player_id'] : null;
    $skillType = isset($_POST['skill_type']) ? $_POST['skill_type'] : null;
    $skillCat = isset($_POST['skill_cat']) ? $_POST['skill_cat'] : null;
    
    if (!$playerId || !$skillType || !$skillCat) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate skill category format (single uppercase letter)
    if (!preg_match('/^[ADGMPS]$/', $skillCat)) {
        throw new Exception('Invalid skill category format');
    }
    
    // Create roller and roll skills
    $roller = new BloodBowlSkillRoller($db_host, $db_user, $db_passwd, $db_name);
    $result = $roller->rollSkills($playerId, $skillType, $skillCat);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
?>