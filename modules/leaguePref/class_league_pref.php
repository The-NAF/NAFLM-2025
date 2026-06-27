<?php
/*
 *  Copyright (c) Ian Williams <email is protected> 2011. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class LeaguePref implements ModuleInterface
{

/***************
 * ModuleInterface requirements. These functions MUST be defined.
 ***************/

public static function main($argv)
{
    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

public static function getModuleAttributes()
{
    return array(
        'author'     => 'DoubleSkulls',
        'moduleName' => 'LeaguePref',
        'date'       => '2011',
        'setCanvas'  => true,
    );
}

public static function getModuleTables()
{
    global $CT_cols;

	return array(
        'league_prefs' => array(
			'f_lid'       => $CT_cols[T_NODE_LEAGUE].' NOT NULL PRIMARY KEY ',
	        'prime_tid'   => $CT_cols[T_NODE_TOURNAMENT],
	        'second_tid'  => $CT_cols[T_NODE_TOURNAMENT],
	        'league_name' => 'VARCHAR(128) ',
	        'forum_url'   => 'VARCHAR(256) ',
	        'welcome'     => 'TEXT ',
	        'rules'       => 'TEXT ',
        ),
    );
}

public static function getModuleUpgradeSQL()
{
    global $CT_cols;
    return array(
        '096-097' => array(
            'CREATE TABLE IF NOT EXISTS league_prefs
            (
                f_lid       ' . $CT_cols[T_NODE_LEAGUE] . ' NOT NULL PRIMARY KEY,
                prime_tid   ' . $CT_cols[T_NODE_TOURNAMENT] . ',
                second_tid  ' . $CT_cols[T_NODE_DIVISION] . ',
                league_name VARCHAR(128),
                forum_url   VARCHAR(256),
                welcome     TEXT,
                rules       TEXT
            )'            
        ),
    );
}

public static function triggerHandler($type, $argv){
    global $settings;
    
    switch ($type) {
        case ( $type == T_TRIGGER_BEFORE_PAGE_RENDER ):
            if(isset($_POST['core_theme_id']))
                $settings['stylesheet'] = $_POST['core_theme_id'];
        
            break;
    }
}

/***************
 * Properties
 ***************/
public $lid      = 0;
public $l_name    = '';
public $p_tour   = 0;
public $s_tour = 0;
public $league_name = '';
public $forum_url = '';
public $welcome = '';
public $rules = '';
public $existing = false;
public $theme_css = '';
public $core_theme_id = 0;
public $tv = 0;
public $language = 'en-GB';
public $helf = 0;
public $slann = 0;
public $randomskillrolls = 0;
public $randomstatrolls = 0;
public $randomskillmanualentry = 0;
public $megastars = 0;
public $base_inducements = 0;
public $fireunder11 = 0;
public $enforce_hire_fire_order = 0;
public $major_win_tds = 0;
public $major_win_pts = 0;
public $clean_sheet_pts = 0;
public $major_beat_cas = 0;
public $major_beat_pts = 0;
public $prayer_cost = 0;
public $banned_stars = '';
public $megastar_tax = 0;
public $min_tv = 0;
public $discord_webhook_url = '';
public $discord_post_permission = 'admins';

function __construct($lid, $name, $ptid, $stid, $league_name, $forum_url, $welcome, $rules, $existing, $theme_css, $core_theme_id, $tv, $language, $helf, $slann, 
$randomskillrolls, $randomstatrolls, $randomskillmanualentry, $megastars, $base_inducements, $fireunder11, $enforce_hire_fire_order, $major_win_tds, $major_win_pts, $clean_sheet_pts, $major_beat_cas, $major_beat_pts, 
$prayer_cost, $banned_stars, $megastar_tax, $min_tv, $discord_webhook_url, $discord_post_permission) {
	global $settings;
	$this->lid = $lid;
	$this->l_name = $name;
	$this->p_tour = $ptid;
	$this->s_tour = $stid;
	$this->league_name = isset($league_name) ? $league_name: $settings['league_name'];
	$this->forum_url = isset($forum_url) ? $forum_url: $settings['forum_url'];
	$this->welcome = isset($welcome) ? $welcome: $settings['welcome'];
	$this->rules = isset($rules) ? $rules: $settings['rules'];
	$this->existing = $existing;
    $this->theme_css = $theme_css;
    $this->core_theme_id = $core_theme_id;
    $this->tv = $tv;
    $this->language = $language;
    $this->helf = $helf;
    $this->slann = $slann;
    $this->randomskillrolls = $randomskillrolls;
    $this->randomstatrolls = $randomstatrolls;
    $this->randomskillmanualentry = $randomskillmanualentry;
    $this->megastars = $megastars;
    $this->base_inducements = $base_inducements;
    $this->fireunder11 = $fireunder11;
	$this->enforce_hire_fire_order  = $enforce_hire_fire_order;
    $this->major_win_tds = $major_win_tds;
    $this->major_win_pts = $major_win_pts;
    $this->clean_sheet_pts = $clean_sheet_pts;
    $this->major_beat_cas = $major_beat_cas;
    $this->major_beat_pts = $major_beat_pts;
    $this->prayer_cost = $prayer_cost;
    $this->banned_stars = $banned_stars;
    $this->megastar_tax = $megastar_tax;
    $this->min_tv = $min_tv;
	$this->discord_webhook_url = $discord_webhook_url;
    $this->discord_post_permission = $discord_post_permission;
}

/* Gets the preferences for the current league */
public static function getLeaguePreferences() {
	global $settings, $coach, $leagues, $rules;

    list($sel_lid, $HTML_LeagueSelector) = HTMLOUT::simpleLeagueSelector();
    echo $HTML_LeagueSelector;

	$result = mysql_query("SELECT lid, name, prime_tid, second_tid, league_name, forum_url, welcome, rules FROM leagues LEFT OUTER JOIN league_prefs on lid=f_lid WHERE lid=$sel_lid");

    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $theme_css = FileManager::readFile(FileManager::getCssDirectoryName() . "/league_override_$sel_lid.css"); 
            
            return new LeaguePref($row['lid'], $row['name'],
                $row['prime_tid'], $row['second_tid'], $row['league_name'], $row['forum_url'],
                $row['welcome'], $row['rules'], true, $theme_css, 
                $settings['stylesheet'], $rules['initial_treasury'], 
				$settings['lang'],
				$rules['helf'],$rules['slann'],
				$rules['randomskillrolls'],$rules['randomstatrolls'],$rules['randomskillmanualentry'],
				$rules['megastars'],$rules['base_inducements'],$rules['fireunder11'],
				isset($rules['enforce_hire_fire_order']) ? $rules['enforce_hire_fire_order'] : 0,
				$rules['major_win_tds'],$rules['major_win_pts'],$rules['clean_sheet_pts'],
				$rules['major_beat_cas'],$rules['major_beat_pts'],
				isset($rules['prayer_cost']) ? $rules['prayer_cost'] : 0,
				isset($rules['banned_stars']) ? $rules['banned_stars'] : '',
				isset($rules['megastar_tax']) ? $rules['megastar_tax'] : 0,
				isset($rules['min_tv']) ? $rules['min_tv'] : 0,
				isset($rules['discord_webhook_url']) ? $rules['discord_webhook_url'] : '',
				isset($rules['discord_post_permission']) ? $rules['discord_post_permission'] : 'admins');
        }
    } else {
		return new LeaguePref($sel_lid, $leagues['lname'], null, null, null, null, null, null, false, null, 
            $settings['stylesheet'], $rules['initial_treasury'], 
			$settings['lang'],
			$rules['helf'],$rules['slann'],
			$rules['randomskillrolls'],$rules['randomstatrolls'],$rules['randomskillmanualentry'],
			$rules['megastars'],$rules['base_inducements'],$rules['fireunder11'], 0,
			$rules['major_win_tds'],$rules['major_win_pts'],$rules['clean_sheet_pts'],
			$rules['major_beat_cas'],$rules['major_beat_pts'],
			0, '', 0, 0, '', 'admins');
	}
}

function validate() {
	return $this->p_tour != $this->s_tour && $this->p_tour > 0;
}

/**
 * Syncs league-specific settings file with template, preserving custom values and comments
 * Only updates/inserts the specific rules that are managed by LeaguePref
 */
private function syncSettingsWithTemplate() {
    $templatePath = FileManager::getSettingsDirectoryName() . "/settings_new_league_template.php";
    $leagueSettingsPath = FileManager::getSettingsDirectoryName() . "/settings_$this->lid.php";
    
    // Read both files
    $templateContents = FileManager::readFile($templatePath);
    $leagueContents = FileManager::readFile($leagueSettingsPath);
    
    // Define which rules are managed by LeaguePref (the ones we have form fields for)
    // IMPORTANT: These must be in the same order as they appear in the template
    $managedRules = array(
        'initial_treasury',
        'helf',
        'slann',
        'randomskillrolls',
        'randomstatrolls',
        'randomskillmanualentry',
        'megastars',
        'base_inducements',
        'fireunder11',
        'enforce_hire_fire_order',
        'major_win_tds',
        'major_win_pts',
        'clean_sheet_pts',
        'major_beat_cas',
        'major_beat_pts',
        'prayer_cost',
        'banned_stars',
        'megastar_tax',
        'min_tv',
        'discord_webhook_url',
        'discord_post_permission'
    );
    
    // Process in reverse order so insertions don't mess up positions
    for ($i = count($managedRules) - 1; $i >= 0; $i--) {
        $ruleName = $managedRules[$i];
        $pattern = "/\\\$rules\['$ruleName'\]/";
        
        // If the rule doesn't exist in the league file, we need to add it from template
        if (!preg_match($pattern, $leagueContents)) {
            // Extract the line from template with optional single comment
            $extractPattern = "/(\/\/[^\n]*\n)?\\\$rules\['$ruleName'\][^;]+;/";
            if (preg_match($extractPattern, $templateContents, $matches)) {
                $templateLine = $matches[0];
                
                // Find where to insert by looking for the NEXT rule in the list that exists
                $inserted = false;
                for ($j = $i + 1; $j < count($managedRules); $j++) {
                    $nextRule = $managedRules[$j];
                    $nextPattern = "/((?:\/\/[^\n]*\n)?)\\\$rules\['$nextRule'\]/";
                    
                    if (preg_match($nextPattern, $leagueContents, $nextMatches, PREG_OFFSET_CAPTURE)) {
                        // Insert before this next rule (including its comment if present)
                        $insertPos = $nextMatches[1][1];
                        $leagueContents = substr_replace($leagueContents, $templateLine . "\n", $insertPos, 0);
                        $inserted = true;
                        break;
                    }
                }
                
                // If no next rule found, insert before initial_team_treasury (including its comment)
                if (!$inserted) {
                    $insertBeforePattern = "/((?:\/\/[^\n]*\n)?)\\\$rules\['initial_team_treasury'\]/";
                    if (preg_match($insertBeforePattern, $leagueContents, $insertMatches, PREG_OFFSET_CAPTURE)) {
                        $insertPos = $insertMatches[1][1];
                        $leagueContents = substr_replace($leagueContents, $templateLine . "\n", $insertPos, 0);
                    }
                }
            }
        }
    }
    
    return $leagueContents;
}

/**
 * Updates a single rule value in the settings content, preserving comments
 */
private function updateRule($contents, $ruleName, $value, $isString = false) {
    $valueFormatted = $isString ? "'$value'" : $value;
    
    // Match the rule assignment but preserve any inline comment
    $pattern = "/(\\\$rules\['$ruleName'\]\s*=\s*)[^;]+(;[^\n]*)/";
    $replacement = "\${1}$valueFormatted\${2}";
    
    return preg_replace($pattern, $replacement, $contents);
}

function save() {
    global $settings, $rules;
    
    $hasLeaguePref = mysql_fetch_object(mysql_query("SELECT f_lid from league_prefs where f_lid=$this->lid"));
    if($hasLeaguePref) {
        $query = "UPDATE league_prefs SET prime_tid=$this->p_tour,second_tid=$this->s_tour, league_name='".mysql_real_escape_string($this->league_name)."', forum_url='".mysql_real_escape_string($this->forum_url)."' , welcome='".mysql_real_escape_string($this->welcome)."' , rules='".mysql_real_escape_string($this->rules)."'  WHERE f_lid=$this->lid";
    } else {
        $query = "INSERT INTO league_prefs (f_lid, prime_tid, second_tid, league_name, forum_url, welcome, rules) VALUE ($this->lid, $this->p_tour, $this->s_tour, '".mysql_real_escape_string($this->league_name)."', '".mysql_real_escape_string($this->forum_url)."', '".mysql_real_escape_string($this->welcome)."', '".mysql_real_escape_string($this->rules)."')";
    }
    
    // Save CSS
    $savedcss = preg_replace('/<p>/', '', $this->theme_css);
    $savedcssfinal = preg_replace('/<\/p>/', '', $savedcss);
    FileManager::writeFile(FileManager::getCssDirectoryName() . "/league_override_$this->lid.css", $savedcssfinal);
    
    // Sync settings file with template (adds any missing rules)
    $settingsFileContents = $this->syncSettingsWithTemplate();
    
    // Update stylesheet and language (these use different patterns)
    $settingsFileContents = preg_replace("/settings\['stylesheet'\]\s*=\s*[^;]+;/", "settings['stylesheet'] = $this->core_theme_id;", $settingsFileContents);
    $settingsFileContents = preg_replace("/settings\['lang'\]\s*=\s*[^;]+;/", "settings['lang'] = '$this->language';", $settingsFileContents);
    
    // Update all the rules using the helper method
    $settingsFileContents = $this->updateRule($settingsFileContents, 'initial_treasury', $this->tv);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'helf', $this->helf == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'slann', $this->slann == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'randomskillrolls', $this->randomskillrolls == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'randomstatrolls', $this->randomstatrolls == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'randomskillmanualentry', $this->randomskillmanualentry == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'megastars', $this->megastars == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'base_inducements', $this->base_inducements == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'fireunder11', $this->fireunder11 == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'enforce_hire_fire_order', $this->enforce_hire_fire_order == 1 ? 1 : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'major_win_tds', $this->major_win_tds > 0 ? $this->major_win_tds : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'major_win_pts', $this->major_win_pts > 0 ? $this->major_win_pts : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'clean_sheet_pts', $this->clean_sheet_pts > 0 ? $this->clean_sheet_pts : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'major_beat_cas', $this->major_beat_cas > 0 ? $this->major_beat_cas : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'major_beat_pts', $this->major_beat_pts > 0 ? $this->major_beat_pts : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'prayer_cost', $this->prayer_cost > 0 ? $this->prayer_cost : 0);
    $banned_stars_clean = mysql_real_escape_string(trim($this->banned_stars));
    $settingsFileContents = $this->updateRule($settingsFileContents, 'banned_stars', $banned_stars_clean, true);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'megastar_tax', $this->megastar_tax > 0 ? $this->megastar_tax : 0);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'min_tv', $this->min_tv > 0 ? $this->min_tv : 0);
	$settingsFileContents = $this->updateRule($settingsFileContents, 'discord_webhook_url', mysql_real_escape_string(trim($this->discord_webhook_url)), true);
    $settingsFileContents = $this->updateRule($settingsFileContents, 'discord_post_permission', mysql_real_escape_string(trim($this->discord_post_permission)), true);
    
    // Write the file
    FileManager::writeFile(FileManager::getSettingsDirectoryName() . "/settings_$this->lid.php", $settingsFileContents);
    
    // Update global settings/rules arrays
    $settings['stylesheet'] = $this->core_theme_id;
    $settings['lang'] = $this->language;
    $rules['initial_treasury'] = $this->tv;
    $rules['helf'] = $this->helf;
    $rules['slann'] = $this->slann;
    $rules['randomskillrolls'] = $this->randomskillrolls;
    $rules['randomstatrolls'] = $this->randomstatrolls;
    $rules['randomskillmanualentry'] = $this->randomskillmanualentry;
    $rules['megastars'] = $this->megastars;
    $rules['base_inducements'] = $this->base_inducements;
    $rules['fireunder11'] = $this->fireunder11;
    $rules['enforce_hire_fire_order'] = $this->enforce_hire_fire_order;
    $rules['major_win_tds'] = $this->major_win_tds;
    $rules['major_win_pts'] = $this->major_win_pts;
    $rules['clean_sheet_pts'] = $this->clean_sheet_pts;
    $rules['major_beat_cas'] = $this->major_beat_cas;
    $rules['major_beat_pts'] = $this->major_beat_pts;
    $rules['prayer_cost'] = $this->prayer_cost;
    $rules['banned_stars'] = $this->banned_stars;
    $rules['megastar_tax'] = $this->megastar_tax;
    $rules['min_tv'] = $this->min_tv;
	$rules['discord_webhook_url'] = $this->discord_webhook_url;
    $rules['discord_post_permission'] = $this->discord_post_permission;
            
    return mysql_query($query);
}

public static function showLeaguePreferences() {
    global $lng, $tours, $coach, $leagues, $settings, $rules, $stars;
    title($lng->getTrn('name', 'LeaguePref'));

    // Show success message if redirected here after a save
    if (isset($_GET['saved']) && $_GET['saved'] == 1) {
        echo "<div class='boxWide'>";
        HTMLOUT::helpBox($lng->getTrn('saved', 'LeaguePref'), '');
        echo "</div>";
    }

	self::handleActions();

	// short cuts to text lookups
	$prime_title = $lng->getTrn('prime_title', 'LeaguePref');
	$prime_help = $lng->getTrn('prime_help', 'LeaguePref');
	
	$second_title = $lng->getTrn('second_title', 'LeaguePref');
	$second_help = $lng->getTrn('second_help', 'LeaguePref');

	$league_name_title = $lng->getTrn('league_name_title', 'LeaguePref');
	$league_name_help = $lng->getTrn('league_name_help', 'LeaguePref');

	$forum_url_title = $lng->getTrn('forum_url_title', 'LeaguePref');
	$forum_url_help = $lng->getTrn('forum_url_help', 'LeaguePref');

	$welcome_title = $lng->getTrn('welcome_title', 'LeaguePref');
	$welcome_help = $lng->getTrn('welcome_help', 'LeaguePref');

	$rules_title = $lng->getTrn('rules_title', 'LeaguePref');
	$rules_help = $lng->getTrn('rules_help', 'LeaguePref');

	$submit_text = $lng->getTrn('submit_text', 'LeaguePref');
	$submit_title = $lng->getTrn('submit_title', 'LeaguePref');

	// Get the selected league ID first
	list($sel_lid, $HTML_LeagueSelector_temp) = HTMLOUT::simpleLeagueSelector();

	// Filter tournaments to only those belonging to the selected league
	$leagueTours = array();
	$tourQuery = mysql_query("SELECT t.tour_id, t.name FROM tours t 
							  INNER JOIN divisions d ON t.f_did = d.did 
							  WHERE d.f_lid = $sel_lid");
							  
	if ($tourQuery && mysql_num_rows($tourQuery) > 0) {
		while ($row = mysql_fetch_assoc($tourQuery)) {
			$leagueTours[$row['tour_id']] = array('tname' => $row['name']);
		}
	}
	$rTours = array_reverse($leagueTours, true);

	// Now get the league preferences (this will echo the selector again)
	$l_pref = self::getLeaguePreferences();
	
	// check this coach is allowed to administer this league
	$canEdit = is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $l_pref->lid) ? "" : "DISABLED";
    ?>
	<div class='boxWide'>
		<h3 class='boxTitle4'><?php echo $l_pref->l_name; ?></h3>
		<div class='boxConf'>
            <form method="POST">
                <input type="hidden" name="lid" value="<?php echo $l_pref->lid; ?>" />
                <input type="hidden" name="existing" value="<?php echo $l_pref->existing; ?>" />
                <div class='tableResponsive'>
                <table width="100%" border="0">
                    <tr title="<?php echo $league_name_help; ?>">
                        <td>
                            <?php echo $league_name_title; ?>:
                        </td>
                        <td>
                            <input type="text" size="118" maxsize="128" name="league_name" <?php echo $canEdit; ?> value="<?php echo $l_pref->league_name; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('tv_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('tv_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <input type="number" min="0" step="5000" name="tv" <?php echo $canEdit; ?> value="<?php echo $l_pref->tv; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('min_tv_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('min_tv_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <input type="number" min="0" step="5000" name="min_tv" <?php echo $canEdit; ?> value="<?php echo $l_pref->min_tv; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('language_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('language_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <select name="language" <?php echo $canEdit; ?>>
                                <option value="en-GB" <? echo 'en-GB' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/english'); ?></option>
                                <option value="es-ES" <? echo 'es-ES' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/spanish'); ?></option>
                                <option value="de-DE" <? echo 'de-DE' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/german'); ?></option>
                                <option value="fr-FR" <? echo 'fr-FR' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/french'); ?></option>
                                <option value="it-IT" <? echo 'it-IT' == $settings['lang'] ? 'selected' : '' ?>><?php echo $lng->getTrn('common/italian'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr title="<?php echo $forum_url_help; ?>">
                        <td>
                            <?php echo $forum_url_title; ?>:
                        </td>
                        <td>
                            <input type="text" size="118" maxsize="256" name="forum_url" <?php echo $canEdit; ?> value="<?php echo $l_pref->forum_url; ?>" />
                        </td>
                    </tr>
                    <tr title="<?php echo $welcome_help; ?>">
                        <td>
                            <?php echo $welcome_title; ?>:
                        </td>
                        <td>
                            <textarea rows="4" cols="90" class="html_edit" name="welcome" <?php echo $canEdit; ?>><?php echo $l_pref->welcome; ?></textarea>
                        </td>
                    </tr>
                    <tr title="<?php echo $rules_help; ?>">
                        <td>
                            <?php echo $rules_title; ?>:
                        </td>
                        <td>
                            <textarea rows="4" cols="90" class="html_edit" name="rules" <?php echo $canEdit; ?>><?php echo $l_pref->rules; ?></textarea>
                        </td>
                    </tr>
                    <tr title="<?php echo $prime_help; ?>">
                        <td>
                            <?php echo $prime_title; ?>:
                        </td>
                        <td>
                            <select name="p_tour">
                                <?php
                                    foreach ($rTours as $trid => $desc) {
                                        echo "<option value='$trid'" . ($trid==$l_pref->p_tour ? 'SELECTED' : '') . " " . $canEdit . ">" . $desc['tname'] . "</option>\n";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
					<tr title="<?php echo $second_help; ?>">
                        <td>
                            <?php echo $second_title; ?>:
                        </td>
                        <td>
                            <select name="s_tour">
								<option value="0">--Select--</option>
                                <?php
                                    foreach ($rTours as $trid => $desc) {
                                        echo "<option value='$trid'" . ($trid==$l_pref->s_tour ? 'SELECTED' : '') . " " . $canEdit . ">" . $desc['tname'] . "</option>\n";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('core_theme_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('core_theme_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <select name="core_theme_id">
                            <?php
                                $stylesheetLength = strlen('stylesheet');
                                foreach(FileManager::getAllCoreCssSheetFileNames() as $cssFileName) {
                                    $extensionIndex = strrpos($cssFileName, '.');
                                    $fileStartIndex = strrpos($cssFileName, 'stylesheet');
                                    $idLength = $extensionIndex - $fileStartIndex -$stylesheetLength;
                                    $cssId = substr($cssFileName, $fileStartIndex + $stylesheetLength, $idLength);
                                    
                                    // '_default' isn't a valid option, it's the default.
                                    if($cssId != '_default') {
                                        $coreThemeName = isset($settings['core_theme_names'][$cssId]) ? $settings['core_theme_names'][$cssId] : $cssId;
                                        echo '<option value="' . $cssId . '"' . ($cssId == $settings['stylesheet'] ? 'SELECTED' : '') . '>' . $coreThemeName . '</option>';
                                    }
                                }
                            ?>
                            </select>
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('css_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('css_title', 'LeaguePref'); ?>
                        </td>
                        <td>
                            <textarea rows="10" cols="120" name="theme_css" <?php echo $canEdit; ?>><?php echo $l_pref->theme_css; ?></textarea>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('teams_legend_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('teams_legend_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='helf' value='1' onclick='slideToggleFast("helf");'	<?php if($rules['helf'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_helf', 'LeaguePref'); ?></b>
							<br>
							<input type='checkbox' name='slann' value='1' onclick='slideToggleFast("slann");'	<?php if($rules['slann'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('teams_legend_slann', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('fireunder11_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('fireunder11_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='fireunder11' value='1' onclick='slideToggleFast("fireunder11");'	<?php if($rules['fireunder11'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('fireunder11', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('enforce_hire_fire_order_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('enforce_hire_fire_order_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='enforce_hire_fire_order' value='1' onclick='slideToggleFast("enforce_hire_fire_order");'	<?php if($rules['enforce_hire_fire_order'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('enforce_hire_fire_order', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('randomskillrolls_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('randomskillrolls_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='randomskillrolls' value='1' onclick='slideToggleFast("randomskillrolls");'	<?php if($rules['randomskillrolls'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('randomskillrolls', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('randomstatrolls_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('randomstatrolls_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='randomstatrolls' value='1' onclick='slideToggleFast("randomstatrolls");'	<?php if($rules['randomstatrolls'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('randomstatrolls', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('randomskillmanualentry_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('randomskillmanualentry_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='randomskillmanualentry' value='1' onclick='slideToggleFast("randomskillmanualentry");'	<?php if($rules['randomskillmanualentry'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('randomskillmanualentry', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr> 
                    <tr title="<?php echo $lng->getTrn('bonuspoints_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('bonuspoints_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type="number" min="0" max="10" name="major_win_tds" <?php echo $canEdit; ?> value="<?php echo $rules['major_win_tds'] ?>" />
							<b><?php echo $lng->getTrn('major_win_tds', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="major_win_pts" <?php echo $canEdit; ?> value="<?php echo $rules['major_win_pts'] ?>" />
							<b><?php echo $lng->getTrn('major_win_pts', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="clean_sheet_pts" <?php echo $canEdit; ?> value="<?php echo $rules['clean_sheet_pts'] ?>" />
							<b><?php echo $lng->getTrn('clean_sheet_pts', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="major_beat_cas" <?php echo $canEdit; ?> value="<?php echo $rules['major_beat_cas'] ?>" />
							<b><?php echo $lng->getTrn('major_beat_cas', 'LeaguePref'); ?></b><br>
							<input type="number" min="0" max="10" name="major_beat_pts" <?php echo $canEdit; ?> value="<?php echo $rules['major_beat_pts'] ?>" />
							<b><?php echo $lng->getTrn('major_beat_pts', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('base_inducements_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('base_inducements_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='base_inducements' value='1' onclick='slideToggleFast("base_inducements");'	<?php if($rules['base_inducements'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('base_inducements', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('prayer_cost_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('prayer_cost_title', 'LeaguePref'); ?>
                        </td>
                        <td>
                            <input type="number" min="0" step="5000" name="prayer_cost" <?php echo $canEdit; ?> value="<?php echo $l_pref->prayer_cost; ?>" />
                            <small><?php echo $lng->getTrn('prayer_cost_note', 'LeaguePref'); ?></small>
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('megastars_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('megastars_title', 'LeaguePref'); ?>
                        </td>
                        <td>     
							<input type='checkbox' name='megastars' value='1' onclick='slideToggleFast("megastars");'	<?php if($rules['megastars'] == 1) {echo 'checked';}?>>
                            <b><?php echo $lng->getTrn('megastars', 'LeaguePref'); ?></b>
                        </td>                        
                    </tr>
                    <tr title="<?php echo $lng->getTrn('megastar_tax_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('megastar_tax_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <input type="number" min="0" step="5000" name="megastar_tax" <?php echo $canEdit; ?> value="<?php echo $l_pref->megastar_tax; ?>" />
                            <small><?php echo $lng->getTrn('megastar_tax_note', 'LeaguePref'); ?></small>
                        </td>
                    </tr>
                    <tr title="<?php echo $lng->getTrn('banned_stars_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('banned_stars_title', 'LeaguePref'); ?>:
                        </td>
                        <td>
                            <?php
                            $banned_array = !empty($l_pref->banned_stars) ? explode(',', $l_pref->banned_stars) : array();
                            ?>
                            <select name="banned_stars[]" multiple size="8" style="width: 400px;" <?php echo $canEdit; ?>>
                            <?php
                            foreach ($stars as $star_name => $star_data) {
                                $star_id = $star_data['id'];
                                $selected = in_array($star_id, $banned_array) ? 'selected' : '';
                                echo '<option value="' . $star_id . '" ' . $selected . '>' . htmlspecialchars($star_name) . '</option>';
                            }
                            ?>
                            </select>
                            <br><small><?php echo $lng->getTrn('banned_stars_note', 'LeaguePref'); ?></small>
                        </td>
                    </tr>
					<tr title="<?php echo $lng->getTrn('discord_url_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('discord_url_title', 'LeaguePref'); ?>:
                        </td>
						<td>
							<input type="text" size="80" maxlength="512"
								   name="discord_webhook_url" <?php echo $canEdit; ?>
								   value="<?php echo htmlspecialchars($l_pref->discord_webhook_url); ?>" />
							<small><?php echo $lng->getTrn('discord_url_note', 'LeaguePref'); ?></small>
						</td>
					</tr>
					<tr title="<?php echo $lng->getTrn('discord_perms_help', 'LeaguePref'); ?>">
                        <td>
                            <?php echo $lng->getTrn('discord_perms_title', 'LeaguePref'); ?>:
                        </td>
						<td>
							<label>
								<input type="radio" name="discord_post_permission" value="admins"
									<?php echo ($l_pref->discord_post_permission != 'coaches')
										? 'checked' : ''; ?>
									<?php echo $canEdit; ?>>
								<?php echo $lng->getTrn('discord_perms_admins', 'LeaguePref'); ?>:
							</label>
							&nbsp;&nbsp;
							<label>
								<input type="radio" name="discord_post_permission" value="coaches"
									<?php echo ($l_pref->discord_post_permission == 'coaches')
										? 'checked' : ''; ?>
									<?php echo $canEdit; ?>>
								<?php echo $lng->getTrn('discord_perms_coaches', 'LeaguePref'); ?>:
							</label>
						</td>
					</tr>

                    <tr title="<?php echo $submit_title; ?>">
                        <td colspan="2">
                            <br><input type="submit" name="action" <?php echo $canEdit; ?> value="<?php echo $submit_text; ?>" style="position:relative; right:-200px;">
                        </td>
                    </tr>
                </table>
                </div>
            </form>
		</div>
	</div>
    <div class='boxWide'>
        <?php HTMLOUT::helpBox($lng->getTrn('help', 'LeaguePref'), ''); ?>
	</div>
    <?php
}

public static function handleActions() {
    global $lng, $coach;
    
    if (isset($_POST['action'])) {
    	if (is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $_POST['lid'])) {
    	    // Handle banned stars multi-select
    	    $banned_stars = '';
    	    if (isset($_POST['banned_stars']) && is_array($_POST['banned_stars'])) {
    	        $banned_stars = implode(',', $_POST['banned_stars']);
    	    }
    	    
			$l_pref = new LeaguePref($_POST['lid'], "", $_POST['p_tour'], $_POST['s_tour'],
                $_POST['league_name'], $_POST['forum_url'], $_POST['welcome'], 
                $_POST['rules'], $_POST['existing'], $_POST['theme_css'], 
                $_POST['core_theme_id'], $_POST['tv'], 
				$_POST['language'],
				isset($_POST['helf']) ? $_POST['helf'] : 0,
				isset($_POST['slann']) ? $_POST['slann'] : 0,
				isset($_POST['randomskillrolls']) ? $_POST['randomskillrolls'] : 0,
				isset($_POST['randomstatrolls']) ? $_POST['randomstatrolls'] : 0,
				isset($_POST['randomskillmanualentry']) ? $_POST['randomskillmanualentry'] : 0,
				isset($_POST['megastars']) ? $_POST['megastars'] : 0,
				isset($_POST['base_inducements']) ? $_POST['base_inducements'] : 0,
				isset($_POST['fireunder11']) ? $_POST['fireunder11'] : 0,
				isset($_POST['enforce_hire_fire_order']) ? $_POST['enforce_hire_fire_order'] : 0,
				$_POST['major_win_tds'],$_POST['major_win_pts'],$_POST['clean_sheet_pts'],
				$_POST['major_beat_cas'],$_POST['major_beat_pts'],
				$_POST['prayer_cost'], $banned_stars, $_POST['megastar_tax'], $_POST['min_tv'],
				isset($_POST['discord_webhook_url']) ? $_POST['discord_webhook_url'] : '',
				isset($_POST['discord_post_permission']) ? $_POST['discord_post_permission'] : 'admins');
			if($l_pref->validate()) {
				if($l_pref->save()) {
					// Redirect back to the same page so the form re-renders with fresh data from the saved file.
					// The ?saved=1 parameter triggers the success message after the redirect.
					$lid = intval($_POST['lid']);
					$redirectUrl = "handler.php?type=leaguepref&lid=$lid&saved=1";
					echo "<script>window.location.replace('" . $redirectUrl . "');</script>";
					return;
				} else {
					echo "<div class='boxWide'>";
					HTMLOUT::helpBox($lng->getTrn('failedSave', 'LeaguePref'), '', 'errorBox');
					echo "</div>";
				}
			} else {
				echo "<div class='boxWide'>";
				// Provide more specific error message
				if ($l_pref->p_tour <= 0) {
					HTMLOUT::helpBox('You must select a Primary Tournament before saving. If one does not appear as selectable you must first create a tournament.', '', 'errorBox');
				} else {
					HTMLOUT::helpBox($lng->getTrn('failedValidate', 'LeaguePref'), '', 'errorBox');
				}
				echo "</div>";
			}
		} else {
			echo "<div class='boxWide'>";
			HTMLOUT::helpBox($lng->getTrn('failedSecurity', 'LeaguePref'), '', 'errorBox');
			echo "</div>";
		}
    }
}
}
?>