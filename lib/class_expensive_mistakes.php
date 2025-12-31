<?php

class ExpensiveMistakes
{
    // Treasury threshold
    const THRESHOLD_MIN = 100000;  // 100k
    
    // Expensive Mistakes table
    public static $EM_TABLE = array(
        1 => array(
            '100k-195k' => 'Minor Incident',
            '200k-295k' => 'Minor Incident',
            '300k-395k' => 'Major Incident',
            '400k-495k' => 'Major Incident',
            '500k-595k' => 'Catastrophe',
            '600k+'     => 'Catastrophe'
        ),
        2 => array(
            '100k-195k' => 'Crisis Averted',
            '200k-295k' => 'Minor Incident',
            '300k-395k' => 'Minor Incident',
            '400k-495k' => 'Major Incident',
            '500k-595k' => 'Major Incident',
            '600k+'     => 'Catastrophe'
        ),
        3 => array(
            '100k-195k' => 'Crisis Averted',
            '200k-295k' => 'Crisis Averted',
            '300k-395k' => 'Minor Incident',
            '400k-495k' => 'Minor Incident',
            '500k-595k' => 'Major Incident',
            '600k+'     => 'Major Incident'
        ),
        4 => array(
            '100k-195k' => 'Crisis Averted',
            '200k-295k' => 'Crisis Averted',
            '300k-395k' => 'Crisis Averted',
            '400k-495k' => 'Minor Incident',
            '500k-595k' => 'Minor Incident',
            '600k+'     => 'Major Incident'
        ),
        5 => array(
            '100k-195k' => 'Crisis Averted',
            '200k-295k' => 'Crisis Averted',
            '300k-395k' => 'Crisis Averted',
            '400k-495k' => 'Crisis Averted',
            '500k-595k' => 'Minor Incident',
            '600k+'     => 'Minor Incident'
        ),
        6 => array(
            '100k-195k' => 'Crisis Averted',
            '200k-295k' => 'Crisis Averted',
            '300k-395k' => 'Crisis Averted',
            '400k-495k' => 'Crisis Averted',
            '500k-595k' => 'Crisis Averted',
            '600k+'     => 'Minor Incident'
        )
    );
    
    public static function shouldShowOption($team_id)
    {
        $team = new Team($team_id);
        return ($team->treasury >= self::THRESHOLD_MIN);
    }
    
    private static function getTreasuryBracket($treasury)
    {
        if ($treasury >= 600000) return '600k+';
        if ($treasury >= 500000) return '500k-595k';
        if ($treasury >= 400000) return '400k-495k';
        if ($treasury >= 300000) return '300k-395k';
        if ($treasury >= 200000) return '200k-295k';
        return '100k-195k';
    }
    
    public static function performRoll($team_id)
    {
        $team = new Team($team_id);
        $treasury_before = $team->treasury;
        
        if ($treasury_before < self::THRESHOLD_MIN) {
            return array(
                'success' => false,
                'error' => 'Treasury below 100k threshold'
            );
        }
        
        // Roll D6
        $d6_roll = mt_rand(1, 6);
        
        // Determine bracket
        $bracket = self::getTreasuryBracket($treasury_before);
        
        // Get outcome
        $outcome = self::$EM_TABLE[$d6_roll][$bracket];
        
        // Calculate loss
        $loss_result = self::calculateLoss($outcome, $treasury_before);
        $loss_amount = $loss_result['amount'];
        $additional_rolls = $loss_result['rolls'];
        
        // Apply loss to treasury
        $team->dtreasury(-$loss_amount);
        
        $treasury_after = $treasury_before - $loss_amount;
        
        // Update team stats
        if (function_exists('SQLTriggers') && class_exists('SQLTriggers')) {
            SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('obj' => T_OBJ_TEAM, 'id' => $team_id));
        }
        
        return array(
            'success' => true,
            'd6_roll' => $d6_roll,
            'bracket' => $bracket,
            'outcome' => $outcome,
            'treasury_before' => $treasury_before,
            'treasury_after' => $treasury_after,
            'loss_amount' => $loss_amount,
            'additional_rolls' => $additional_rolls
        );
    }
    
    private static function calculateLoss($outcome, $treasury)
    {
        $amount = 0;
        $rolls = '';
        
        switch ($outcome) {
            case 'Crisis Averted':
                // No loss
                $amount = 0;
                $rolls = 'No loss - Crisis Averted!';
                break;
                
            case 'Minor Incident':
                // D3 × 10k loss
                $d3 = mt_rand(1, 3);
                $amount = $d3 * 10000;
                $rolls = "D3 roll: $d3 (lose " . ($amount/1000) . "k)";
                break;
                
            case 'Major Incident':
                // Lose half of treasury, rounded down to nearest 5k
                $half = floor($treasury / 2);
                $amount = floor($half / 5000) * 5000;
                $rolls = "Half of " . ($treasury/1000) . "k = " . ($half/1000) . "k, rounded to " . ($amount/1000) . "k";
                break;
                
            case 'Catastrophe':
                // Lose all treasury except 2D6 × 10k
                $d6_1 = mt_rand(1, 6);
                $d6_2 = mt_rand(1, 6);
                $keep = ($d6_1 + $d6_2) * 10000;
                $amount = max(0, $treasury - $keep);
                $rolls = "2D6 roll: $d6_1 + $d6_2 = " . ($d6_1 + $d6_2) . " (keep " . ($keep/1000) . "k, lose " . ($amount/1000) . "k)";
                break;
        }
        
        return array(
            'amount' => $amount,
            'rolls' => $rolls
        );
    }
}