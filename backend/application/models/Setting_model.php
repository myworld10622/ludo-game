<?php

use phpDocumentor\Reflection\DocBlock\Tags\Return_;

class Setting_model extends MY_Model
{
    public function Setting($select='*')
    {
        $this->db->select($select);
        $this->db->from('tbl_setting');
        $Query = $this->db->get();
        return $Query->row();
    }

    public function admin()
    {
        // $this->db->select($select);
        $this->db->from('tbl_admin');
        $Query = $this->db->get();
        return $Query->row();
    }
    public function getAllAdminCoin()
    {
        $this->db->select('*');
        $this->db->from('tbl_direct_admin_profit_statement');
        $this->db->order_by('id','desc');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function GetPermission($select='*')
    {
        $this->db->select($select);
        $this->db->from('tbl_games_on_off');
        $this->db->join('tbl_setting', 'tbl_setting.id=tbl_games_on_off.id','left');
        $Query = $this->db->get();
        return $Query->row();
    }

    public function update($data)
    {
        if ($this->db->update('tbl_setting', $data)) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function update_jackpot_amount($jackpot_coin)
    {
        $this->db->set('jackpot_coin', 'jackpot_coin'.$jackpot_coin, false);
        if ($jackpot_coin<0) {
            $this->db->set('jackpot_status', 0);
        }
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function updateSpinBucket($coins)
    {
        $this->db->set('spin_bucket', 'spin_bucket'.$coins, false);
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function updateAdminCoin($coins)
    {
        $this->db->set('admin_coin', 'admin_coin+'.$coins, false);
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function DeductAviatorBucket($coins)
    {
        $this->db->set('aviator_bucket',0);
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function update_jackpot_status($jackpot_status)
    {
        $this->db->set('jackpot_status', $jackpot_status);
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function update_rummy_bot_status($bot_status)
    {
        $this->db->set('robot_rummy', $bot_status);
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function update_teenpatti_bot_status($bot_status)
    {
        $this->db->set('robot_teenpatti', $bot_status);
        if ($this->db->update('tbl_setting')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function AllTipLog()
    {
        $Query = $this->db->select('tbl_tip_log.*,tbl_users.name')
            ->from('tbl_tip_log')
            ->join('tbl_users', 'tbl_users.id=tbl_tip_log.user_id')
            ->get();
        return $Query->result();
    }

    public function AllCommissionLog()
    {
        $Query = $this->db->select('tbl_game.*,tbl_users.name')
            ->from('tbl_game')
            ->join('tbl_users', 'tbl_users.id=tbl_game.winner_id')
            ->where('tbl_game.winner_id!=', 0)
            ->where('tbl_game.amount!=', 0)
            ->order_by('tbl_game.id', 'DESC')
            ->get();
        return $Query->result();
    }
    public function AllAdminCommisionLog()
    {
        $Query = $this->db->select('tbl_statement.*')
            ->from('tbl_statement')
            ->where('tbl_statement.admin_commission!=', 0)
            ->order_by('tbl_statement.id', 'DESC')
            ->get();
        return $Query->result();
    }

    public function UpdateGamesStatus($column, $type)
    {
        $this->db->set($column, $type);
        if ($this->db->update('tbl_games_on_off')) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function GetAllLogs()
    {
        ## Read value
        //    $draw = $postData['draw'];
        //    $start = $postData['start'];
        //    $rowperpage = $postData['length']; // Rows display per page
        //    $columnIndex = $postData['order'][0]['column']; // Column index
        //    $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        //    $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        //    $searchValue = $postData['search']['value']; // Search value
        $sql = 'SELECT main_table.*, tbl_users.wallet as user_wallet FROM (
    SELECT 
    "Andar Bahar" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_ander_baher
    UNION
    SELECT 
    "Aviator" as game,id as reff_id,id as bet_id,user_id as id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_aviator_bet
    UNION
    SELECT 
    "Dragon & Tiger" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_dragon_tiger
    UNION
    SELECT 
    "Baccarat" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_baccarat
    UNION
    SELECT 
    "Seven Up Down" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_seven_up
    UNION
    SELECT 
    "Car Roulette" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_car_roulette
    UNION
    SELECT 
    "Color Predection" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_color_prediction
    UNION
    SELECT 
    "Animal Roulette" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_animal_roulette
    UNION
    SELECT 
    "Head Tail" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_head_tail
    UNION
    SELECT 
    "Red Vs Black" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_red_black
    UNION
    SELECT 
    "Dragon & Tiger" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_dragon_tiger
    UNION
    SELECT 
    "Roulette" as game,id as id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_roulette_bet
    UNION
    SELECT 
    "Jhandi Munda" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_jhandi_munda
    UNION
    SELECT 
    "Poker" as game,id as reff_id,id as bet_id,winner_id as id,user_winning_amt as winning_amount,admin_winning_amt as comission_amount,added_date,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_poker
    UNION
    SELECT 
    "Teen Patti Win" as game,id as reff_id,id as bet_id,winner_id as id,user_winning_amt as winning_amount,admin_winning_amt as comission_amount,updated_date as added_date,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_game
    UNION
    SELECT 
    "JackPot" as game,id as reff_id,id as bet_id,id,winning_amount,comission_amount,added_date,user_amount, 1 as is_game
    FROM tbl_jackpot
    UNION
    SELECT 
    "Rummy Win" as game,id as reff_id,id as bet_id,winner_id as id,user_winning_amt as winning_amount,admin_winning_amt as comission_amount,updated_date as added_date,user_winning_amt as user_amount, 1 as is_game
    FROM tbl_rummy
    UNION
    SELECT 
    "Deal Rummy Win" as game,id as reff_id,id as bet_id,winner_id as id,user_amount as winning_amount,commission_amount,updated_date as added_date,winning_amount as user_amount, 1 as is_game
    FROM tbl_rummy_deal_table
    UNION
    SELECT 
    "Pool Rummy Win" as game,id as reff_id,id as bet_id,winner_id as id,user_amount as winning_amount,commission_amount,updated_date as added_date,winning_amount as user_amount, 1 as is_game
    FROM tbl_rummy_pool_table
    UNION
    SELECT 
    "Ludo" as game,id as reff_id,id as bet_id,winner_id as id,user_winning_amt as winning_amount,admin_winning_amt as comission_amount,added_date,user_winning_amt as user_amount, 1 as is_game  
    from tbl_ludo
    ) as main_table join tbl_users on tbl_users.id=main_table.id where tbl_users.isDeleted=0 AND main_table.comission_amount !=0 ';

        //    if ($searchValue) {
        //     $sql .= ' and game like "%' . $searchValue . '%"';
        // }
        $sql.=' order by added_date desc limit 100';
        // $sql.=' order by '.$columnName.' '.$columnSortOrder;
        // $sql.=' limit '.$start.','.$rowperpage.'';
        $query=$this->db->query($sql);
        //echo $this->db->last_query();
        // $this->db->order_by($columnName, $columnSortOrder);
        return $records = $query->result();
    
    }
}