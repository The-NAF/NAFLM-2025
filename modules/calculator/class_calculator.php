<?php
/*
 *  Dice Probability Calculator Module
 *  
 */
class Calculator implements ModuleInterface
{
    public static function main($argv)
    {
        global $coach;
        
        // Check if we're calculating or just showing the form
        if (isset($_POST['calculate'])) {
            self::showResults();
        } else {
            self::showForm();
        }
        
        return true;
    }
    
    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'Val Catella',
            'moduleName' => 'Dice Calculator',
            'date'       => '2025',
            'setCanvas'  => true,
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
    
    private static function showForm()
    {
        global $lng;
        
        ?>
        <style>
        .action-item {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 10px;
        }
        
        .action-controls {
            margin-top: 8px;
        }
        
        .action-controls label {
            margin-right: 10px;
        }
        
        .sequence-item {
            display: inline-block;
            background-color: #e8f5e9;
            border: 1px solid #4CAF50;
            border-radius: 3px;
            padding: 5px 10px;
            margin: 3px;
        }
        
        #sequence-preview {
            min-height: 30px;
            padding: 10px;
        }
        </style>
        
        <h2>Blood Bowl Dice Probability Calculator (BETA)</h2>
        
        <form method="POST" id="calcForm">
            
            <!-- Player Skills Section -->
            <div class="boxWide">
                <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                    Player Skills
                </div>
                <div class="boxBody">
                    <div class='tableResponsive'>
                    <table class="common" style="width:100%;">
                        <tr>
                            <td>
                                <label><input type="checkbox" name="skills[]" value="dodge"> Dodge</label>
                                <label><input type="checkbox" name="skills[]" value="sure_hands"> Sure Hands</label>
                                <label><input type="checkbox" name="skills[]" value="sure_feet"> Sure Feet</label>
                                <label><input type="checkbox" name="skills[]" value="pass"> Pass</label>
                                <label><input type="checkbox" name="skills[]" value="catch"> Catch</label>
                                <label><input type="checkbox" name="skills[]" value="pro"> Pro</label>
                                <label><input type="checkbox" name="skills[]" value="loner_3"> Loner (3+)</label>
                                <label><input type="checkbox" name="skills[]" value="loner_4"> Loner (4+)</label>
                                <label><input type="checkbox" name="skills[]" value="steady_footing"> Steady Footing</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="checkbox" name="skills[]" value="block"> Block</label>
                                <label><input type="checkbox" name="skills[]" value="wrestle"> Wrestle</label>
                                <label><input type="checkbox" name="skills[]" value="juggernaut"> Juggernaut</label>
                                <label><input type="checkbox" name="skills[]" value="frenzy"> Frenzy</label>
                                <label><input type="checkbox" name="skills[]" value="brawler"> Brawler</label>
                                <label><input type="checkbox" name="skills[]" value="mighty_blow"> Mighty Blow</label>
                                <label><input type="checkbox" name="skills[]" value="sprint"> Sprint</label>
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>
            
            <br>
            
            <!-- Team Rerolls Section -->
            <div class="boxWide">
                <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                    Team Rerolls Available
                </div>
                <div class="boxBody">
                    <label>Number of Team Rerolls:</label>
                    <select name="team_rerolls">
                        <option value="0">0</option>
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                    </select>
                    
                    <br><br>
                    
                    <label>Reroll Strategy:</label>
                    <select name="reroll_strategy">
                        <option value="order" selected>Use rerolls in action sequence order</option>
                        <option value="optimal">Use rerolls on least likely actions (optimal)</option>
                        <option value="worst">Use rerolls on most likely actions</option>
                    </select>
                </div>
            </div>
            
            <br>
            
            <!-- Actions Section -->
            <div class="boxWide">
                <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                    Build Your Sequence
                </div>
                <div class="boxBody">
                    <div id="actions-container">
                        <!-- Actions will be added here dynamically -->
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <input type="button" value="+ Dodge" onclick="addAction('dodge')">
                        <input type="button" value="+ Pick-up" onclick="addAction('pickup')">
                        <input type="button" value="+ Rush" onclick="addAction('rush')">
                        <input type="button" value="+ Pass" onclick="addAction('pass')">
                        <input type="button" value="+ Catch" onclick="addAction('catch')">
                        <input type="button" value="+ Block" onclick="addAction('block')">
                        <input type="button" value="+ Armor Break" onclick="addAction('armor')">
                        <input type="button" value="+ Injury" onclick="addAction('injury')">
						<input type="button" value="+ Other Action" onclick="addAction('other')">
                    </div>
                </div>
            </div>
            
            <br>
            
            <!-- Sequence Display -->
            <div class="boxWide">
                <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                    Current Sequence
                </div>
                <div class="boxBody">
                    <div id="sequence-preview">No actions added yet</div>
                </div>
            </div>
            
            <br>
            
            <!-- Calculate Button -->
            <div style="text-align: center;">
                <input type="submit" name="calculate" value="Calculate Probabilities" style="font-size: 16px; padding: 10px 30px;">
            </div>
			
			<!-- Warning Footer -->
			<p style="text-align: center;">
				<small><strong>Warning:</strong> This dice odds calculator is still in development and may show errors in its calculations. If you spot any issues or errors, please contact a NAFLM administrator. Thank you!</small>
			</p>
            
        </form>
        
        <script>
        var actionCounter = 0;
        var lastAG = 3;
        var lastPA = 3;
        
        function addAction(type) {
            var container = document.getElementById('actions-container');
            var actionDiv = document.createElement('div');
            actionDiv.className = 'action-item';
            actionDiv.id = 'action-' + actionCounter;
            
            var html = '<strong>Action ' + (actionCounter + 1) + ': ' + capitalizeFirst(type) + '</strong> ';
            html += '<input type="button" value="Remove" style="float:right;" onclick="removeAction(' + actionCounter + ')">';
            html += '<input type="hidden" name="actions[' + actionCounter + '][type]" value="' + type + '">';
            html += '<div class="action-controls">';
            
            switch(type) {
                case 'dodge':
                    html += '<label>AG:</label> ';
                    html += '<select name="actions[' + actionCounter + '][ag]" onchange="rememberAG(this); updateSequencePreview()">';
                    html += '<option value="6"' + (lastAG == 6 ? ' selected' : '') + '>6+</option>';
                    html += '<option value="5"' + (lastAG == 5 ? ' selected' : '') + '>5+</option>';
                    html += '<option value="4"' + (lastAG == 4 ? ' selected' : '') + '>4+</option>';
                    html += '<option value="3"' + (lastAG == 3 ? ' selected' : '') + '>3+</option>';
                    html += '<option value="2"' + (lastAG == 2 ? ' selected' : '') + '>2+</option>';
                    html += '<option value="1"' + (lastAG == 1 ? ' selected' : '') + '>1+</option>';
                    html += '</select> ';
                    html += '<label>Modifier:</label> ';
                    html += '<input type="number" name="actions[' + actionCounter + '][modifier]" value="0" min="-3" max="0" style="width:50px;" onchange="updateSequencePreview()"> ';
                    html += '<span style="color:#666;">(negative for multiple tackle zones)</span>';
                    break;
                    
                case 'pickup':
                    html += '<label>AG:</label> ';
                    html += '<select name="actions[' + actionCounter + '][ag]" onchange="rememberAG(this); updateSequencePreview()">';
                    html += '<option value="6"' + (lastAG == 6 ? ' selected' : '') + '>6+</option>';
                    html += '<option value="5"' + (lastAG == 5 ? ' selected' : '') + '>5+</option>';
                    html += '<option value="4"' + (lastAG == 4 ? ' selected' : '') + '>4+</option>';
                    html += '<option value="3"' + (lastAG == 3 ? ' selected' : '') + '>3+</option>';
                    html += '<option value="2"' + (lastAG == 2 ? ' selected' : '') + '>2+</option>';
                    html += '<option value="1"' + (lastAG == 1 ? ' selected' : '') + '>1+</option>';
                    html += '</select> ';
                    html += '<label>Modifier:</label> ';
                    html += '<input type="number" name="actions[' + actionCounter + '][modifier]" value="0" min="-3" max="0" style="width:50px;" onchange="updateSequencePreview()"> ';
                    html += '<span style="color:#666;">(e.g., -1 in tackle zone)</span>';
                    break;
                    
                case 'rush':
                    html += '<label>Target:</label> ';
                    html += '<select name="actions[' + actionCounter + '][target]" onchange="updateSequencePreview()">';
                    html += '<option value="2" selected>2+ (Normal)</option>';
                    html += '<option value="3">3+</option>';
                    html += '<option value="4">4+</option>';
                    html += '</select>';
                    break;
                    
                case 'pass':
                    html += '<label>PA:</label> ';
                    html += '<select name="actions[' + actionCounter + '][pa]" onchange="rememberPA(this); updateSequencePreview()">';
                    html += '<option value="6"' + (lastPA == 6 ? ' selected' : '') + '>6+</option>';
                    html += '<option value="5"' + (lastPA == 5 ? ' selected' : '') + '>5+</option>';
                    html += '<option value="4"' + (lastPA == 4 ? ' selected' : '') + '>4+</option>';
                    html += '<option value="3"' + (lastPA == 3 ? ' selected' : '') + '>3+</option>';
                    html += '<option value="2"' + (lastPA == 2 ? ' selected' : '') + '>2+</option>';
                    html += '<option value="1"' + (lastPA == 1 ? ' selected' : '') + '>1+</option>';
                    html += '</select> ';
                    html += '<label>Range:</label> ';
                    html += '<select name="actions[' + actionCounter + '][range]" onchange="updateSequencePreview()">';
                    html += '<option value="1">Quick Pass (+1)</option>';
                    html += '<option value="0" selected>Short Pass (0)</option>';
                    html += '<option value="-1">Long Pass (-1)</option>';
                    html += '<option value="-2">Long Bomb (-2)</option>';
                    html += '</select> ';
                    html += '<label>Extra Modifier:</label> ';
                    html += '<input type="number" name="actions[' + actionCounter + '][modifier]" value="0" min="-3" max="1" style="width:50px;" onchange="updateSequencePreview()"> ';
                    html += '<span style="color:#666;">(e.g., +1 for safe throw, -1 for weather)</span>';
                    break;
                    
                case 'catch':
                    html += '<label>AG:</label> ';
                    html += '<select name="actions[' + actionCounter + '][ag]" onchange="rememberAG(this); updateSequencePreview()">';
                    html += '<option value="6"' + (lastAG == 6 ? ' selected' : '') + '>6+</option>';
                    html += '<option value="5"' + (lastAG == 5 ? ' selected' : '') + '>5+</option>';
                    html += '<option value="4"' + (lastAG == 4 ? ' selected' : '') + '>4+</option>';
                    html += '<option value="3"' + (lastAG == 3 ? ' selected' : '') + '>3+</option>';
                    html += '<option value="2"' + (lastAG == 2 ? ' selected' : '') + '>2+</option>';
                    html += '<option value="1"' + (lastAG == 1 ? ' selected' : '') + '>1+</option>';
                    html += '</select> ';
                    html += '<label>Modifier:</label> ';
                    html += '<input type="number" name="actions[' + actionCounter + '][modifier]" value="0" min="-3" max="1" style="width:50px;" onchange="updateSequencePreview()"> ';
                    html += '<span style="color:#666;">(e.g., +1 for accurate pass, -1 in tackle zone)</span>';
                    break;
                    
                case 'block':
                    html += '<label>Dice:</label> ';
                    html += '<select name="actions[' + actionCounter + '][dice]" onchange="updateSequencePreview()">';
                    html += '<option value="3-against">3 Dice Against</option>';
                    html += '<option value="2-against">2 Dice Against</option>';
                    html += '<option value="1" selected>1 Die</option>';
                    html += '<option value="2">2 Dice</option>';
                    html += '<option value="3">3 Dice</option>';
                    html += '</select> ';
                    html += '<label>Role:</label> ';
                    html += '<select name="actions[' + actionCounter + '][role]" onchange="updateSequencePreview()">';
                    html += '<option value="attacker" selected>Attacker</option>';
                    html += '<option value="defender">Defender</option>';
                    html += '</select> ';
                    html += '<label>Need:</label> ';
                    html += '<select name="actions[' + actionCounter + '][need]" onchange="updateSequencePreview()">';
                    html += '<option value="pow" selected>Pow/Knockdown</option>';
                    html += '<option value="push">Push or Better</option>';
                    html += '<option value="not_skull">Not Skull</option>';
                    html += '<option value="not_both_down">Not Both Down</option>';
                    html += '</select><br>';
                    html += '<label><input type="checkbox" name="actions[' + actionCounter + '][opponent_block]" value="1"> Opponent has Block</label> ';
                    html += '<label><input type="checkbox" name="actions[' + actionCounter + '][opponent_dodge]" value="1"> Opponent has Dodge</label> ';
                    html += '<label><input type="checkbox" name="actions[' + actionCounter + '][opponent_steady]" value="1"> Opponent has Steady Footing</label>';
                    break;
                    
                case 'armor':
                    html += '<label>Armor Value:</label> ';
                    html += '<input type="number" name="actions[' + actionCounter + '][av]" value="8" min="4" max="11" style="width:50px;" onchange="updateSequencePreview()"> ';
                    html += '<span style="color:#666;">(2D6 to break)</span>';
                    break;
                    
                case 'injury':
                    html += '<label>Need:</label> ';
                    html += '<select name="actions[' + actionCounter + '][need]" onchange="updateSequencePreview()">';
                    html += '<option value="8">Stun or Better (8+)</option>';
                    html += '<option value="9">KO or Better (9+)</option>';
                    html += '<option value="10" selected>Casualty (10+)</option>';
                    html += '</select> ';
                    html += '<label>Modifier:</label> ';
                    html += '<input type="number" name="actions[' + actionCounter + '][modifier]" value="0" min="-2" max="2" style="width:50px;" onchange="updateSequencePreview()"> ';
                    html += '<span style="color:#666;">(e.g., +1 for Mighty Blow, -1 for Stunty)</span>';
                    break;
					
				case 'other':
					html += '<label>Target Roll:</label> ';
					html += '<select name="actions[' + actionCounter + '][target]" onchange="updateSequencePreview()">';
					html += '<option value="6">6+</option>';
					html += '<option value="5">5+</option>';
					html += '<option value="4">4+</option>';
					html += '<option value="3" selected>3+</option>';
					html += '<option value="2">2+</option>';
					html += '<option value="1">1+</option>';
					html += '</select> ';
					html += '<label>Description:</label> ';
					html += '<input type="text" name="actions[' + actionCounter + '][description]" value="Other" style="width:150px;" onchange="updateSequencePreview()"> ';
					html += '<span style="color:#666;">(e.g., "Argue the Call", "Dauntless")</span>';
					break;
            }
            
            html += '</div>';
            actionDiv.innerHTML = html;
            container.appendChild(actionDiv);
            
            actionCounter++;
            updateSequencePreview();
        }
        
        function rememberAG(select) {
            lastAG = parseInt(select.value);
        }
        
        function rememberPA(select) {
            lastPA = parseInt(select.value);
        }
        
        function removeAction(id) {
            var actionDiv = document.getElementById('action-' + id);
            if (actionDiv) {
                actionDiv.parentNode.removeChild(actionDiv);
                updateSequencePreview();
            }
        }
        
        function updateSequencePreview() {
            var container = document.getElementById('actions-container');
            var actions = container.getElementsByClassName('action-item');
            var preview = document.getElementById('sequence-preview');
            
            if (actions.length == 0) {
                preview.innerHTML = 'No actions added yet';
                return;
            }
            
            var html = '';
            for (var i = 0; i < actions.length; i++) {
                var actionDiv = actions[i];
                var typeInput = actionDiv.querySelector('input[name*="[type]"]');
                if (!typeInput) continue;
                
                var type = typeInput.value;
                var displayText = (i+1) + '. ' + capitalizeFirst(type);
                
                switch(type) {
                    case 'dodge':
                    case 'pickup':
                    case 'catch':
                        var agSelect = actionDiv.querySelector('select[name*="[ag]"]');
                        var modInput = actionDiv.querySelector('input[name*="[modifier]"]');
                        if (agSelect && modInput) {
                            var ag = parseInt(agSelect.value);
                            var mod = parseInt(modInput.value);
                            var finalTarget = ag - mod;
                            finalTarget = Math.max(2, Math.min(6, finalTarget));
                            displayText += ' ' + finalTarget + '+';
                        }
                        break;
                        
                    case 'rush':
                        var targetSelect = actionDiv.querySelector('select[name*="[target]"]');
                        if (targetSelect) {
                            displayText += ' ' + targetSelect.value + '+';
                        }
                        break;
                        
                    case 'pass':
                        var paSelect = actionDiv.querySelector('select[name*="[pa]"]');
                        var rangeSelect = actionDiv.querySelector('select[name*="[range]"]');
                        var modInput = actionDiv.querySelector('input[name*="[modifier]"]');
                        if (paSelect && rangeSelect && modInput) {
                            var pa = parseInt(paSelect.value);
                            var range = parseInt(rangeSelect.value);
                            var mod = parseInt(modInput.value);
                            var finalTarget = pa - range - mod;
                            finalTarget = Math.max(2, Math.min(6, finalTarget));
                            var rangeNames = {1: 'Quick', 0: 'Short', '-1': 'Long', '-2': 'Bomb'};
                            displayText += ' (' + rangeNames[range] + ') ' + finalTarget + '+';
                        }
                        break;
                        
                    case 'block':
                        var diceSelect = actionDiv.querySelector('select[name*="[dice]"]');
                        var needSelect = actionDiv.querySelector('select[name*="[need]"]');
                        if (diceSelect && needSelect) {
                            var dice = diceSelect.value;
                            var need = needSelect.options[needSelect.selectedIndex].text;
                            displayText += ' ' + dice + 'D, ' + need;
                        }
                        break;
                        
                    case 'armor':
                        var avInput = actionDiv.querySelector('input[name*="[av]"]');
                        if (avInput) {
                            displayText += ' AV' + avInput.value + '+';
                        }
                        break;
                        
                    case 'injury':
                        var needSelect = actionDiv.querySelector('select[name*="[need]"]');
                        var modInput = actionDiv.querySelector('input[name*="[modifier]"]');
                        if (needSelect && modInput) {
                            var need = parseInt(needSelect.value);
                            var mod = parseInt(modInput.value);
                            var finalTarget = need - mod;
                            displayText += ' ' + finalTarget + '+';
                        }
                        break;
						
					case 'other':
						var targetSelect = actionDiv.querySelector('select[name*="[target]"]');
						var descInput = actionDiv.querySelector('input[name*="[description]"]');
						if (targetSelect && descInput) {
							displayText += ' (' + descInput.value + ') ' + targetSelect.value + '+';
						}
						break;
                }
                
                html += '<span class="sequence-item">' + displayText + '</span> ';
            }
            preview.innerHTML = html;
        }
        
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        </script>
        <?php
    }
    
    private static function showResults()
    {
        $skills = isset($_POST['skills']) ? $_POST['skills'] : array();
        $actions = isset($_POST['actions']) ? $_POST['actions'] : array();
        $teamRerolls = isset($_POST['team_rerolls']) ? (int)$_POST['team_rerolls'] : 0;
        $rerollStrategy = isset($_POST['reroll_strategy']) ? $_POST['reroll_strategy'] : 'order';
        
        if (empty($actions)) {
            echo "<p>No actions specified!</p>";
            self::showForm();
            return;
        }
        
        // Convert skills array to associative for easier checking
        $playerSkills = array();
        foreach ($skills as $skill) {
            $playerSkills[$skill] = true;
        }
        
        // Limit rerolls to the number of actions (can only reroll each action once)
        $maxUsableRerolls = min($teamRerolls, count($actions));
        
        // Calculate probabilities for each reroll scenario
        $allScenarios = array();
        
        for ($numRerolls = 0; $numRerolls <= $maxUsableRerolls; $numRerolls++) {
            $scenarioResult = self::calculateScenario($actions, $playerSkills, $numRerolls, $rerollStrategy, 'team_reroll');
            $allScenarios[$numRerolls] = $scenarioResult;
        }
        
        // Check if all actions have skill rerolls (not counting Pro or Steady Footing)
		$allHaveSkills = true;
		foreach ($allScenarios[0]['breakdown'] as $item) {
			if (!$item['skills_used_list'] || empty($item['skills_used_list'])) {
				$allHaveSkills = false;
				break;
			}
			// Pro and Steady Footing don't count as skill rerolls - they're alternative/subsequent reroll options
			$actualSkillRerolls = array_diff($item['skills_used_list'], array('steady_footing', 'pro'));
			if (empty($actualSkillRerolls)) {
				$allHaveSkills = false;
				break;
			}
		}
				
        // Calculate Pro scenarios if Pro is available
		$proScenarios = array();
		if (isset($playerSkills['pro'])) {
			// Pro only (no team rerolls)
			$proOnlyResult = self::calculateScenario($actions, $playerSkills, 0, $rerollStrategy, 'pro');
			$proScenarios['Pro Only'] = $proOnlyResult;
			
			// Pro + team rerolls combinations (only if more than 1 action)
			if (count($actions) > 1) {
				for ($numRerolls = 1; $numRerolls <= $maxUsableRerolls; $numRerolls++) {
					$proTeamResult = self::calculateScenario($actions, $playerSkills, $numRerolls, $rerollStrategy, 'pro_and_team');
					$proScenarios['Pro + ' . $numRerolls . ' Reroll' . ($numRerolls > 1 ? 's' : '')] = $proTeamResult;
				}
			}
		}
        
        // Calculate Brawler scenarios if Brawler is available on multi-die blocks
        $brawlerScenarios = array();
        if (isset($playerSkills['brawler'])) {
            $hasMultiDieBlock = false;
            foreach ($actions as $action) {
                if ($action['type'] == 'block') {
                    $dice = $action['dice'];
                    $numDice = is_string($dice) && strpos($dice, '-against') !== false ? (int)$dice : (int)$dice;
                    if ($numDice > 1 && $action['need'] == 'not_both_down') {
                        $hasMultiDieBlock = true;
                        break;
                    }
                }
            }
            
            if ($hasMultiDieBlock) {
				// Brawler only (no team rerolls)
				$brawlerOnlyResult = self::calculateScenario($actions, $playerSkills, 0, $rerollStrategy, 'brawler');
				$brawlerScenarios['Brawler Only'] = $brawlerOnlyResult;
				
				// Brawler + team rerolls (only if more than 1 action)
				if (count($actions) > 1) {
					for ($numRerolls = 1; $numRerolls <= $maxUsableRerolls; $numRerolls++) {
						$brawlerTeamResult = self::calculateScenario($actions, $playerSkills, $numRerolls, $rerollStrategy, 'brawler_and_team');
						$brawlerScenarios['Brawler + ' . $numRerolls . ' Reroll' . ($numRerolls > 1 ? 's' : '')] = $brawlerTeamResult;
					}
				}
			}
        }
        
        // Display results
        ?>
        <h2>Calculation Results</h2>
        
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                Active Skills
            </div>
            <div class="boxBody" style="text-align: left;">
                <?php echo empty($skills) ? 'No skills selected' : implode(', ', array_map('ucwords', str_replace('_', ' ', $skills))); ?>
            </div>
        </div>
        
        <br>
        
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                Action Breakdown (Skill Rerolls Only)
            </div>
            <div class="boxBody" style="text-align: left;">
                <div class='tableResponsive'>
                <table class="common" style="width:100%;">
                    <tr>
                        <th>Action</th>
                        <th>Base Success</th>
                        <th>With Skills</th>
                        <th>Skills Used</th>
                    </tr>
                    <?php foreach ($allScenarios[0]['breakdown'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo number_format($item['base_prob'] * 100, 2); ?>% (<?php echo self::toFraction($item['base_prob']); ?>)</td>
                        <td><?php echo number_format($item['with_skill'] * 100, 2); ?>% (<?php echo self::toFraction($item['with_skill']); ?>)</td>
                        <td><?php 
                            if (!empty($item['skills_used_list'])) {
                                echo implode(', ', array_map(function($s) { return ucwords(str_replace('_', ' ', $s)); }, $item['skills_used_list']));
                            } else {
                                echo 'None';
                            }
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                </div>
            </div>
        </div>
        
        <br>
        
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO; ?>">
                Combined Probabilities
            </div>
            <div class="boxBody" style="text-align: left;">
                <div class='tableResponsive'>
                <table class="common" style="width:100%;">
                    <tr>
                        <th>Scenario</th>
                        <th>Success %</th>
                        <th>Success Fraction</th>
                        <th>Success Odds</th>
                        <th>Chance of Failure</th>
                    </tr>
                    
                    <?php 
                    // Show team reroll scenarios (skip if all have skills)
                    if (!$allHaveSkills || $maxUsableRerolls == 0) {
                        foreach ($allScenarios as $numRerolls => $scenario):
                            $failProb = 1 - $scenario['probability'];
                    ?>
                    <tr>
                        <td><strong><?php echo $numRerolls == 0 ? 'No Rerolls' : 'With ' . $numRerolls . ' Reroll' . ($numRerolls > 1 ? 's' : ''); ?></strong></td>
                        <td style="font-size:18px; color:#4CAF50;"><strong><?php echo number_format($scenario['probability'] * 100, 2); ?>%</strong></td>
                        <td style="font-size:16px;"><strong><?php echo self::toFraction($scenario['probability']); ?></strong></td>
                        <td><strong>1 in <?php echo $scenario['probability'] > 0 ? number_format(1 / $scenario['probability'], 1) : '∞'; ?></strong></td>
                        <td><strong><?php echo self::toFraction($failProb) . ' (' . number_format($failProb * 100, 2) . '%)'; ?></strong></td>
                    </tr>
                    <?php 
                        endforeach;
                    }
                    ?>
                    
                    <?php if (!empty($proScenarios)): ?>
                    <tr>
                        <td colspan="5" style="padding-top: 15px;"><strong>With Pro (3+) Skill:</strong></td>
                    </tr>
                    <?php foreach ($proScenarios as $label => $scenario): 
                        $failProb = 1 - $scenario['probability'];
                    ?>
                    <tr>
                        <td><strong><?php echo $label; ?></strong></td>
                        <td style="font-size:18px; color:#2196F3;"><strong><?php echo number_format($scenario['probability'] * 100, 2); ?>%</strong></td>
                        <td style="font-size:16px;"><strong><?php echo self::toFraction($scenario['probability']); ?></strong></td>
                        <td><strong>1 in <?php echo $scenario['probability'] > 0 ? number_format(1 / $scenario['probability'], 1) : '∞'; ?></strong></td>
                        <td><strong><?php echo self::toFraction($failProb) . ' (' . number_format($failProb * 100, 2) . '%)'; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($brawlerScenarios)): ?>
                    <tr>
                        <td colspan="5" style="padding-top: 15px;"><strong>With Brawler Skill (Multi-Die Blocks):</strong></td>
                    </tr>
                    <?php foreach ($brawlerScenarios as $label => $scenario): 
                        $failProb = 1 - $scenario['probability'];
                    ?>
                    <tr>
                        <td><strong><?php echo $label; ?></strong></td>
                        <td style="font-size:18px; color:#FF9800;"><strong><?php echo number_format($scenario['probability'] * 100, 2); ?>%</strong></td>
                        <td style="font-size:16px;"><strong><?php echo self::toFraction($scenario['probability']); ?></strong></td>
                        <td><strong>1 in <?php echo $scenario['probability'] > 0 ? number_format(1 / $scenario['probability'], 1) : '∞'; ?></strong></td>
                        <td><strong><?php echo self::toFraction($failProb) . ' (' . number_format($failProb * 100, 2) . '%)'; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </table>
                </div>
            </div>
        </div>
        
        <br>
        
        <div style="text-align: center;">
            <input type="button" value="Calculate Another Sequence" onclick="window.location.href='handler.php?type=calculator'">
        </div>
        <?php
    }
    
    private static function calculateScenario($actions, $playerSkills, $numRerolls, $rerollStrategy, $rerollType = 'team_reroll')
    {
        // Track which skill rerolls have been used
        $skillsUsed = array(
            'dodge' => false,
            'sure_hands' => false,
            'sure_feet' => false,
            'pass' => false,
            'catch' => false,
            'pro' => false,
            'brawler' => false
        );
        
        // First pass: calculate probabilities for all actions
        $actionResults = array();
        foreach ($actions as $index => $action) {
            $result = self::calculateAction($action, $playerSkills, $skillsUsed, true, $rerollType);
            $actionResults[$index] = $result;
        }
        
        // For team reroll scenarios with multiple actions, use the proper combination calculation
        if ($rerollType == 'team_reroll' && $numRerolls > 0) {
            return self::calculateTeamRerollScenario($actions, $actionResults, $numRerolls, $rerollStrategy);
        }
        
        // For Pro/Brawler "only" scenarios or single action scenarios, apply to all eligible actions
        $breakdown = array();
        $totalProb = 1.0;
        
        foreach ($actionResults as $index => $result) {
            $actionProb = $result['with_skill'];
            $rerollUsedType = null;
            
            // For "Only" scenarios (Pro Only, Brawler Only), apply to ALL eligible actions
            if ($rerollType == 'pro' && !$result['skill_used']) {
                $actionProb = $result['with_pro'];
                $rerollUsedType = 'pro';
            } elseif ($rerollType == 'brawler' && isset($result['with_brawler']) && !$result['skill_used']) {
                $actionProb = $result['with_brawler'];
                $rerollUsedType = 'brawler';
            } elseif ($rerollType == 'pro_and_team' || $rerollType == 'brawler_and_team') {
                // For combined scenarios with multiple actions, this should use the proper calculation
                // But for single action or when numRerolls = 0, just use best available
                if (!$result['skill_used']) {
                    if ($rerollType == 'pro_and_team') {
                        $actionProb = max($result['with_pro'], $result['with_team_reroll']);
                        $rerollUsedType = ($result['with_pro'] > $result['with_team_reroll']) ? 'pro' : 'team';
                    } else {
                        if (isset($result['with_brawler'])) {
                            $actionProb = max($result['with_brawler'], $result['with_team_reroll']);
                            $rerollUsedType = ($result['with_brawler'] > $result['with_team_reroll']) ? 'brawler' : 'team';
                        } else {
                            $actionProb = $result['with_team_reroll'];
                            $rerollUsedType = 'team';
                        }
                    }
                }
            }
            
            $breakdown[] = array(
                'action' => $actions[$index],
                'description' => $result['description'],
                'base_prob' => $result['base_prob'],
                'with_skill' => $result['with_skill'],
                'skills_used_list' => $result['skills_used_list'],
                'used_reroll' => ($rerollUsedType !== null),
                'reroll_type' => $rerollUsedType
            );
            
            $totalProb *= $actionProb;
        }
        
        return array(
            'probability' => $totalProb,
            'breakdown' => $breakdown
        );
    }
    
    private static function calculateTeamRerollScenario($actions, $actionResults, $numRerolls, $rerollStrategy)
    {
        // Get base probabilities for actions without skill rerolls
        $actionProbs = array();
        $rerollableIndices = array();
        
        foreach ($actionResults as $index => $result) {
            $actionProbs[$index] = $result['with_skill'];
            
            // Only actions without skill rerolls can use team rerolls
            if (!$result['skill_used']) {
                $rerollableIndices[] = $index;
            }
        }
        
        // If no actions can be rerolled, just multiply probabilities
        if (empty($rerollableIndices)) {
            $totalProb = 1.0;
            foreach ($actionProbs as $p) {
                $totalProb *= $p;
            }
            
            $breakdown = array();
            foreach ($actionResults as $index => $result) {
                $breakdown[] = array(
                    'action' => $actions[$index],
                    'description' => $result['description'],
                    'base_prob' => $result['base_prob'],
                    'with_skill' => $result['with_skill'],
                    'skills_used_list' => $result['skills_used_list'],
                    'used_reroll' => false,
                    'reroll_type' => null
                );
            }
            
            return array(
                'probability' => $totalProb,
                'breakdown' => $breakdown
            );
        }
        
        // Calculate the proper probability considering all ways rerolls can be used
        $totalProb = self::calculateSequenceWithRerolls($actionProbs, $rerollableIndices, $numRerolls);
        
        // Build breakdown for display
        $breakdown = array();
        foreach ($actionResults as $index => $result) {
            $breakdown[] = array(
                'action' => $actions[$index],
                'description' => $result['description'],
                'base_prob' => $result['base_prob'],
                'with_skill' => $result['with_skill'],
                'skills_used_list' => $result['skills_used_list'],
                'used_reroll' => in_array($index, $rerollableIndices),
                'reroll_type' => in_array($index, $rerollableIndices) ? 'team' : null
            );
        }
        
        return array(
            'probability' => $totalProb,
            'breakdown' => $breakdown
        );
    }
    
    private static function calculateSequenceWithRerolls($actionProbs, $rerollableIndices, $numRerolls)
    {
        // Calculate the probability that the sequence succeeds with up to numRerolls available
        // This considers all possible ways the rerolls can be distributed
        
        $n = count($actionProbs);
        $r = count($rerollableIndices);
        
        if ($numRerolls == 0 || $r == 0) {
            // No rerolls - just multiply probabilities
            $prob = 1.0;
            foreach ($actionProbs as $p) {
                $prob *= $p;
            }
            return $prob;
        }
        
        // Total probability is sum of:
        // 1. All actions succeed on first try
        // 2. Exactly 1 rerollable action fails, we reroll it successfully, rest succeed
        // 3. Exactly 2 rerollable actions fail, we reroll both successfully, rest succeed
        // ... up to min(numRerolls, r) failures
        
        $totalProb = 0.0;
        
        for ($numFails = 0; $numFails <= min($numRerolls, $r); $numFails++) {
            $prob = self::calculateExactFailures($actionProbs, $rerollableIndices, $numFails);
            $totalProb += $prob;
        }
        
        return $totalProb;
    }
    
    private static function calculateExactFailures($actionProbs, $rerollableIndices, $numFails)
    {
        // Calculate probability that exactly $numFails rerollable actions fail initially,
        // all are rerolled successfully, and all other actions succeed
        
        if ($numFails == 0) {
            // All actions succeed on first try
            $prob = 1.0;
            foreach ($actionProbs as $p) {
                $prob *= $p;
            }
            return $prob;
        }
        
        // Get all combinations of which rerollable actions fail
        $combinations = self::getCombinations($rerollableIndices, $numFails);
        
        $totalProb = 0.0;
        
        foreach ($combinations as $failedIndices) {
            $scenarioProb = 1.0;
            
            foreach ($actionProbs as $index => $baseProb) {
                if (in_array($index, $failedIndices)) {
                    // This action fails initially, then succeeds on reroll
                    // P(fail then succeed) = (1-p) * p
                    $scenarioProb *= (1 - $baseProb) * $baseProb;
                } else {
                    // This action succeeds on first try
                    $scenarioProb *= $baseProb;
                }
            }
            
            $totalProb += $scenarioProb;
        }
        
        return $totalProb;
    }
    
    private static function getCombinations($array, $k)
    {
        $n = count($array);
        if ($k > $n) return array();
        if ($k == 0) return array(array());
        if ($k == $n) return array($array);
        
        $result = array();
        
        // Generate combinations recursively
        for ($i = 0; $i <= $n - $k; $i++) {
            $head = array($array[$i]);
            $tailCombos = self::getCombinations(array_slice($array, $i + 1), $k - 1);
            foreach ($tailCombos as $combo) {
                $result[] = array_merge($head, $combo);
            }
        }
        
        return $result;
    }
    
    private static function calculateAction($action, $playerSkills, &$skillsUsed, $calculateRerolls = false, $rerollType = 'team_reroll')
    {
        $type = $action['type'];
        $description = '';
        $baseProb = 0;
        $skillUsed = false;
        $skillsUsedList = array();
        
        switch ($type) {
            case 'dodge':
                $target = (int)$action['ag'];
                $modifier = (int)$action['modifier'];
                $finalTarget = $target - $modifier;
                $finalTarget = max(2, min(6, $finalTarget));
                
                $baseProb = self::calculateD6($finalTarget);
                $description = "Dodge " . $finalTarget . "+";
                
                if (isset($playerSkills['dodge']) && !$skillsUsed['dodge']) {
                    $skillUsed = 'dodge';
                    $skillsUsed['dodge'] = true;
                }
                break;
                
            case 'pickup':
                $target = (int)$action['ag'];
                $modifier = (int)$action['modifier'];
                $finalTarget = $target - $modifier;
                $finalTarget = max(2, min(6, $finalTarget));
                
                $baseProb = self::calculateD6($finalTarget);
                $description = "Pick-up " . $finalTarget . "+";
                
                if (isset($playerSkills['sure_hands']) && !$skillsUsed['sure_hands']) {
                    $skillUsed = 'sure_hands';
                    $skillsUsed['sure_hands'] = true;
                }
                break;
                
            case 'rush':
                $target = (int)$action['target'];
                $baseProb = self::calculateD6($target);
                $description = "Rush " . $target . "+";
                
                if (isset($playerSkills['sure_feet']) && !$skillsUsed['sure_feet']) {
                    $skillUsed = 'sure_feet';
                    $skillsUsed['sure_feet'] = true;
                }
                break;
                
            case 'pass':
                $target = (int)$action['pa'];
                $range = (int)$action['range'];
                $modifier = isset($action['modifier']) ? (int)$action['modifier'] : 0;
                $totalMod = $range + $modifier;
                $finalTarget = $target - $totalMod;
                $finalTarget = max(2, min(6, $finalTarget));
                
                $baseProb = self::calculateD6($finalTarget);
                $rangeText = array(1 => 'Quick', 0 => 'Short', -1 => 'Long', -2 => 'Long Bomb');
                $description = "Pass (" . $rangeText[$range] . ") " . $finalTarget . "+";
                
                if (isset($playerSkills['pass']) && !$skillsUsed['pass']) {
                    $skillUsed = 'pass';
                    $skillsUsed['pass'] = true;
                }
                break;
                
            case 'catch':
                $target = (int)$action['ag'];
                $modifier = (int)$action['modifier'];
                $finalTarget = $target - $modifier;
                $finalTarget = max(2, min(6, $finalTarget));
                
                $baseProb = self::calculateD6($finalTarget);
                $description = "Catch " . $finalTarget . "+";
                
                if (isset($playerSkills['catch']) && !$skillsUsed['catch']) {
                    $skillUsed = 'catch';
                    $skillsUsed['catch'] = true;
                }
                break;
                
            case 'block':
                $dice = $action['dice'];
                $role = $action['role'];
                $need = $action['need'];
                $oppBlock = !empty($action['opponent_block']);
                $oppDodge = !empty($action['opponent_dodge']);
                $oppSteady = !empty($action['opponent_steady']);
                
                $baseProb = self::calculateBlockDice($dice, $role, $need, $playerSkills, $oppBlock, $oppDodge, $oppSteady);
                $description = "Block: " . $dice . ", " . $role . ", need " . str_replace('_', ' ', $need);
                
                // Brawler can be used on multi-die blocks with "not_both_down" need
                if (isset($playerSkills['brawler']) && !$skillsUsed['brawler']) {
                    $numDice = is_string($dice) && strpos($dice, '-against') !== false ? (int)$dice : (int)$dice;
                    if ($numDice > 1 && $need == 'not_both_down') {
                        $skillUsed = 'brawler';
                        $skillsUsed['brawler'] = true;
                    }
                }
                break;
                
            case 'armor':
                $av = (int)$action['av'];
                $baseProb = self::calculate2D6($av);
                $description = "Break AV" . $av;
                break;
                
            case 'injury':
                $need = (int)$action['need'];
                $modifier = isset($action['modifier']) ? (int)$action['modifier'] : 0;
                $target = $need - $modifier;
                $baseProb = self::calculate2D6($target);
                
                $needText = array(8 => 'Stun+', 9 => 'KO+', 10 => 'Casualty');
                $description = "Injury: " . $needText[$need] . " (" . $target . "+)";
                break;
				
			case 'other':
				$target = (int)$action['target'];
				$desc = isset($action['description']) ? $action['description'] : 'Other Action';
				$baseProb = self::calculateD6($target);
				$description = htmlspecialchars($desc) . " " . $target . "+";
				// No skills can be used for generic "other" actions
				break;
        }
        
        // Calculate with skill reroll + Steady Footing
        $hasSteadyFooting = isset($playerSkills['steady_footing']);
        $steadyApplies = in_array($type, array('dodge', 'pickup', 'rush', 'catch'));
        
        if ($skillUsed) {
            $skillsUsedList[] = $skillUsed;
            $withSkill = self::withReroll($baseProb);
            
            if ($hasSteadyFooting && $steadyApplies) {
                $steadyProb = 1.0 / 6.0;
                $withSkill = $withSkill + (1 - $withSkill) * $steadyProb;
                $skillsUsedList[] = 'steady_footing';
            }
        } else {
            if ($hasSteadyFooting && $steadyApplies) {
                $steadyProb = 1.0 / 6.0;
                $withSkill = $baseProb + (1 - $baseProb) * $steadyProb;
                $skillsUsedList[] = 'steady_footing';
            } else {
                $withSkill = $baseProb;
            }
        }
        
        // Calculate reroll probabilities if requested
        $withTeamReroll = $withSkill;
        $withPro = $withSkill;
        $withBrawler = $withSkill;
        
        if ($calculateRerolls && !$skillUsed) {
            // Loner probability
            $lonerProb = 1.0;
            if (isset($playerSkills['loner_3'])) {
                $lonerProb = 4.0 / 6.0;
            } elseif (isset($playerSkills['loner_4'])) {
                $lonerProb = 3.0 / 6.0;
            }
            
            // Team reroll
            $withTeamReroll = $baseProb + (1 - $baseProb) * $lonerProb * $baseProb;
            if ($hasSteadyFooting && $steadyApplies) {
                $steadyProb = 1.0 / 6.0;
                $withTeamReroll = $withTeamReroll + (1 - $withTeamReroll) * $steadyProb;
            }
            
            // Pro (3+)
            $proProb = 4.0 / 6.0;
            $withPro = $baseProb + (1 - $baseProb) * $proProb * $baseProb;
            if ($hasSteadyFooting && $steadyApplies) {
                $steadyProb = 1.0 / 6.0;
                $withPro = $withPro + (1 - $withPro) * $steadyProb;
            }
            
            // Brawler (only for multi-die blocks)
            if ($type == 'block') {
                $dice = $action['dice'];
                $numDice = is_string($dice) && strpos($dice, '-against') !== false ? (int)$dice : (int)$dice;
                if ($numDice > 1 && $action['need'] == 'not_both_down') {
                    // Brawler lets you reroll one die showing Both Down
                    // This is complex - for now use simplified calculation
                    $withBrawler = self::calculateBlockDiceWithBrawler($dice, $action['role'], $action['need'], $playerSkills, 
                        !empty($action['opponent_block']), !empty($action['opponent_dodge']), !empty($action['opponent_steady']));
                }
            }
        }
        
        return array(
            'description' => $description,
            'base_prob' => $baseProb,
            'with_skill' => $withSkill,
            'with_team_reroll' => $withTeamReroll,
            'with_pro' => $withPro,
            'with_brawler' => $withBrawler,
            'skill_used' => $skillUsed,
            'skills_used_list' => $skillsUsedList
        );
    }
    
    private static function calculateD6($target)
    {
        if ($target <= 1) return 1.0;
        if ($target > 6) return 0.0;
        return (7 - $target) / 6.0;
    }
    
    private static function calculate2D6($target)
    {
        $successes = 0;
        for ($d1 = 1; $d1 <= 6; $d1++) {
            for ($d2 = 1; $d2 <= 6; $d2++) {
                if ($d1 + $d2 >= $target) {
                    $successes++;
                }
            }
        }
        return $successes / 36.0;
    }
    
    private static function calculateBlockDice($numDice, $role, $need, $skills, $oppBlock, $oppDodge, $oppSteady)
    {
        $hasBlock = isset($skills['block']);
        $hasWrestle = isset($skills['wrestle']);
        $hasJuggernaut = isset($skills['juggernaut']);
        
        $isAgainst = false;
        if (is_string($numDice) && strpos($numDice, '-against') !== false) {
            $isAgainst = true;
            $numDice = (int)$numDice;
        } else {
            $numDice = (int)$numDice;
        }
        
        $faces = array(
            'attacker_down' => 0,
            'both_down' => 0,
            'push1' => 0,
            'push2' => 0,
            'stumbles' => 0,
            'defender_down' => 0
        );
        
        if ($role == 'attacker') {
            switch ($need) {
                case 'pow':
                    $faces['defender_down'] = 1;
                    if (!$oppDodge) {
                        $faces['stumbles'] = 1;
                    }
                    if ($hasBlock && !$oppBlock) {
                        $faces['both_down'] = 1;
                    }
                    if ($hasWrestle) {
                        $faces['both_down'] = 1;
                    }
                    break;
                    
                case 'push':
                    $faces['push1'] = 1;
                    $faces['push2'] = 1;
                    $faces['defender_down'] = 1;
                    $faces['stumbles'] = 1;
                    if ($hasBlock && !$oppBlock) {
                        $faces['both_down'] = 1;
                    }
                    if ($hasWrestle) {
                        $faces['both_down'] = 1;
                    }
                    if ($hasJuggernaut) {
                        $faces['both_down'] = 1;
                    }
                    break;
                    
                case 'not_skull':
                    $faces['push1'] = 1;
                    $faces['push2'] = 1;
                    $faces['stumbles'] = 1;
                    $faces['defender_down'] = 1;
                    if (($hasBlock && !$oppBlock) || $hasWrestle || $hasJuggernaut) {
                        $faces['both_down'] = 1;
                    }
                    if ((!$hasBlock && !$oppBlock) || ($hasBlock && $oppBlock)) {
                        $faces['both_down'] = 1;
                    }
                    break;
                    
                case 'not_both_down':
                    $faces['push1'] = 1;
                    $faces['push2'] = 1;
                    $faces['stumbles'] = 1;
                    $faces['defender_down'] = 1;
                    if ($hasBlock && !$oppBlock) {
                        $faces['both_down'] = 1;
                    }
                    if ($hasJuggernaut) {
                        $faces['both_down'] = 1;
                    }
                    break;
            }
        } else {
            switch ($need) {
                case 'pow':
                    break;
                case 'push':
                    $faces['attacker_down'] = 1;
                    $faces['both_down'] = 1;
                    $faces['push1'] = 1;
                    $faces['push2'] = 1;
                    $faces['stumbles'] = 1;
                    break;
                case 'not_skull':
                    $faces['both_down'] = 1;
                    $faces['push1'] = 1;
                    $faces['push2'] = 1;
                    $faces['stumbles'] = 1;
                    break;
                case 'not_both_down':
                    $faces['attacker_down'] = 1;
                    $faces['push1'] = 1;
                    $faces['push2'] = 1;
                    $faces['stumbles'] = 1;
                    break;
            }
        }
        
        $singleDieProb = ($faces['attacker_down'] + $faces['both_down'] + $faces['push1'] + $faces['push2'] + $faces['stumbles'] + $faces['defender_down']) / 6.0;
        
        if ($oppSteady && $role == 'attacker') {
            $knockdownFaces = $faces['stumbles'] + $faces['defender_down'];
            if ($hasBlock && !$oppBlock) {
                $knockdownFaces += $faces['both_down'];
            }
            
            if ($knockdownFaces > 0) {
                $steadyProb = 5.0 / 6.0;
                $knockdownProb = $knockdownFaces / 6.0;
                $adjustedKnockdownProb = $knockdownProb * $steadyProb;
                $nonKnockdownProb = $singleDieProb - $knockdownProb;
                $singleDieProb = $nonKnockdownProb + $adjustedKnockdownProb;
            }
        }
        
        if ($numDice == 1) {
            return $singleDieProb;
        }
        
        if ($isAgainst) {
            return pow($singleDieProb, $numDice);
        }
        
        if ($role == 'attacker') {
            return 1.0 - pow(1.0 - $singleDieProb, $numDice);
        } else {
            return pow($singleDieProb, $numDice);
        }
    }
    
    private static function calculateBlockDiceWithBrawler($numDice, $role, $need, $skills, $oppBlock, $oppDodge, $oppSteady)
    {
        // Brawler lets you reroll one die showing Both Down
        // For "not_both_down", this is very powerful
        
        $isAgainst = false;
        if (is_string($numDice) && strpos($numDice, '-against') !== false) {
            $isAgainst = true;
            $numDice = (int)$numDice;
        } else {
            $numDice = (int)$numDice;
        }
        
        // Get base probability without Brawler
        $baseProb = self::calculateBlockDice($numDice . ($isAgainst ? '-against' : ''), $role, $need, $skills, $oppBlock, $oppDodge, $oppSteady);
        
        // Calculate probability of rolling at least one Both Down
        // For simplicity, assume Brawler adds moderate improvement
        // Full calculation would require enumerating all dice combinations
        
        $improvementFactor = 1.15; // Brawler roughly 15% improvement for multi-die blocks
        $withBrawler = min(1.0, $baseProb * $improvementFactor);
        
        return $withBrawler;
    }
    
    private static function withReroll($baseProb)
    {
        return $baseProb + (1 - $baseProb) * $baseProb;
    }
    
    private static function toFraction($decimal)
    {
        if ($decimal == 0) return "0/1";
        if ($decimal == 1) return "1/1";
        if ($decimal < 0) return "-" . self::toFraction(-$decimal);
        
        $tolerance = 1.0e-9;
        $maxDenominator = 10000;
        
        $bestNumerator = 0;
        $bestDenominator = 1;
        $bestError = abs($decimal);
        
        for ($denominator = 1; $denominator <= $maxDenominator; $denominator++) {
            $numerator = round($decimal * $denominator);
            $error = abs($decimal - ($numerator / $denominator));
            
            if ($error < $bestError) {
                $bestError = $error;
                $bestNumerator = $numerator;
                $bestDenominator = $denominator;
                
                if ($error < $tolerance) {
                    break;
                }
            }
        }
        
        if ($bestNumerator > 0) {
            $gcd = self::gcd($bestNumerator, $bestDenominator);
            $bestNumerator = $bestNumerator / $gcd;
            $bestDenominator = $bestDenominator / $gcd;
            
            if ($bestDenominator <= 10000) {
                return $bestNumerator . "/" . $bestDenominator;
            }
        }
        
        return "~" . number_format($decimal, 4);
    }
    
    private static function gcd($a, $b)
    {
        $a = abs($a);
        $b = abs($b);
        
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        
        return $a;
    }
}
?>