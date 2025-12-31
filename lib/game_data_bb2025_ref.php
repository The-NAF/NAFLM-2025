<?php
/* ******************************************************************************************************** **  
    A single, unified Game Data file to replace:
        game_data.php
        game_data_lrb6x.php
        game_data_lrb6.php
        game_data_bb16.php
		game_data_bb2016.php
    IMPORTANT!
    Whenever the values of this file are changed you must run the 
    "Synchronise the PHP-stored BB game data" under "DB maintenance" from the "Admin -> Core panel" menu.
** ******************************************************************************************************** */
/*  Races  **************************************************************************************************/
// Race definitions
//Blood Bowl Teams
define('T_RACE_AMAZON',       	0);
define('T_RACE_CHAOS',        	1);
define('T_RACE_CHAOS_DWARF',  	2);
define('T_RACE_DARK_ELF',     	3);
define('T_RACE_DWARF',        	4);
define('T_RACE_ELF',          	5);
define('T_RACE_GOBLIN',       	6);
define('T_RACE_HALFLING',     	7);
define('T_RACE_HIGH_ELF',     	8);
define('T_RACE_HUMAN',        	9);
define('T_RACE_KHEMRI',      	10);
define('T_RACE_LIZARDMAN',   	11);
define('T_RACE_ORC',         	12);
define('T_RACE_NECROMANTIC', 	13);
define('T_RACE_NORSE',       	14);
define('T_RACE_NURGLE',      	15);
define('T_RACE_OGRE',        	16);
define('T_RACE_UNDEAD',      	17);
define('T_RACE_VAMPIRE',     	18);
define('T_RACE_SKAVEN',      	19);
define('T_RACE_WOOD_ELF',    	20);
define('T_RACE_CHAOS_PACT',  	21);
define('T_RACE_SLANN',       	22);
define('T_RACE_UNDERWORLD',  	23);
define('T_RACE_OLDWORLD',    	24);
define('T_RACE_SNOTLING',   	25);
define('T_RACE_BLACK_ORC',   	26);
define('T_RACE_IMPERIAL',  		27);
define('T_RACE_KHORNE',  		28);
define('T_RACE_GNOME',  		29);
define('T_RACE_BRETONNIAN',  	30);
//following reserved for GW future releases of teams of legend
//define('T_RACE_HIGH_ELVES',  	31);


}
/*  Special rules  *************************************************************************************** **
    Note: Never change Special Rules ids after creating a team in OBBLM.
** ******************************************************************************************************** */
$specialrulesarray    = array (
	# Race leagues and special rules
    'L'    => array (
        0  => 'Badlands Brawl',
        1  => 'Chaos Clash',
        2  => 'Elven Kingdoms League',
        3  => 'Halfling Thimble Cup',
        4  => 'Lustrian Superleague',
        5  => 'Old World Classic',
        6  => 'Sylvanian Spotlight',
        7  => 'Underworld Challenge',
        8  => 'Woodlands League',
        9  => 'Worlds Edge Superleague',
	),
    'R'    => array (       
        10  => 'Brawlin Brutes',
		11  => 'Bribery and Corruption',
		12  => 'Favoured of...',
        13  => 'Favoured of Chaos Undivided',
        14  => 'Favoured of Hashut',
        15  => 'Favoured of Khorne',
        16  => 'Favoured of Nurgle',
        17  => 'Favoured of Slaanesh',
        18  => 'Favoured of Tzeentch',
        19  => 'Low Cost Linemen',
        20  => 'Masters of Undeath',
        21  => 'Swarming',
        22  => 'Team Captain',
	),
    'S'    => array (
	# Star Player special rules
        30 => 'Sneakiest of the Lot',
        31 => 'Reliable',
        32 => 'Mesmerizing Dance',
        33 => 'Frenzied Rush',
        34 => 'Shot to Nothing',
        35 => 'Two for One',
        36 => 'Incorporeal',
        37 => 'Consummate Professional',
        38 => 'Slayer',
        39 => 'Trecherous',
        40 => 'Old Pro',
        41 => 'Indomitable',
        42 => 'Lord of Chaos',
        43 => 'Crushing Blow',
        44 => 'The Ballista',
        45 => 'Burst of Speed',
        46 => 'Ram',
        47 => 'Strong Passing Game',
        48 => 'Wisdom of the White Dwarf',
        49 => 'Excuse me are you a Zoat?',
        50 => 'Brutal Block',
        51 => 'Savage Mauling',
        52 => 'Ghostly Flames',
		53 => 'Blind Rage',
		54 => 'I\'ll Be Back',
		55 => 'Gored by the Bull',
		56 => 'Fury of the Blood God',
		57 => 'Maximum Carnage',
		58 => 'Blast It!',
		59 => 'Whirling Dervish',
		60 => 'Kaboom!',
		61 => 'Raiding Party',
		62 => 'Pump Up the Crowd',
		63 => 'Beer Barrel Bash!',
		64 => 'Look Into My Eyes',
		65 => 'Baleful Hex',
		66 => 'Primal Savagery',
		67 => 'Halfling Luck',
		68 => 'All You Can Eat',
		69 => 'A Sneaky Pair',
		70 => 'Putrid Regurgitation',
		71 => 'Thinking Man\'s Troll',
		72 => 'Kick \'em while they\'re down!',
		73 => 'Yoink!',
		74 => 'Watch out!',
		75 => 'Tasty Morsel',
		76 => 'Dwarfen Scourge',
		77 => 'Star of the Show',
		78 => 'Master Assassin',
		79 => 'Bounding Leap',
		80 => 'Catch of the Day',
		81 => 'Then I Started Blastin\'!',
		82 => 'Unstoppable Momentum',
		83 => 'Toxin Connoisseur',
		84 => 'Swift as the Breeze',
		85 => 'The Flashing Blade',
		86 => 'Savage Blow',
		87 => 'Vicious Vines',
		88 => 'Furious Outburst',
		89 => 'Quick Bite',
		90 => 'Black Ink',
		91 => 'Dwarven Grit',
    ),
);
// Create specialrule ID index (key:val = id:specialrule_name).
$specialruleididx = array();
foreach ($specialrulesarray as $rcat => $sprules) {
    foreach ($sprules as $rid => $rname)
        $specialruleididx[$rid] = $rname;
}
/*  Special rules  *************************************************************************************** **
    Note: Never change Special Rules ids after creating a team in OBBLM.
** ******************************************************************************************************** */
$playerkeywordsarray    = array (
	# Race leagues and special rules
    'K'    => array (
        0  => 'Animal',
        1  => 'Beastman',
        2  => 'Construct',
        3  => 'Dwarf',
        4  => 'Elf',
        5  => 'Ghoul',
        6  => 'Gnoblar',
        7  => 'Gnome',
        8  => 'Goblin',
        9  => 'Halfling',
        10 => 'Human',
        11 => 'Lizardman',
        12 => 'Minotaur',
        13 => 'Ogre',
        14 => 'Orc',
        15 => 'Skaven',
        16 => 'Skeleton',
        17 => 'Slann',
        18 => 'Snotling',
        19 => 'Spawn',
        20 => 'Thrall',
        21 => 'Treeman',
        22 => 'Troll',
        23 => 'Undead',
        24 => 'Vampire',
        25 => 'Werewolf',
        26 => 'Wraith',
        27 => 'Yhetee',
        28 => 'Zombie',
        29 => 'Dryad',
        30 => 'Snakeman',
        31 => 'Squirrel',
        32 => 'Skink',
        33 => 'Spite',
        34 => 'Zoat',
    ),
    'P'    => array (       
        50  => 'Big Guy',
		51  => 'Blitzer',
		52  => 'Blocker',
        53  => 'Catcher',
        54  => 'Lineman',
        55  => 'Runner',
        56  => 'Special',
        57  => 'Thrower',
	),
);

   ),
);
/*  Paired Stars  ***************************************************************************************** */
$starpairs = array (
    // Parent => Child
    -15  => -16,
    -29 => -30,
    -54 => -55
);
/*  SPP  ************************************************************************************************** */
$sparray = array (    
    'Rookie'     => array (
        'CSPP'   => 0,
        'SPP'    => 0,
        'SPR'    => 0    
    ),
    'Experienced'    => array (
        'CSPP'   => 6,
        'SPP'    => 3,
        'SPR'    => 1
    ),
    'Veteran'    => array (
        'CSPP'   => 8,
        'SPP'    => 4,
        'SPR'    => 2
    ),
    'Emerging Star'    => array (
        'CSPP'   => 12,
        'SPP'    => 6,
        'SPR'    => 3    
    ),
    'Star'    	 => array (
        'CSPP'   => 16,
        'SPP'    => 8,
        'SPR'    => 4    
    ),
    'Super Star' => array (
        'CSPP'   => 20,
        'SPP'    => 10,
        'SPR'    => 5    
    ),
    'Legend'     => array (
        'CSPP'   => 30,
        'SPP'    => 15,
        'SPR'    => 6
    )
);
/*  Skills  *********************************************************************************************** **
    ID => "skill name"
    Note jumps in ID numbering, this allowed future skills to be added still maintaining sensible ordering.
** ******************************************************************************************************** */
$skillarray    = array (
    'G'    => array (
        1  => 'Block',
        2  => 'Dauntless',
        4  => 'Fend',
        5  => 'Frenzy',
        6  => 'Kick',
        9  => 'Pro',
        11 => 'Strip Ball',
        12 => 'Sure Hands',
        13 => 'Tackle',
        14 => 'Wrestle',
        16 => 'Steady Footing',
        17 => 'Taunt',
    ),
    'A'    => array (
        20 => 'Catch',
        21 => 'Diving Catch',
        22 => 'Diving Tackle',
        23 => 'Dodge',
        24 => 'Jump Up',
        25 => 'Leap',
        26 => 'Sidestep',
        28 => 'Sprint',
        29 => 'Sure Feet',
        30 => 'Defensive',
        31 => 'Safe Pair of Hands',
        128 => 'Hit and Run',
    ),
    'D'    => array (
        3  => 'Dirty Player',
        27 => 'Sneaky Git',
        10 => 'Shadowing',
        49 => 'Fumblerooski',
        56 => 'Pile Driver',
        19 => 'Eye Gouge',
        32 => 'Lethal Flight',
        33 => 'Lone Fouler',
        34 => 'Put the Boot In',
        35 => 'Quick Foul',
        36 => 'Saboteur',
        37 => 'Violent Innovator',
    ),
    'P'    => array (
        40 => 'Accurate',
        41 => 'Dump-Off',
        42 => 'Hail Mary Pass',
        43 => 'Leader',
        44 => 'Nerves of Steel',
        45 => 'Pass',
        46 => 'Safe Pass',
        47 => 'Cannoneer',
        48 => 'Cloud Burster',
		7  => 'Give and Go',
		8  => 'On the Ball',
		18  => 'Punt',
    ),
    'S'    => array (
        50 => 'Break Tackle',
        51 => 'Grab',
        52 => 'Guard',
        53 => 'Juggernaut',
        54 => 'Mighty Blow',
        55 => 'Multiple Block',
        57 => 'Stand Firm',
        58 => 'Strong Arm', //do not change this id (58), see Throw Team-mate skill below (110)
        59 => 'Thick Skull',
        60 => 'Arm Bar',
        61 => 'Brawler',
        63 => 'Bullseye',
    ),
    'M'    => array (
        70 => 'Big Hand',
        71 => 'Claws',
        72 => 'Disturbing Presence',
        73 => 'Extra Arms',
        74 => 'Foul Appearance',
        75 => 'Horns',
        76 => 'Prehensile Tail',
        77 => 'Tentacles',
        78 => 'Two Heads',
        79 => 'Very Long Legs',
        80 => 'Iron Hard Skin',
        81 => 'Monstrous Mouth',
    ),
    # Extraordinary/Traits
    'E'    => array (
        //15 => 'Dirty Player plus2',
        //62 => 'Mighty Blow plus2',
        90 => 'Always Hungry',
        91 => 'Ball & Chain',
        92 => 'Kick Team-Mate',
        93 => 'Bombardier',
        94 => 'Bone-Head',
        95 => 'Chainsaw',
        96 => 'Decay',
        97 => 'Projectile Vomit',
        98 => 'Hypnotic Gaze',
        99 => 'Loner 4plus',
        100 => 'No Ball',
        101 => 'Plague Ridden',
        102 => 'Really Stupid',
        103 => 'Regeneration',
        104 => 'Right Stuff',
        105 => 'Secret Weapon',
        106 => 'Stab',
        107 => 'Swarming',
        108 => 'Stunty',
        109 => 'Take Root',
        110 => 'Throw Team-Mate', //do not change this id as system is hardcoded to only allow Strong Arm skill id (58) if a player has Throw Team-mate id (110)
        111 => 'Titchy',
        112 => 'Animal Savagery',
        113 => 'Animosity All',
        114 => 'Unchannelled Fury',
        115 => 'Timmm-ber',
        116 => 'Pogo',
        117 => 'Swoop',
        118 => 'Low Cost Linemen', //do not change this id as system is hardcoded to discount player cost from TV for this skill id (118)
        119 => 'Loner 3plus',
        120 => 'Loner 5plus',
        121 => 'Animosity Goblin',
        122 => 'Animosity Orc Linemen',
        123 => 'Animosity Big Un',
        124 => 'Animosity Dwarf & Halfling',
        125 => 'Animosity Dwarf & Human',
        126 => 'Drunkard',
        127 => 'Pick-me-up',
        129 => 'Bloodlust 2plus',
        130 => 'Bloodlust 3plus',
        131 => 'Trickster',
        132 => 'My Ball',
        133 => 'Breathe Fire',
        134 => 'Insignificant',
        135 => 'Hatred Animal',
        136 => 'Hatred Beastman',
        137 => 'Hatred Construct',
        138 => 'Hatred Dwarf',
        139 => 'Hatred Elf',
        140 => 'Hatred Ghoul',
        141 => 'Hatred Gnoblar',
        142 => 'Hatred Gnome',
        143 => 'Hatred Goblin',
        144 => 'Hatred Halfling',
        145 => 'Hatred Human',
        146 => 'Hatred Lizardman',
        147 => 'Hatred Minotaur',
        148 => 'Hatred Ogre',
        149 => 'Hatred Orc',
        150 => 'Hatred Skaven',
        151 => 'Hatred Skeleton',
        152 => 'Hatred Snotling',
        153 => 'Hatred Spawn',
        154 => 'Hatred Thrall',
        155 => 'Hatred Treeman',
        156 => 'Hatred Troll',
        157 => 'Hatred Undead',
        158 => 'Hatred Vampire',
        159 => 'Hatred Werewolf',
        160 => 'Hatred Wraith',
        161 => 'Hatred Yhetee',
        162 => 'Hatred Zombie',
        163 => 'Loner 2plus',
        164 => 'Unsteady',
        165 => 'Hatred Big Guy'
    ),
);
// Create skill ID index (key:val = id:skill_name).
$skillididx = array();
foreach ($skillarray as $grp => $skills) {
    foreach ($skills as $id => $name)
        $skillididx[$id] = $name;
}
$IllegalSkillCombinations = array(
// Syntax:
// "If player has this skill" => array("player may not have this skill", "or these skills", ...),
	5   => array(51),
    51   => array(5),
    91   => array(22, 5, 7, 8, 10),
    116  => array(25),	
    100  => array(7, 12, 20, 21, 31, 40, 41, 42, 44, 45, 46, 47, 48, 49),
);
/*  Necromancer  ****************************************************************************************** *
    Instead of purchasing an Apothecary, Necromantic and Undead teams use the services of a Necromancer. 
    This means these teams may also receive free zombies (Masters of undeath special team rule).
    Khemri, Necromantic, Nurgle and Undead teams may not purchase or use an Apothecary.
    Nurgles players with Nurgle's Rot may raise rotters. Really, this only applies for _players_ (not the
    race) with the skill "Plague Ridden".
** ******************************************************************************************************** */
$racesDungeonBowl = array(T_RACE_COLLEGE_FIRE,T_RACE_COLLEGE_SHADOW,T_RACE_COLLEGE_METAL,T_RACE_COLLEGE_LIGHT,T_RACE_COLLEGE_DEATH,T_RACE_COLLEGE_BEASTS,T_RACE_COLLEGE_HEAVENS,T_RACE_COLLEGE_LIFE);
$racesHasNecromancer = array(T_RACE_NECROMANTIC,T_RACE_UNDEAD,T_RACE_SEVENS_NECROMANTIC, T_RACE_SEVENS_UNDEAD);
$racesNoApothecary = array_merge($racesHasNecromancer, $racesDungeonBowl, array(T_RACE_KHEMRI,T_RACE_NURGLE));
$racesMayRaiseRotters = array(T_RACE_NURGLE,T_RACE_SEVENS_NURGLE);
$racesMayRaiseThrall = array(T_RACE_VAMPIRES,T_RACE_SEVENS_VAMPIRE); //AKA Vampire Lord Special Rule
/*  Inducements  ****************************************************************************************** */
