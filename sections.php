<?php

/*************************
 *  Login
 *************************/
function getFormAction($params) {
    $mobilePrefix =(strpos($params, '?') !== FALSE) ? '&' : '?';    
    return 'index.php' . $params . (Mobile::isMobile() ? ($mobilePrefix . 'mobile=1') : '');
}
 
function sec_login() {
    global $lng, $settings;
    $_URL_forgotpass = "index.php?section=login&amp;forgotpass=1";
    if (isset($_GET['forgotpass'])) {
        if (!isset($_POST['_retry'])) {
            title($lng->getTrn('login/forgotpass'));
        }
        if (isset($_GET['cid']) && isset($_GET['activation_code'])) {
            $c = new Coach((int) $_GET['cid']);
            status($new_passwd = $c->confirmActivation($_GET['activation_code']));
            echo "<br><br>";
            echo $lng->getTrn('login/temppasswd')." <b>$new_passwd</b><br>\n";
            echo '<a href="'.urlcompile(T_URL_PROFILE,T_OBJ_COACH,$_GET['cid'],false,false).'&amp;subsec=profile">'.$lng->getTrn('login/setnewpasswd').'.</a>';
        }
        else if (isset($_POST['coach_AC']) && isset($_POST['email'])) {
            $cid = get_alt_col('coaches', 'name', $_POST['coach_AC'], 'coach_id');
            $c = new Coach($cid);
            $correct_user = ($c->mail == $_POST['email']);
            status($correct_user, $correct_user ? '' : $lng->getTrn('login/mismatch'));
            if ($correct_user) {
                $c->requestPasswdReset();
                echo "<br><br>";
                echo $lng->getTrn('login/resetpasswdmail').'.';
            } else {
                // Return to same page.
                unset($_POST['coach']);
                unset($_POST['email']);
                $_POST['_retry'] = true;
                sec_login();
            }
        } else {
            ?>
            <div class='boxCommon'>
                <h3 class='boxTitle<?php echo T_HTMLBOX_COACH;?>'><?php echo $lng->getTrn('login/forgotpass');?></h3>
                <div class='boxBody'>
                <form method="POST" action="<?php echo $_URL_forgotpass;?>">
                    <?php echo $lng->getTrn('login/loginname');?><br>
                    <input type="text" name="coach_AC" size="20" maxlength="50">
                    <br><br>
                    Email<br>
                    <input type="text" name="email" size="20" maxlength="50">
                    <br><br>
                    <input type="submit" name="reqAC" value="<?php echo $lng->getTrn('common/submit');?>">
                </form>
                </div>
            </div>
            <?php 
        }       
    } else {
        title($lng->getTrn('menu/login'));
        ?>
        <script lang="text/javascript">
            $(document).ready(function() {
                $('#coach').focus();
            });
        </script>
        <div class='boxCommon'>
            <h3 class='boxTitle<?php echo T_HTMLBOX_COACH;?>'><?php echo $lng->getTrn('menu/login');?></h3>
            <div class='boxBody'>
            <form method="POST" action="<?php echo getFormAction(''); ?>">
                <?php echo $lng->getTrn('login/loginname');?><br>
                <input type="text" id="coach" name="coach" size="20" maxlength="50"><br><br>
                <?php echo $lng->getTrn('login/passwd');?><br>
                <input type="password" name="passwd" size="20" maxlength="50">
                <div style='display: none;'><input type='text' name='hackForHittingEnterToLogin' size='1'></div>
                <br><br>
                <?php echo $lng->getTrn('login/remember');?>
                <input type='checkbox' name='remember' value='1'>
                <br><br>
                <input type="submit" name="login" value="<?php echo $lng->getTrn('login/loginbutton');?>">
            </form>
            <br><br>
            <?php
            if(!Mobile::isMobile()) {
                if (Module::isRegistered('Registration') && $settings['allow_registration']) {
                    echo "<a href='handler.php?type=registration'><b>Register</b></a>";
                }  
                echo "<br><br>";
                echo "<a href='$_URL_forgotpass'><b>".$lng->getTrn('login/forgotpass').'</b></a>';
            }
            ?>
            </div>
        </div>
        <?php
    }
}

/*************************
 *  Main
 *************************/
function sec_main() {
    global $settings, $rules, $coach, $lng, $leagues;
    MTS('Main start');
    list($sel_lid, $HTML_LeagueSelector) = HTMLOUT::simpleLeagueSelector();
    $IS_GLOBAL_ADMIN = (is_object($coach) && $coach->ring == Coach::T_RING_GLOBAL_ADMIN);
    
    /*
     *  Was any main board actions made?
     */

    if (isset($_POST['type']) && is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $sel_lid)) {
        if (get_magic_quotes_gpc()) {
            if (isset($_POST['title'])) $_POST['title'] = stripslashes($_POST['title']);
            if (isset($_POST['txt']))   $_POST['txt']   = stripslashes($_POST['txt']);
        }
        $msg = isset($_POST['msg_id']) ? new Message((int) $_POST['msg_id']) : null;
        switch ($_POST['type'])
        {
            case 'msgdel': status($msg->delete()); break;
            case 'msgnew':  
                status(Message::create(array(
                    'f_coach_id' => $coach->coach_id, 
                    'f_lid'      => ($IS_GLOBAL_ADMIN && isset($_POST['BC']) && $_POST['BC']) ? Message::T_BROADCAST : $sel_lid, 
                    'title'      => $_POST['title'], 
                    'msg'        => $_POST['txt'])
                )); break;
            case 'msgedit': status($msg->edit($_POST['title'], $_POST['txt'])); break;
            case 'pin':     status($msg->pin(1)); break;
            case 'unpin':   status($msg->pin(0)); break;

        }
    }

    /*
     *  Now we are ready to generate the HTML code.
     */

    ?>
    <div class="main_head"><?php echo $settings['league_name']; ?></div>
    <div class='main_leftColumn'>
        <div class="main_leftColumn_head">
            <?php
            echo "<div class='main_leftColumn_welcome'><br>\n";
            echo $settings['welcome'];
            echo "<br></div>\n";
            echo "<div class='main_leftColumn_left'><br>\n";
            if(count($leagues) > 1)
				echo $HTML_LeagueSelector;
            echo "</div>\n";
            echo "<div class='main_leftColumn_right'>\n";
            if (is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $sel_lid)) {
                echo "<a href='javascript:void(0);' onClick=\"slideToggle('msgnew');\">".$lng->getTrn('main/newmsg')."</a>&nbsp;\n";
            }
            if (Module::isRegistered('RSSfeed')) {echo "<a href='handler.php?type=rss'>RSS</a>\n";}
            echo "</div>\n";
            ?>
            <div style="display:none; clear:both;" id="msgnew">
                <br><br>
                <form method="POST">
                    <textarea name="title" rows="1" cols="50"><?php echo $lng->getTrn('common/notitle');?></textarea><br><br>
                    <textarea name="txt" rows="15" cols="50"><?php echo $lng->getTrn('common/nobody');?></textarea><br><br>
                    <?php 
                    if ($IS_GLOBAL_ADMIN) {
                        echo $lng->getTrn('main/broadcast');
                        ?><input type="checkbox" name="BC"><br><br><?php
                    }
                    ?>
                    <input type="hidden" name="type" value="msgnew">
                    <input type="submit" value="<?php echo $lng->getTrn('common/submit');?>">
                </form>
            </div>
        </div>

        <?php
        /*
            Generate main board.

            Left column is the message board, consisting of both commissioner messages and game summaries/results.
            To generate this table we create a general array holding the content of both.
        */
        $j = 1; $prevPinned = 0;
        foreach (TextSubSys::getMainBoardMessages($settings['fp_messageboard']['length'], $sel_lid, $settings['fp_messageboard']['show_team_news'], $settings['fp_messageboard']['show_match_summaries']) as $e) {

            if ($prevPinned == 1 && !$e->pinned) { echo "<hr>\n"; }
            $prevPinned = $e->pinned;

            echo "<div class='boxWide'>\n";
                echo "<h3 class='boxTitle$e->cssidx'>$e->title</h3>\n";
                echo "<div class='boxBody'>\n";
                    $fmtMsg = fmtprint($e->message); # Basic supported syntax: linebreaks.
                    echo "
                    <div id='e$j' class='expandable'>$fmtMsg</div>
                    <script type='text/javascript'>
                      $('#e$j').expander({
                        slicePoint:       300,
                        expandText:       '".$lng->getTrn('main/more')."',
                        collapseTimer:    0,
                        userCollapseText: ''
                      });
                      </script>";
                    echo "<br><hr>\n";
                    echo "<table class='boxTable'><tr>\n";
                        switch ($e->type) 
                        {
                            case T_TEXT_MATCH_SUMMARY:
                                echo "<td align='left' width='100%'>".$lng->getTrn('main/posted')." ".textdate($e->date)." " . ((isset($e->date_mod) && $e->date_mod != $e->date) ? "(".$lng->getTrn('main/lastedit')." ".textdate($e->date_mod).") " : '') .$lng->getTrn('main/by')." $e->author</td>\n";
                                echo "<td align='right'><a href='index.php?section=matches&amp;type=report&amp;mid=$e->match_id'>".$lng->getTrn('common/view')."</a></td>\n";
                                break;
                            case  T_TEXT_MSG:
                                echo "<td align='left' width='100%'>".$lng->getTrn('main/posted')." ".textdate($e->date)." ".$lng->getTrn('main/by')." $e->author</td>\n";
                                if (is_object($coach) && ($IS_GLOBAL_ADMIN || $coach->coach_id == $e->author_id)) { // Only admins may delete messages, or if it's a commissioner's own message.
                                    echo "<td align='right'><a href='javascript:void(0);' onClick=\"slideToggle('msgedit$e->msg_id');\">".$lng->getTrn('common/edit')."</a></td>\n";
                                    echo "<td align='right'>";
                                    $fieldname = 'pin'; if ($e->pinned) {$fieldname = 'unpin';}
                                    echo inlineform(array('type' => "$fieldname", 'msg_id' => $e->msg_id), "${fieldname}$e->msg_id", $lng->getTrn("main/$fieldname"));
                                    echo "</td>";
                                    echo "<td align='right'>";
                                    echo inlineform(array('type' => 'msgdel', 'msg_id' => $e->msg_id), "msgdel$e->msg_id", $lng->getTrn('common/delete'));
                                    echo "</td>";
                                }
                                break;
                            case T_TEXT_TNEWS:
                                echo "<td align='left' width='100%'>".$lng->getTrn('main/posted')." ".textdate($e->date)."</td>\n";
                                break;
                        }
                        ?>
                    </tr></table>
                    <?php
                    if ($e->type == T_TEXT_MSG) {
                        echo "<div style='display:none;' id='msgedit$e->msg_id'>\n";
                        echo "<hr><br>\n";
                        echo '<form method="POST">
                            <textarea name="title" rows="1" cols="50">'.$e->title.'</textarea><br><br>
                            <textarea name="txt" rows="15" cols="50">'.$e->message.'</textarea><br><br>
                            <input type="hidden" name="type" value="msgedit">
                            <input type="hidden" name="msg_id" value="'.$e->msg_id.'">
                            <input type="submit" value="'.$lng->getTrn('common/submit').'">
                        </form>';
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            <?php
            $j++;
        }
        ?>

    </div>
    <?php
    MTS('Board messages generated');
    
    /*
        The right hand side column optionally (depending on settings) contains standings, latest game results, touchdown and casualties stats.
        We will now generate the stats, so that they are ready to be printed in correct order.
    */
    
    echo "<div class='main_rightColumn'>\n";
    $boxes_all = array_merge($settings['fp_standings'], $settings['fp_leaders'], $settings['fp_events'], $settings['fp_latestgames']);
    usort($boxes_all, create_function('$a,$b', 'return (($a["box_ID"] > $b["box_ID"]) ? 1 : (($a["box_ID"] < $b["box_ID"]) ? -1 : 0) );')); 
    $boxes = array();
    foreach ($boxes_all as $box) {
        # These fields distinguishes the box types.
        if      (isset($box['fields'])) {$box['dispType'] = 'standings';}
        else if (isset($box['field']))  {$box['dispType'] = 'leaders';}
        else if (isset($box['content'])){$box['dispType'] = 'events';}
        else                            {$box['dispType'] = 'latestgames';}
        switch ($box['type']) {
            case 'league':     $_type = T_NODE_LEAGUE; break;
            case 'division':   $_type = T_NODE_DIVISION; break;
            case 'tournament': $_type = T_NODE_TOURNAMENT; break;
            default: $_type = T_NODE_LEAGUE;
        }
        $box['type'] = $_type;
        $boxes[] = $box;
    }

    // Used in the below standings dispType boxes.
    global $core_tables, $ES_fields;
    $_MV_COLS = array_merge(array_keys($core_tables['mv_teams']), array_keys($ES_fields));
    $_MV_RG_INTERSECT = array_intersect(array_keys($core_tables['teams']), array_keys($core_tables['mv_teams']));
    
    // Let's print those boxes!
    foreach ($boxes as $box) {
    
    switch ($box['dispType']) {
        
        case 'standings':
            $_BAD_COLS = array(); # Halt on these columns/fields.
            switch ($box['type']) {
                case T_NODE_TOURNAMENT:
                    if (!get_alt_col('tours', 'tour_id', $box['id'], 'tour_id')) {
                        break 2;
                    }
                    $tour = new Tour($box['id']);
                    $SR = array_map(create_function('$val', 'return $val[0]."mv_".substr($val,1);'), $tour->getRSSortRule());
                    break;
                    
                case T_NODE_DIVISION: 
                    $_BAD_COLS = array('elo', 'swon', 'slost', 'sdraw', 'win_pct'); # Divisions do not have pre-calculated, MV, values of these fields.
                    // Fall through!
                case T_NODE_LEAGUE:
                default:
                    global $hrs;
                    $SR = $hrs[$box['HRS']]['rule'];
                    foreach ($SR as &$f) {
                        $field = substr($f,1);
                        if (in_array($field, $_MV_RG_INTERSECT)) {
                            if (in_array($field, $_BAD_COLS)) { # E.g. divisions have no win_pct record for teams like the mv_teams table (for tours) has.
                                fatal("Sorry, the element '$field' in your specified house sortrule #$box[HRS] is not supported for your chosen type (ie. tournament/division/league).");
                            }
                            $f = $f[0]."rg_".substr($f,1);
                        }
                        else {
                            $f = $f[0]."mv_".substr($f,1);                            
                        }
                    }
                    break;
            }
            list($teams, ) = Stats::getRaw(T_OBJ_TEAM, array($box['type'] => $box['id']), array(1, $box['length']), $SR, false);
            ?>
            <div class='boxWide'>
                <h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $box['title'];?></h3>
                <div class='boxBody'>
                    <div class='tableResponsive'>
                    <table class="boxTable">
                        <?php
                        echo "<tr>\n";
                        foreach ($box['fields'] as $title => $f) {
                            echo "<td><i>$title</i></td>\n";
                        }
                        echo "</tr>\n";
                        foreach ($teams as $t) {
                            if (!$t['retired']) {
                                echo "<tr>\n";
                                foreach ($box['fields'] as $title => $f) {
                                    if (in_array($f, $_MV_COLS)) {
                                        $f = 'mv_'.$f;
                                    }
                                    echo "<td>";
                                    if ($settings['fp_links'] && $f == 'name')
                                        echo "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$t['team_id'],false,false)."'>$t[name]</a>";
                                    elseif (is_numeric($t[$f]) && !ctype_digit(($t[$f][0] == '-') ? substr($t[$f],1) : $t[$f]))
                                        echo sprintf('%1.2f', $t[$f]);
                                    else
                                        echo in_array($f, array('tv')) ? $t[$f]/1000 : $t[$f];
                                    echo "</td>\n";
                                }
                                echo "</tr>\n";
                            }
                        }
                        ?>
                    </table>
                    </div>
                    <?php
                    if (isset($box['infocus']) && $box['infocus']) {
                        echo "<hr>";
                        _infocus($teams);
                    }                    
                    ?>
                </div>
            </div>
            <?php
            MTS('Standings table generated');
            break;
            
        case 'latestgames':
    
            if ($box['length'] <= 0) {
                break;
            }
            $upcoming = isset($box['upcoming']) ? $box['upcoming'] : false;  
           ?>
          <div class="boxWide">
              <h3 class='boxTitle<?php echo T_HTMLBOX_MATCH;?>'><?php echo $box['title'];?></h3>
              <div class='boxBody'>
                  <div class='tableResponsive'>
                  <table class="boxTable">
                      <tr>
                          <td style="text-align: right;" width="50%"><i><?php echo $lng->getTrn('common/home');?></i></td>
                          <td> </td>
                          <td style="text-align: left;" width="50%"><i><?php echo $lng->getTrn('common/away');?></i></td>
                          <?php if (!$upcoming) { ?>
                              <td><i><?php echo $lng->getTrn('common/date');?></i></td>
                          <?php } ?>
                          <td> </td>
                      </tr>
                        <?php
                        list($matches,$pages) = Match::getMatches(array(1, $box['length']), $box['type'], $box['id'], $upcoming); 
                        foreach ($matches as $m) {
                            echo "<tr valign='top'>\n";
                            $t1name = ($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team1_id,false,false)."'>$m->team1_name</a>" : $m->team1_name;
                            $t2name = ($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team2_id,false,false)."'>$m->team2_name</a>" : $m->team2_name;
                            echo "<td style='text-align: right;'>$t1name</td>\n";
                            if ($upcoming) {
                                echo "<td>&mdash;</td>\n";
                            } else {
                                echo "<td><nobr>$m->team1_score&mdash;$m->team2_score</nobr></td>\n";
                            }
                            echo "<td style='text-align: left;'>$t2name</td>\n";
                            if (!$upcoming) {
                                echo "<td>" . str_replace(' ', '&nbsp;', textdate($m->date_played,true)) . "</td>";
                            }
                            echo "<td><a href='index.php?section=matches&amp;type=report&amp;mid=$m->match_id'>Show</a></td>";
                            echo "</tr>";
                        }
                        ?>  
                    </table>
                    </div>
                </div>
            </div>
            <?php
            MTS('Latest matches table generated');
            break;
    
        case 'leaders':
        
            $f = 'mv_'.$box['field'];
            list($players, ) = Stats::getRaw(T_OBJ_PLAYER, array($box['type'] => $box['id']), array(1, $box['length']), array('-'.$f), false)
            ?>
            <div class="boxWide">
                <h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $box['title'];?></h3>
                <div class='boxBody'>
                    <div class='tableResponsive'>
                    <table class="boxTable">
                        <tr>
                            <td><i><?php echo $lng->getTrn('common/name');?></i></td>
                            <?php 
                            if ($box['show_team']) {
                                ?><td><i><?php echo $lng->getTrn('common/team');?></i></td><?php
                            }
                            ?>
                            <td><i>#</i></td>
                            <td><i><?php echo $lng->getTrn('common/value');?></i></td>
                        </tr>
                        <?php
                        foreach ($players as $p) {
                            echo "<tr>\n";
                            echo "<td>".(($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$p['player_id'],false,false)."'>$p[name]</a>" : $p['name'])."</td>\n";
                            if ($box['show_team']) {
                                echo "<td>".(($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$p['owned_by_team_id'],false,false)."'>$p[f_tname]</a>" : $p['f_tname'])."</td>\n";
                            }
                            echo "<td>".$p[$f]."</td>\n";
                            echo "<td>".$p['value']/1000 ."k</td>\n";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                    </div>
                </div>
            </div>
            <?php
            MTS('Leaders standings generated');
            break;
            
        case 'events':
            $events = _events($box['content'], $box['type'], $box['id'], $box['length']);
            ?>
            <div class="boxWide">
                <h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $box['title'];?></h3>
                <div class='boxBody'>
                    <div class='tableResponsive'>
                    <table class="boxTable">
                        <?php
                        $head = array_pop($events);
                        echo "<tr>\n";
                        foreach ($head as $col => $name) {
                            echo "<td><i>$name</i></td>\n";
                        }
                        echo "</tr>\n";
                        foreach ($events as $e) {
                            echo "<tr>\n";
                            foreach ($head as $col => $name) {
                                switch ($col) {
                                    case 'date':
                                        $e->$col = str_replace(' ', '&nbsp;', textdate($e->$col,true));
                                        break;
                                    case 'name': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$e->pid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'tname': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$e->f_tid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'rname': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_RACE,$e->f_rid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'f_pos_name': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_RACE,$e->f_rid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'value': 
                                            $e->$col = $e->$col/1000 . 'k';
                                        break;
                                }
                                echo "<td>".$e->$col."</td>\n";
                            }
                            echo "</tr>\n";
                        }
                        ?>
                    </table>
                    </div>
                </div>
            </div>
            <?php
            MTS('Events box generated');
            break;
    }
    }
    ?>
    </div>
    <div class="main_foot">
        <?php
        HTMLOUT::dnt();
        ?>
        <br>
        <!--a TARGET="_blank" href="https://github.com/TheNAF/naflm">NAFLM official website</a> <br><br-->
        This web site is completely unofficial and in no way endorsed by Games Workshop Limited.
        <br>
        Bloodquest, Blood Bowl, the Blood Bowl logo, The Blood Bowl Spike Device, Chaos, the Chaos device, the Chaos logo, Games Workshop, Games Workshop logo, Nurgle, the Nurgle device, Skaven, Tomb Kings, and all associated marks, names, races, race insignia, characters, vehicles, locations, units, illustrations and images from the Blood Bowl game, the Warhammer world are either (R), TM and/or (C) Games Workshop Ltd 2000-2025, variably registered in the UK and other countries around the world. Used without permission. No challenge to their status intended. All Rights Reserved to their respective owners.
        <br>
        FUMBBL icons are used with permission.  
    <?php
}

$_INFOCUSCNT = 1; # HTML ID for _infocus()
function _infocus($teams) {
    
    //Create a new array of teams to display
    $ids = array();
    foreach ($teams as $team) {
        if (!$team['retired']) {
            $ids[] = $team['team_id'];
        }
    }

    if (empty($teams)) {
        return;
    }

    global $lng, $_INFOCUSCNT;

    //Select random team
    $teamKey = array_rand($ids);
    $teamId = $ids[$teamKey];
    $team = new Team($teamId);
    $teamLink =  "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$teamId,false,false)."'>$team->name</a>";

    //Create $logo_html
    $img = new ImageSubSys(IMGTYPE_TEAMLOGO, $team->team_id);
    $logo_html = "<img border='0px' height='60' alt='Team picture' src='".$img->getPath($team->f_race_id)."'>";

    //Create $starPlayers array used to display the three most experienced players on the team
    $starPlayers = array();
    foreach ($team->getPlayers() as $p) {
        if ($p->is_dead || $p->is_sold) {
            continue;
        }
        $starPlayers[] = array('name' => preg_replace('/\s/', '&nbsp;', $p->name), 'spp' => $p->mv_spp - $p->extra_spp);
    }

    //Sort the array
    usort($starPlayers, create_function('$objA,$objB', 'return ($objA["spp"] < $objB["spp"]) ? +1 : -1;'));
    $starPlayers = array_slice($starPlayers, 0, 3); # Show only 3 Star players

    ?>
    <style type="text/css">
        /* InFocus Mod */
        #inFocusBox<?php echo $_INFOCUSCNT;?> .leftContentTd{
            font-weight: bold;
            padding-right: 1em;
        }

        #inFocusBox<?php echo $_INFOCUSCNT;?> .teamLogo {
            float: left;
            margin: 0 36px 0 20px;
        }

        #inFocusBox<?php echo $_INFOCUSCNT;?> .teamName {
            font-weight: bold;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> {
            position:relative;
            left: 160px;
            height: 80px;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> P {
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV {
            position:absolute;
            top:0;
            left:0;
            z-index:8;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV.invisible {
            display: none;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV.inFocus {
            z-index:10;
            display: inline;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV.last-inFocus {
            z-index:9;redeclare compare_spp
        }
    </style>
    <div id="inFocusBox<?php echo $_INFOCUSCNT;?>" >
        <h3><?php echo $lng->getTrn('main/infocus').': '.$teamLink; ?></h3><br>
        <div style='clear:both;'>
            <div class='teamLogo'>
                <?php echo $logo_html; ?>
            </div>
            <div id="inFocusContent<?php echo $_INFOCUSCNT;?>">
                <div class="inFocus">
                    <div class='tableResponsive'>
                    <table>
                        <tr><td class="leftContentTd"><?php echo $lng->getTrn('common/coach'); ?></td><td><?php echo $team->f_cname; ?></td></tr>
                        <tr><td class="leftContentTd"><?php echo $lng->getTrn('common/race'); ?></td><td><?php echo $team->f_rname; ?></td></tr>
                        <tr><td class="leftContentTd"><?php echo 'TV'; ?></td><td><?php echo (string)($team->tv / 1000); ?>k</td></tr>
                    </table>
                    </div>
                </div>
                <div class="invisible">
                    <p><?php echo $lng->getTrn('common/stars'); ?></p>
                    <div class='tableResponsive'>
                    <table>
                        <?php
                        foreach($starPlayers as $player) {
                            echo "<tr><td class='leftContentTd'>".$player['name']."</td><td>".$player['spp']." spp</td></tr>";
                        }
                        ?>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    /* 
     * This script creates a slideshow of all <div>s in the "inFocusContent" div 
     * Based on an example by Jon Raasch:
     * http://jonraasch.com/blog/a-simple-jquery-slideshow
     */
    function nextContent<?php echo $_INFOCUSCNT;?>() {
        var $currentDiv = $('#inFocusContent<?php echo $_INFOCUSCNT;?> DIV.inFocus');
        var $nextDiv = $currentDiv.next().length ? $currentDiv.next() : $('#inFocusContent<?php echo $_INFOCUSCNT;?> DIV:first');
        $currentDiv.addClass('last-inFocus');

        //Fade current out
        $currentDiv.animate({opacity: 0.0}, 500, function() {
            $currentDiv.removeClass('inFocus last-inFocus');
            $currentDiv.addClass('invisible');
        });

        //Fade next in
        $nextDiv.css({opacity: 0.0})
            .addClass('inFocus')
            .animate({opacity: 1.0}, 500, function() {
            });
    }

    $(function() {
        setInterval( "nextContent<?php echo $_INFOCUSCNT;?>()", 5000 );
    });
    </script>
    
    <?php
    $_INFOCUSCNT++;
}

$_T_EVENTS = array('dead' => ObjEvent::T_PLAYER_DEAD, 'sold' => ObjEvent::T_PLAYER_SOLD, 'hired' => ObjEvent::T_PLAYER_HIRED, 'skills' => ObjEvent::T_PLAYER_SKILLS); # Allowed events
function _events($event, $node, $node_id, $N) {
    global $mv_keys, $_T_EVENTS, $lng;
    $dispColumns = array();
    switch ($event) {
        case 'dead':
        case 'sold':
        case 'hired':
            $dispColumns = array('name' => $lng->getTrn('common/player'), 'value' => $lng->getTrn('common/value'), 'tname' => $lng->getTrn('common/team'), 'date' => $lng->getTrn('common/date'));
            break;

        case 'skills':
            $dispColumns = array('skill' => $lng->getTrn('common/skill'), 'name' => $lng->getTrn('common/player'), 'f_pos_name' => $lng->getTrn('common/pos'), 'value' => $lng->getTrn('common/value'), 'tname' => $lng->getTrn('common/team'));
            break;

        // Event type not existing
        default:
          return array();
    }
    $events = ObjEvent::getRecentEvents($_T_EVENTS[$event], $node, $node_id, $N);
    $events[] = $dispColumns;
    return $events;
}

function sec_teamlist() {
    global $lng;
    title($lng->getTrn('menu/teams'));
    Team_HTMLOUT::dispList();
}

function sec_coachlist() {
    global $lng;
    title($lng->getTrn('menu/coaches'));
    Coach_HTMLOUT::dispList();
}

function sec_matcheshandler() {
    switch ($_GET['type'])
    {
        # Save all these subroutines in class_match_htmlout.php
        case 'tours':       Match_HTMLOUT::tours(); break;
        case 'tourmatches': Match_HTMLOUT::tourMatches(); break;
        case 'report':      Match_HTMLOUT::report(); break;
        case 'recent':      Match_HTMLOUT::recentMatches(); break;
        case 'upcoming':    Match_HTMLOUT::upcomingMatches(); break;
        case 'usersched':   Match_HTMLOUT::userSched(); break;
    }    
}

function sec_objhandler() {
    $types = array(T_OBJ_PLAYER => 'Player', T_OBJ_TEAM => 'Team', T_OBJ_COACH => 'Coach', T_OBJ_STAR => 'Star', T_OBJ_RACE => 'Race');
    foreach ($types as $t => $classPrefix) {
        if ($_GET['obj'] == $t) {
            switch ($_GET['type'])
            {
                case T_URL_STANDINGS:
                    call_user_func(
                        array("${classPrefix}_HTMLOUT", 'standings'), 
                        isset($_GET['node'])    ? (int) $_GET['node']    : false, 
                        isset($_GET['node_id']) ? (int) $_GET['node_id'] : false
                    );
                    break;
                case T_URL_PROFILE:
                    if (!call_user_func(array($classPrefix, 'exists'), (int) $_GET['obj_id'])) {
                        fatal("The specified ID does not exist.");
                    }
                    call_user_func(array("${classPrefix}_HTMLOUT", 'profile'), (int) $_GET['obj_id']);
                    break;
            }
        }
    }
}

/*************************
 *  Rules
 *************************/
function sec_rules() {
    global $lng, $settings, $leagues;
    title($lng->getTrn('menu/rules'));
    ?>
    <div class="boxWide">
        <?php
            if(count($leagues) > 1)
            {
                echo '<div class="boxTitle4">';
                list($sel_lid, $HTML_LeagueSelector) = HTMLOUT::simpleLeagueSelector();
                echo $HTML_LeagueSelector;
                echo '</div>';
            }
        ?>
        <div class="boxBody">
            <div>
                <?php echo $settings['rules']; ?>
            </div>
        </div>
    </div>
    <?php
}

/*************************
 *  Star Players List
 *************************/
function sec_stars() {
    global $lng, $settings, $leagues, $DEA, $stars, $specialruleididx, $rules;
    title($lng->getTrn('common/starlist'));
    ?>
    <div class="boxWide">
        <div class="boxBody" id="starPlayersListBox">
            <div>
		<?php	
		$racestars = array(); 
		foreach ($stars as $s => $d) {  
		$tmp = new Star($d['id']);
		$tmp->skills = skillsTrans($tmp->skills);    
		$tmp->keywords = '('.keywordsTrans($tmp->keywords).')';    
		$tmp->special = specialsTrans($tmp->special);   
		$tmp->specialdesc = $lng->getTrn('specialrules/'.$tmp->specialdesc.'desc');
		$tmp->teamrules = specialsTrans($tmp->teamrules);            
		$tmp->races = racesTrans($tmp->races);            
		$racestars[] = $tmp;
			if ($tmp->pa == 0) {       
				$tmp->pa = '-';
			}
			if ($tmp->pa != '-' && $tmp->pa != '1+' && $tmp->pa != '2+' && $tmp->pa != '3+' && $tmp->pa != '4+' && $tmp->pa != '5+' && $tmp->pa != '6+') {       
				$tmp->pa = $tmp->pa.'+';
			}
			if ($tmp->megastar == 1) {       
				$tmp->name = $tmp->name.'*';
			}
			if (preg_match('/Badlands Brawl, Chaos Clash, Elven Kingdoms League, Halfling Thimble Cup, Lustrian Superleague, Old World Classic, Sylvanian Spotlight, Underworld Challenge, Woodlands League, Worlds Edge Superleague/',$tmp->teamrules)) {       
			$tmp->teamrules = preg_replace("/Badlands Brawl, Chaos Clash, Elven Kingdoms League, Halfling Thimble Cup, Lustrian Superleague, Old World Classic, Sylvanian Spotlight, Underworld Challenge, Woodlands League, Worlds Edge Superleague/", "Any Team", $tmp->teamrules);
			}
		}
		$fields = array(
			'name'   => array('desc' => $lng->getTrn('common/star'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'star_id')),
			'ma'     => array('desc' => $lng->getTrn('common/ma')),
			'st'     => array('desc' => $lng->getTrn('common/st')),
			'ag'     => array('desc' => $lng->getTrn('common/ag'), 'suffix' => '+'),
			'pa'     => array('desc' => $lng->getTrn('common/pa')),
			'av'     => array('desc' => $lng->getTrn('common/av'), 'suffix' => '+'),
			'skills' => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
			'cost'   => array('desc' => $lng->getTrn('common/price'), 'kilo' => true, 'suffix' => 'k'),
			'special' => array('desc' => $lng->getTrn('common/specialrule')),
			'specialdesc' => array('desc' => $lng->getTrn('common/specialruledesc')),
			//'races' => array('desc' => $lng->getTrn('common/playsfor'), 'nosort' => true),
			'teamrules' => array('desc' => $lng->getTrn('common/playsfor'), 'nosort' => true),
			'keywords' => array('desc' => $lng->getTrn('common/keywords'), 'nosort' => true),
		);
		HTMLOUT::sort_table(
			$lng->getTrn('common/starlist'),
			'index.php?section=stars',
			$racestars,
			$fields,
			sort_rule('star'),
			(isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
			array('anchor' => 's2', 'doNr' => false, 'noHelp' => true)
		);
		?>
            </div>
        </div>
    </div>
    <?php
}

/*************************
 *  Inducements List
 *************************/
function sec_inducements() {
    global $lng, $settings, $leagues, $DEA, $inducements, $specialruleididx, $rules;
    title($lng->getTrn('common/inducementlist'));
    ?>
    <div class="boxWide">
        <div class="boxBody">
		<div>
		<?php	
		$inducementslist = array(); 
		foreach ($inducements as $ind_name => $ind) {	  
			$inducementrules = implode(",", specialsTrans($ind['teamrules']));
			if (preg_match('/Badlands Brawl,Chaos Clash,Elven Kingdoms League,Halfling Thimble Cup,Lustrian Superleague,Old World Classic,Sylvanian Spotlight,Underworld Challenge,Woodlands League,Worlds Edge Superleague/',$inducementrules)) {       
				$inducementrules = preg_replace("/Badlands Brawl,Chaos Clash,Elven Kingdoms League,Halfling Thimble Cup,Lustrian Superleague,Old World Classic,Sylvanian Spotlight,Underworld Challenge,Woodlands League,Worlds Edge Superleague/", "Any Team", $inducementrules);
			}
			if (preg_match('/Favoured of...,Favoured of Chaos Undivided,Favoured of Hashut,Favoured of Khorne,Favoured of Nurgle,Favoured of Slaanesh,Favoured of Tzeentch/',$inducementrules)) {       
				$inducementrules = preg_replace("/Favoured of...,Favoured of Chaos Undivided,Favoured of Hashut,Favoured of Khorne,Favoured of Nurgle,Favoured of Slaanesh,Favoured of Tzeentch/", "Favoured of...", $inducementrules);
			}	  			
			$redinducementrules = implode(",", specialsTrans($ind['reduced_cost_rules']));
			if (preg_match('/Badlands Brawl,Chaos Clash,Elven Kingdoms League,Halfling Thimble Cup,Lustrian Superleague,Old World Classic,Sylvanian Spotlight,Underworld Challenge,Woodlands League,Worlds Edge Superleague/',$redinducementrules)) {       
				$redinducementrules = preg_replace("/Badlands Brawl,Chaos Clash,Elven Kingdoms League,Halfling Thimble Cup,Lustrian Superleague,Old World Classic,Sylvanian Spotlight,Underworld Challenge,Woodlands League,Worlds Edge Superleague/", "Any Team", $redinducementrules);
			}
			if (preg_match('/Favoured of...,Favoured of Chaos Undivided,Favoured of Hashut,Favoured of Khorne,Favoured of Nurgle,Favoured of Slaanesh,Favoured of Tzeentch/',$redinducementrules)) {       
				$redinducementrules = preg_replace("/Favoured of...,Favoured of Chaos Undivided,Favoured of Hashut,Favoured of Khorne,Favoured of Nurgle,Favoured of Slaanesh,Favoured of Tzeentch/", "Favoured of...", $redinducementrules);
			}  			
			$redinducementraces = implode(",", racesTrans($ind['reduced_cost_races']));			
			// Check for base inducements rule 
			if (($rules['base_inducements'] == 0 || ($ind['source'] == 1 && $rules['base_inducements'] == 1))) {				
				// Determine type
				$type = '';
				if ($ind['type'] == 1) {
					$type = 'Common';
				}
				if ($ind['type'] == 2) {
					$type = 'Wizard';
				}
				if ($ind['type'] == 3) {
					$type = '(In)famous Coaching Staff';
				}
				if ($ind['type'] == 4) {
					$type = 'Biased Referee';
				}				
				// Determine source
				$source = '';
				if ($ind['source'] == 1) {
					$source = '2025 Rulebook';
				}
				if ($ind['source'] == 2) {
					$source = 'Spike Mag';
				}				
				// Handle special cases for available to
				$availableTo = '';
				if ($ind_name == 'Wandering Apothecaries') {
					$availableTo = 'Any team that can hire an Apothecary';
				} elseif ($ind_name == 'Bottles of Heady Brew') {
					$availableTo = 'Any team in Tier 4';
				} else {
					$availableTo = $inducementrules;
				}				
				// Handle reduced cost fields
				$reducedAvailableTo = '';
				$reducedQty = '';
				$reducedCost = '';				
				if ($redinducementrules.$redinducementraces != '' && $ind_name != 'Bottles of Heady Brew') {
					$reducedAvailableTo = $redinducementrules.$redinducementraces;
					$reducedQty = '0-'.$ind['reduced_max'];
					$reducedCost = ($ind['reduced_cost']/1000).'k';
				}				
				// Create object
				$tmp = (object) array(
					'type' => $type,
					'source' => $source,
					'name' => $ind_name,
					'qty' => '0-'.$ind['max'],
					'cost' => ($ind['cost']/1000).'k',
					'available_to' => $availableTo,
					'reduced_available_to' => $reducedAvailableTo,
					'reduced_qty' => $reducedQty,
					'reduced_cost' => $reducedCost
				);				
				$inducementslist[] = $tmp;
			}
		}
		$fields = array(
			'type' => array('desc' => 'Type'),
			'source' => array('desc' => 'Source'),
			'name' => array('desc' => 'Inducement Name'),
			'qty' => array('desc' => 'Qty'),
			'cost' => array('desc' => 'Cost'),
			'available_to' => array('desc' => 'Available To', 'nosort' => true),
			'reduced_available_to' => array('desc' => 'Available Reduced To', 'nosort' => true),
			'reduced_qty' => array('desc' => 'Qty'),
			'reduced_cost' => array('desc' => 'Reduced Cost')
		);
		HTMLOUT::sort_table(
			'Inducements List',
			'index.php?section=inducements',
			$inducementslist,
			$fields,
			sort_rule('inducement'),
			(isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
			array('anchor' => 's2', 'doNr' => false, 'noHelp' => true)
		);
		?>
        </div>
        </div>
    </div>
    <?php
}
/*************************
 *  Skills & Traits List
 *************************/
function sec_skills() {
    global $lng, $settings, $leagues, $DEA, $skillididx, $skillarray, $rules;
    title($lng->getTrn('common/skilllist'));
    ?>
    <div class="boxWide">
        <div class="boxBody" id="skillslistbox">
		<?php	
		$skillslist = array();
		$hatredFound = false;
		$lonerFound = false;
		$bloodlustFound = false;
		$animosityFound = false;
		foreach ($skillarray as $grp => $skills) {
			foreach ($skills as $id => $name) {
				$grpName = $grp;				
				if ($grpName == 'G') { 
					$grpName = 'General'; 
				}
				if ($grpName == 'A') { 
					$grpName = 'Agility'; 
				}
				if ($grpName == 'D') { 
					$grpName = 'Devious'; 
				}
				if ($grpName == 'S') { 
					$grpName = 'Strengh'; 
				}
				if ($grpName == 'P') { 
					$grpName = 'Passing'; 
				}
				if ($grpName == 'M') { 
					$grpName = 'Mutation'; 
				}
				if ($grpName == 'E') { 
					$grpName = 'Trait'; 
				}			
				if ($name == 'Block' || $name == 'Dodge' || $name == 'Guard' || $name == 'Mighty Blow') { 
					$elite = 'Y'; 
				} else { 
					$elite = 'N' ;
				}			
				// Convert skill name to XML-friendly format (lowercase, no spaces, no special characters)
				$xmlSkillName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));			
				// Skip the lowcostlinemenspecialrule and swarming entries
				if ($xmlSkillName == 'lowcostlinemenspecialrule' || $xmlSkillName == 'swarming') {
					continue;
				}				
				$translatedName = $lng->getTrn('skilllist/' . $xmlSkillName);  
				$skilldesc = $lng->getTrn('skilldesc/' . $xmlSkillName . 'desc');				
				// Check if this is a Hatred skill (check both original name and xml name)
				if (stripos($name, 'Hatred') !== false || stripos($xmlSkillName, 'hatred') !== false || stripos($translatedName, 'Hatred') !== false) {
					if (!$hatredFound) {
						$hatredFound = true;
					}
					continue;
				}				
				// Check if this is a Loner skill
				if (stripos($name, 'Loner') !== false || stripos($xmlSkillName, 'loner') !== false || stripos($translatedName, 'Loner') !== false) {
					if (!$lonerFound) {
						$lonerFound = true;
					}
					continue;
				}				
				// Check if this is a Bloodlust skill
				if (stripos($name, 'Bloodlust') !== false || stripos($xmlSkillName, 'bloodlust') !== false || stripos($translatedName, 'Bloodlust') !== false) {
					if (!$bloodlustFound) {
						$bloodlustFound = true;
					}
					continue;
				}				
				// Check if this is an Animosity skill
				if (stripos($name, 'Animosity') !== false || stripos($xmlSkillName, 'animosity') !== false || stripos($translatedName, 'Animosity') !== false) {
					if (!$animosityFound) {
						$animosityFound = true;
					}
					continue;
				}				
				// Create an object for other skills
				$tmp = (object) array(
					//'id' => $id,
					'skillname' => $translatedName,
					'skillcat' => $grpName,
					'elite' => $elite,
					'skilldesc' => $skilldesc
				);						
				$skillslist[] = $tmp;
			}
		}
		// Add the consolidated entries
		if ($hatredFound) {
			$tmp = (object) array(
				'skillname' => $lng->getTrn('skilllist/hatredx'),
				'skillcat' => 'Trait',
				'elite' => 'N',
				'skilldesc' => $lng->getTrn('skilldesc/hatredxdesc'),
			);
			$skillslist[] = $tmp;
		}
		if ($lonerFound) {
			$tmp = (object) array(
				'skillname' => $lng->getTrn('skilllist/lonerx'),
				'skillcat' => 'Trait',
				'elite' => 'N',
				'skilldesc' => $lng->getTrn('skilldesc/lonerxdesc'),
			);
			$skillslist[] = $tmp;
		}
		if ($bloodlustFound) {
			$tmp = (object) array(
				'skillname' => $lng->getTrn('skilllist/bloodlustx'),
				'skillcat' => 'Trait',
				'elite' => 'N',
				'skilldesc' => $lng->getTrn('skilldesc/bloodlustxdesc'),
			);
			$skillslist[] = $tmp;
		}
		if ($animosityFound) {
			$tmp = (object) array(
				'skillname' => $lng->getTrn('skilllist/animosityx'),
				'skillcat' => 'Trait',
				'elite' => 'N',
				'skilldesc' => $lng->getTrn('skilldesc/animosityxdesc'),
			);
			$skillslist[] = $tmp;
		}
		$fields = array(
			'skillcat'   => array('desc' => $lng->getTrn('common/skill_cat')), 
			'skillname'   => array('desc' => $lng->getTrn('common/skill')), 
			'skilldesc'   => array('desc' => $lng->getTrn('common/skill_desc'), 'nosort' => true),
			'elite' => array('desc' => $lng->getTrn('common/skill_elite')),
		);
		HTMLOUT::sort_table(
			$lng->getTrn('common/skilllist'),
			'index.php?section=skills',
			$skillslist,
			$fields,
			sort_rule('skill'),
			(isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
			array('anchor' => 's2', 'doNr' => false, 'noHelp' => true)
		);
		?>
        </div>
    </div>
    <?php
}
/*************************
 *  Cheat Sheet
 *************************/
function sec_cheatsheet() {
    global $lng;
    title($lng->getTrn('common/cheatsheet'));
    ?>
    <div class="boxWide">
        <div class="boxBody">
		<div>
		<style>
		.bg {
		  background-color: grey;
		}
		</style>
		<div class='tableResponsive'>
		<table border="1" cellpadding="6" cellspacing="0">
			<tbody>

				<!-- WEATHER TABLE -->
				<tr>
					<th>2D6</th>
					<th colspan="5">WEATHER CONDITION</th>
				</tr>

				<tr>
					<td><strong>2</strong></td>
					<td colspan="5"><strong>SWELTERING HEAT:</strong> The intense heat causes some players to faint! At the end of each Drive whilst this weather condition is in effect, one Coach rolls a D3 and each Coach randomly selects that many of their players that were on the pitch when the Drive ended. The selected players are placed in the Reserves Box and cannot be set up on the pitch for the next Drive.</td>
				</tr>

				<tr>
					<td><strong>3</strong></td>
					<td colspan="5"><strong>VERY SUNNY:</strong> The glorious sunshine makes for a beautiful day, but plays havoc with the passing game! Whenever a player makes a Passing Ability Test, apply a -1 modifier to the roll.</td>
				</tr>

				<tr>
					<td><strong>4-10</strong></td>
					<td colspan="5"><strong>PERFECT CONDITIONS:</strong> Not too hot, nor too cold. It's perfect weather for Blood Bowl! There is no additional effect.</td>
				</tr>

				<tr>
					<td><strong>11</strong></td>
					<td colspan="5"><strong>POURING RAIN:</strong> The heavens have opened and the sudden downpour has left the players soaked and the ball rather slippery! Whenever a player attempts to pick up or Catch the ball, or Intercept a Pass Action, they suffer a -1 modifier to the roll.</td>
				</tr>

				<tr>
					<td><strong>12</strong></td>
					<td colspan="5"><strong>BLIZZARD:</strong> The freezing conditions and swirling snow makes the footing treacherous and drastically impedes a player's vision. Whenever a player attempts to Rush, apply an additional -1 modifier to the roll. Additionally, when a player makes a Pass Action, they may only attempt to make a Quick Pass or Short Pass.</td>
				</tr>

				<tr><td colspan="6" class="bg">&nbsp;</td></tr>

				<!-- KICK-OFF TABLE -->
				<tr>
					<th>2D6</th>
					<th colspan="5">KICK-OFF EVENT</th>
				</tr>

				<tr>
					<td><strong>2</strong></td>
					<td colspan="5"><strong>GET THE REF:</strong> Each team immediately receives one free Bribe Inducement. This Bribe must be used by the end of the game or it is lost.</td>
				</tr>

				<tr>
					<td><strong>3</strong></td>
					<td colspan="5"><strong>TIME-OUT:</strong> If the kicking team's Turn Marker is on turn 6, 7 or 8 for the half, move both teams' Turn Marker back one space. Otherwise, move both teams' Turn Marker forwards one space.</td>
				</tr>

				<tr>
					<td><strong>4</strong></td>
					<td colspan="5"><strong>SOLID DEFENCE:</strong> The Coach of the kicking team selects up to D3+3 Open players on their team. The selected players are removed from the pitch and set up again normally.</td>
				</tr>

				<tr>
					<td><strong>5</strong></td>
					<td colspan="5"><strong>HIGH KICK:</strong> One Open player on the receiving team may immediately be placed in the square the ball is going to land in.</td>
				</tr>

				<tr>
					<td><strong>6</strong></td>
					<td colspan="5"><strong>CHEERING FANS:</strong> Both Coaches roll a D6 and add their Cheerleaders. The first Block Action of the Coach with the higher roll receives an extra Offensive Assist. Ties mean both Coaches gain this benefit.</td>
				</tr>

				<tr>
					<td><strong>7</strong></td>
					<td colspan="5"><strong>BRILLIANT COACHING:</strong> Both Coaches roll a D6 and add Assistant Coaches. The higher (or tied) Coach immediately gains a free Team Re-roll for the Drive.</td>
				</tr>

				<tr>
					<td><strong>8</strong></td>
					<td colspan="5"><strong>CHANGING WEATHER:</strong> Immediately roll again on the Weather Table. If you roll Perfect Conditions, the ball scatters (3) before landing.</td>
				</tr>

				<tr>
					<td><strong>9</strong></td>
					<td colspan="5"><strong>QUICK SNAP:</strong> The receiving Coach picks D3+3 Open players. They may each move 1 square in any direction, even into the opponent’s half.</td>
				</tr>

				<tr>
					<td><strong>10</strong></td>
					<td colspan="5"><strong>CHARGE!:</strong> The kicking Coach selects D3+3 Open players to activate for free Move Actions. One may Blitz, one may Throw Team-mate, one may Kick Team-mate. If any fall over, the Charge ends.</td>
				</tr>

				<tr>
					<td><strong>11</strong></td>
					<td colspan="5"><strong>DODGY SNACK:</strong> Both Coaches roll a D6. The lowest (or tied) Coach randomly selects a player and rolls. On 2+, they suffer –1 MA and –1 AV for the Drive. On 1, they go to Reserves for the Drive.</td>
				</tr>

				<tr>
					<td><strong>12</strong></td>
					<td colspan="5"><strong>PITCH INVASION:</strong> Both Coaches roll a D6 and add Fan Factor. The lowest (or tied) Coach randomly selects D3 players who are Placed Prone and become Stunned.</td>
				</tr>

				<tr><td colspan="6" class="bg">&nbsp;</td></tr>

				<!-- INJURY TABLE -->
				<tr>
					<th>2D6</th>
					<th>INJURY TABLE</th>
					<td rowspan="5"></td>
					<th>2D6</th>
					<th colspan="2">STUNTY INJURY</th>
				</tr>

				<tr>
					<td><strong>2-7</strong></td>
					<td><strong>STUNNED:</strong> The player is immediately Stunned.</td>
					<td><strong>2-6</strong></td>
					<td colspan="2"><strong>STUNNED:</strong> The player is immediately Stunned.</td>
				</tr>

				<tr>
					<td><strong>8-9</strong></td>
					<td><strong>KNOCKED-OUT:</strong> Move to the dugout.</td>
					<td><strong>7-8</strong></td>
					<td colspan="2"><strong>KNOCKED-OUT:</strong> Move to the dugout.</td>
				</tr>

				<tr>
					<td><strong>10-12</strong></td>
					<td><strong>CASUALTY:</strong> Move to the Casualty box. Opponent makes a Casualty Roll.</td>
					<td><strong>9</strong></td>
					<td colspan="2"><strong>BADLY HURT:</strong> No lasting effect, no Casualty Roll.</td>
				</tr>

				<tr>
					<td colspan="2"></td>
					<td><strong>10-12</strong></td>
					<td colspan="2"><strong>CASUALTY:</strong> Casualty Box. Opponent makes a Casualty Roll.</td>
				</tr>

				<tr><td colspan="6" class="bg">&nbsp;</td></tr>

				<!-- CASUALTY TABLE -->
				<tr>
					<th>2D6</th>
					<th>CASUALTY TABLE</th>
					<td rowspan="6"></td>
					<th>D6</th>
					<th>LASTING INJURY</th>
					<th>&nbsp;</th>
				</tr>

				<tr>
					<td><strong>1-8</strong></td>
					<td><strong>BADLY HURT:</strong> No lasting effect.</td>
					<td><strong>1-2</strong></td>
					<td><strong>HEAD INJURY</strong></td>
					<td>–1 AV</td>
				</tr>

				<tr>
					<td><strong>9-10</strong></td>
					<td><strong>SERIOUSLY HURT:</strong> Miss next game.</td>
					<td><strong>3</strong></td>
					<td><strong>SMASHED KNEE</strong></td>
					<td>–1 MA</td>
				</tr>

				<tr>
					<td><strong>11-12</strong></td>
					<td><strong>SERIOUS INJURY:</strong> Niggling Injury + miss next game.</td>
					<td><strong>4</strong></td>
					<td><strong>BROKEN ARM</strong></td>
					<td>–1 PA</td>
				</tr>

				<tr>
					<td><strong>13-14</strong></td>
					<td><strong>LASTING INJURY:</strong> Permanent injury.</td>
					<td><strong>5</strong></td>
					<td><strong>DISLOCATED HIP</strong></td>
					<td>–1 AG</td>
				</tr>

				<tr>
					<td><strong>15-16</strong></td>
					<td><strong>DEAD:</strong> The player is dead!</td>
					<td><strong>6</strong></td>
					<td><strong>BROKEN SHOULDER</strong></td>
					<td>–1 ST</td>
				</tr>

				<tr><td colspan="6" class="bg">&nbsp;</td></tr>

				<!-- IMAGE -->
				<tr>
					<td colspan="6" align="center">
						<img src="images/range.png" alt="Throwing Range Table" width="500">
					</td>
				</tr>

			</tbody>
		</table>
		</div>
		</div>
        </div>
    </div>
    <?php
}
/*************************
 *  Match Sequesnce
 *************************/
function sec_sequence() {
    global $lng;
    title($lng->getTrn('common/sequence'));
    ?>
    <div class="boxWide">
        <div class="boxBody">
		<div>
		<style>
		table, th, td {
		  border: 1px solid black;
		  border-collapse: collapse;
		}
		</style>
		<p align="left"><strong>Pre-Game & Post-Game Sequence</strong></p>
		<div class='tableResponsive'>
		<table cellpadding="6">
			<tr>
			<th style="width: 117.422px;" align="left">Pre-Game</th>
			<th style="width: 158.047px;" align="left">Post-Game</th>
			</tr>
			<tr>
			<td style="width: 117.422px;">Inducements</td>
			<td style="width: 158.047px;">Winnings</td>
			</tr>
			<tr>
			<td style="width: 117.422px;">Fan Factor</td>
			<td style="width: 158.047px;">Dedicated Fans</td>
			</tr>
			<tr>
			<td style="width: 117.422px;">Weather</td>
			<td style="width: 158.047px;">MVP (D6)</td>
			</tr>
			<tr>
			<td style="width: 117.422px;">Kicking Team</td>
			<td style="width: 158.047px;">Expensive Mistakes</td>
			</tr>
		</table>
		</div>

		<br><br>

		<p align="left"><strong>Expensive Mistakes Table</strong></p>
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
							require_once('lib/class_expensive_mistakes.php');
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
		<br>

		<p align="left"><strong>Incident Results</strong></p>
		<div class='tableResponsive'>
		<table cellpadding="6">
		  <tr><th align="left">Crisis Averted</th><td>No treasury lost</td></tr>
		  <tr><th align="left">Minor Incident</th><td>D3 × 10k loss</td></tr>
		  <tr><th align="left">Major Incident</th><td>Lose half of Treasury, rounded down to nearest 5k</td></tr>
		  <tr><th align="left">Catastrophe</th><td>Lose all Treasury except 2D6 × 10k</td></tr>
		</table>
		</div>

		<br><br>

		<p align="left"><strong>Random Skill Table</strong></p>
		<div class='tableResponsive'>
		<table cellpadding="6">
		  <tr>
			<th align="left">1st D6</th>
			<th align="left">2nd D6</th>
			<th align="left">Agility</th>
			<th align="left">Devious</th>
			<th align="left">General</th>
			<th align="left">Mutation</th>
			<th align="left">Passing</th>
			<th align="left">Strength</th>
		  </tr>

		  <tr><th align="left" rowspan="6">1–3</th><th align="left">1</th><td>Catch</td><td>Dirty Player</td><td>Block</td><td>Big Hand</td><td>Accurate</td><td>Arm Bar</td></tr>
		  <tr><th align="left">2</th><td>Diving Catch</td><td>Eye Gouge</td><td>Dauntless</td><td>Claws</td><td>Cannoneer</td><td>Brawler</td></tr>
		  <tr><th align="left">3</th><td>Diving Tackle</td><td>Fumblerooski</td><td>Fend</td><td>Disturbing Presence*</td><td>Cloud Burster</td><td>Break Tackle</td></tr>
		  <tr><th align="left">4</th><td>Dodge</td><td>Lethal Flight</td><td>Frenzy*</td><td>Extra Arms</td><td>Dump-off</td><td>Bullseye</td></tr>
		  <tr><th align="left">5</th><td>Defensive</td><td>Lone Fouler</td><td>Kick</td><td>Foul Appearance*</td><td>Give and Go</td><td>Grab</td></tr>
		  <tr><th align="left">6</th><td>Hit and Run</td><td>Pile Driver</td><td>Pro</td><td>Horns</td><td>Hail Mary Pass</td><td>Guard</td></tr>

		  <tr><th align="left" rowspan="6">4–6</th><th align="left">1</th><td>Jump Up</td><td>Put the Boot In</td><td>Steady Footing</td><td>Iron Hard Skin</td><td>Leader</td><td>Juggernaut</td></tr>
		  <tr><th align="left">2</th><td>Leap</td><td>Quick Foul</td><td>Strip Ball</td><td>Monstrous Mouth</td><td>Nerves of Steel</td><td>Mighty Blow</td></tr>
		  <tr><th align="left">3</th><td>Safe Pair of Hands</td><td>Saboteur</td><td>Sure Hands</td><td>Prehensile Tail</td><td>On the Ball</td><td>Multiple Block</td></tr>
		  <tr><th align="left">4</th><td>Sidestep</td><td>Shadowing</td><td>Tackle</td><td>Tentacles</td><td>Pass</td><td>Stand Firm</td></tr>
		  <tr><th align="left">5</th><td>Sprint</td><td>Sneaky Git</td><td>Taunt</td><td>Two Heads</td><td>Punt</td><td>Strong Arm</td></tr>
		  <tr><th align="left">6</th><td>Sure Feet</td><td>Violent Innovator</td><td>Wrestle</td><td>Very Long Legs</td><td>Safe Pass</td><td>Thick Skull</td></tr>
		</table>
		</div>

		<br><br>

		<p align="left"><strong>Characteristic Improvement</strong></p>
		<div class='tableResponsive'>
		<table cellpadding="6">
		  <tr>
			<th align="left">D8</th>
			<th align="left">Result</th>
		  </tr>
		  <tr><th align="left">1</th><td>AV</td></tr>
		  <tr><th align="left">2</th><td>AV or PA</td></tr>
		  <tr><th align="left">3–4</th><td>AV, MA or PA</td></tr>
		  <tr><th align="left">5</th><td>MA or PA</td></tr>
		  <tr><th align="left">6</th><td>AG or MA</td></tr>
		  <tr><th align="left">7</th><td>AG or ST</td></tr>
		  <tr><th align="left">8</th><td>Any</td></tr>
		</table>
		</div>

        </div>
        </div>
    </div>
    <?php
}
/*************************
 *  Prayer to Nuffle
 *************************/
function sec_ptn() {
    global $lng;
    title($lng->getTrn('common/ptn'));
    ?>
    <div class="boxWide">
        <div class="boxBody">
		<div>
		<div class='tableResponsive'>
		<table style="border-collapse: collapse;" border="0" cellspacing="0" cellpadding="5">
		<tbody>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>D16</strong></td>
		<td style="text-align: left;"><strong>RESULT</strong></td>
		</tr>
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>1</strong></td>
		<td style="text-align: left;"><strong>TREACHEROUS TRAPDOOR:</strong> Each time a player from either team enters a square containing a Trapdoor for any reason, roll a D6. On a 1, the Trapdoor falls open and the player falls through it. Make an Injury Roll for the player exactly as if they had been Pushed into the Crowd. If the player was holding the ball it will Bounce from the Trapdoor square.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>2</strong></td>
		<td><strong>FRIENDS WITH THE REF:</strong> Whenever you Argue the Call, treat a roll of a 5 or 6 as &ldquo;Well, when you put it like that&hellip;&rdquo;</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>3</strong></td>
		<td><strong>STILETTO:</strong> Randomly select one player on your team that is playing this game. The selected player gains the Stab Trait for the duration of the game.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>4</strong></td>
		<td><strong>IRON MAN:</strong> Select one player on your team that is playing this game. The selected player improves their AV by 1 (to a maximum of 11+) for the duration of the game.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>5</strong></td>
		<td><strong>KNUCKLE DUSTERS:</strong> Select one player on your team that is playing this game. The selected player gains the Mighty Blow Skill for the duration of the game.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>6</strong></td>
		<td><strong>BAD HABITS:</strong> Randomly select D3 opposition players that are playing this game. The selected players gain the Loner (2+) Trait for the duration of the game.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>7</strong></td>
		<td><strong>GREASY CLEATS:</strong> Randomly select one opposition player that is playing this game. The selected player reduces their MA by 1 (to a minimum of 1) for the duration of the game.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>8</strong></td>
		<td><strong>BLESSING OF NUFFLE:</strong> Randomly select one player on your team that is playing this game. The selected player gains the Pro Skill for the duration of the game.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>9</strong></td>
		<td><strong>MOLES UNDER THE PITCH:</strong> Opposition players apply a -1 modifier to the roll when attempting to Rush.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>10</strong></td>
		<td><strong>PERFECT PASSING:</strong> Any player on your team that makes a Completion will earn 2 SPP rather than the usual 1.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>11</strong></td>
		<td><strong>DAZZLING CATCHING:</strong> Any player on your team that successfully Catches the ball as a result of a Pass Action will earn 1 SPP.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>12</strong></td>
		<td><strong>FAN INTERACTION:</strong> If an opposition player suffers a Casualty as a result of being Pushed into the Crowd, the player that pushed them into the crowd will earn 2 SPP.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>13</strong></td>
		<td><strong>FOULING FRENZY:</strong> Any player on your team that causes a Casualty as a result of a Foul Action will earn 2 SPP.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>14</strong></td>
		<td><strong>THROW A ROCK:</strong> Once per game, at the start of any of your Turns before any players are activated, you may randomly select one opposition player on the pitch and roll a D6. On a 4+, an angry fan throws a rock and the selected player is immediately Knocked Down.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>15</strong></td>
		<td><strong>UNDER SCRUTINY:</strong> Any opposition player that performs a Foul Action will automatically be Sent-off if they break armour, regardless of whether a natural double is rolled.</td>
		</tr>
		<tr style="height: 15.0pt;">
		<td style="height: 15pt; text-align: center;" align="right" height="20"><strong>16</strong></td>
		<td><strong>INTENSIVE TRAINING:</strong> Randomly select one player on your team that is playing this game. The selected player gains a single Primary Skill of your choice for the duration&nbsp;of&nbsp;the&nbsp;game.</td>
		</tr>
		</tbody>
		</table>
		</div>
        </div>
        </div>
    </div>
    <?php
}
/*************************
 *  About
 *************************/
 
 /*
 All about page variables located in header.php e.g. OBBLM_VERSION and Credits
 */

function sec_about() {
    global $lng, $credits, $naflmcredits;
    title("About NAFLM");
    HTMLOUT::dnt();
    ?>
    <br>
    <p>
        <b><h1>NAFLM version <?php echo NAFLM_VERSION; ?> / Content version <?php echo CONTENT_VERSION; ?></h1></b>
		This version of NAFLM last released on the <?php echo NAFLM_BUILD_DATE; ?>.<br><br>
		Updated game data was sourced from <?php echo CONTENT_DETAIL; ?> and was current at <?php echo CONTENT_DATE; ?>.
		<br><br>
        This software was based on the original OBBLM software, and was developed based on this BB2016 (now outdated) fork <a href="https://github.com/TheNAF/naflm">TheNAF/naflm fork</a> by <?php $lc = array_pop($naflmcredits); echo implode(', ', $naflmcredits)." and $lc"; ?>.
        <br><br>
		The last BB2020 version can still be found at <a href="https://github.com/The-NAF/NAFLM-2020">https://github.com/The-NAF/NAFLM-2020</a>, last updated by Val Catella, but is now discontinued/deprecated, and will no longer be updated.<br><br>
		The latest version of NAFLM 2025 can be found at <a href="https://github.com/The-NAF/NAFLM-2025">https://github.com/The-NAF/NAFLM-2025</a>, updated by Val Catella.<br><br>
		<h1>OBBLM</h1>
		<b>This NAFLM build is based on OBBLM version <?php echo OBBLM_VERSION; ?></b>
        Online Blood Bowl League Manager is an online game management system for Game Workshop's board game Blood Bowl.<br><br>    
        The authors of the original OBBLM program are
        <ul>
            <li> <a href="http://www.nicholasmr.dk/">Nicholas Mossor Rathmann</a>
            <li> <a href="http://www.mercuryvps.com">William Leonard</a>
            <li> Niels Orsleff Justesen</a>
        </ul>
        <br>
        With special thanks to <?php $lc = array_pop($credits); echo implode(', ', $credits)." and $lc"; ?>.<br><br>
		<br>
		<br>
        OBBLM consists of valid HTML 4.01 transitional document type pages.
        <br><br>
        <img src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01 Transitional" height="31" width="88">
        <br><br>
        <b>Modules loaded:</b><br>
        <?php
        $mods = array();
        foreach (Module::getRegistered() as $modname) {
            list($author,$date,$moduleName) = Module::getInfo($modname);
            $mods[] = "<i>$moduleName</i> ($author, $date)";
        }
        echo implode(', ', $mods);
        ?>
    </p>

    <?php 
    title("OBBLM Hosting");
    echo 'Please visit <a href="http://www.mercuryvps.com">Mercury VPS</a> and click on the OBBLM tab to get started.';
    
    title("Documentation");
    echo "See the <a TARGET='_blank' href='".DOC_URL."'>OBBLM documentation wiki</a>";
    
    ?>

    <?php title("Disclaimer");?>
    <p>
        By installing and using this software you hereby accept and understand the following disclaimer
        <br><br>
        <b>This web site is completely unofficial and in no way endorsed by Games Workshop Limited.</b>
        <br><br>
        Bloodquest, Blood Bowl, the Blood Bowl logo, The Blood Bowl Spike Device, Chaos, the Chaos device, the Chaos logo, Games Workshop, Games Workshop logo, Nurgle, the Nurgle device, Skaven, Tomb Kings, 
        and all associated marks, names, races, race insignia, characters, vehicles, locations, units, illustrations and images from the Blood Bowl game, the Warhammer world are either ®, TM and/or © Games Workshop Ltd 2000-2025, 
        variably registered in the UK and other countries around the world. Used without permission. No challenge to their status intended. All Rights Reserved to their respective owners.
        <br><br>
        Fumbbl icons are used with permission.  Credits: harvestmouse, garion, christer, whatball.
    </p>

    <?php title("License");?>
    <p>
        Copyright (c) Niels Orsleff Justesen and Nicholas Mossor Rathmann 2007-2020 All Rights Reserved.
        <br><br>
        OBBLM is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 3 of the License, or
        (at your option) any later version.
        <br><br>
        OBBLM is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.
        <br><br>
        NAFLM is a fork of the original OBBLM programme and inherits all license and copyrights of the original.
        <br><br>
        You should have received a copy of the GNU General Public License
        along with this program.  If not, see http://www.gnu.org/licenses/.
    </p>
    <?php
}

function sec_requestleague() {
    global $coach, $settings, $db_user, $db_passwd;
    
    title("Request League");
    
    if (!isset($_SESSION['logged_in'])) {
        echo 'You must <a href="handler.php?type=registration"><b>register</b></a> as a League Commissioner before you can request a league.';
        return;
    }
    
    if(isset($_POST['requesting_league'])) {
        $to = Email::getAdministratorEmails();
        echo '' . $to . '';
        $subject = 'Request to create a league on TheNAF OBBLM.';
        $message = 'Commissioner Username: ' . $coach->name .
            '\n Full League Name: ' . $_POST['full_league_name'] .
            '\n Short League Name: ' . $_POST['short_league_name'] .
            '\n League City, State, Province: ' . $_POST['league_city_state_province'] .
            '\n League Country: ' . $_POST['league_country'];
        $headers = 'From: '.$_POST['email']. "\r\n" .
                   'Reply-To: '.$_POST['email']. "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        if (!mail($to, $subject, $message, $headers)) {
            ?>
            <div class="boxWide">
                <div class="boxTitle3">There was an error sending your message!</div>
                <div class="boxBody">
                    So you should try your favourite e-mail client instead.
                    <div class="quote">
                        <div><strong>To: </strong><?php echo $to; ?></div>
                        <div><strong>Subject: </strong><?php echo $subject; ?></div>
                        <div><strong>Body: </strong><?php echo $message; ?></div>
                    </div>
                </div>
            <?php
        } else {
            ?>
            Your message was sent successfully. An administrator will get back to you soon!
            <?php
        }
    }
    else {
        ?>
        <form method="POST" id="RequestLeagueForm">
            <input type="hidden" name="section" value="requestleague" />
            <input type="hidden" name="requesting_league" value="true" />
            <div class="input-item"><label>Your e-mail: </label><input type="text" name="email" /></div>
            <div class="input-item"><label>Full League Name: </label><input type="text" name="full_league_name" /></div>
            <div class="input-item"><label>Short League Name: </label><input type="text" name="short_league_name" /></div>
            <div class="input-item"><label>League City, State, Province: </label><input type="text" name="league_city_state_province" /></div>
            <div class="input-item"><label>League Country: </label><input type="text" name="league_country" /></div>
            <input type="submit" value="Send" />
        </form>
        <?php
    }
}
