<?php

class IconRoulette_model extends MY_Model
{
    public function getRoom($RoomId = '', $user_id = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_icon_roulette_map');
        $this->db->where('isDeleted', false);
        if (!empty($RoomId)) {
            $this->db->where('id', $RoomId);
        }
        $this->db->order_by('id', 'asc');
        $Query = $this->db->get();

        $this->db->set('animal_roulette_room_id', $RoomId); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $Query->result();
    }

    public function leave_room($user_id = '')
    {
        $this->db->set('animal_roulette_room_id', ''); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $this->db->last_query();
    }

    public function getRoomOnline($RoomId)
    {
        $Query = $this->db->query('SELECT COUNT(`id`) as online FROM `tbl_icon_roulette_bet` WHERE `icon_roulette_id` = (SELECT `id` FROM `tbl_icon_roulette` WHERE `room_id`=' . $RoomId . ' ORDER BY `id` DESC LIMIT 1)');
        return $Query->row()->online;
    }

    public function getRoomOnlineUser($RoomId)
    {
        $Query = $this->db->query('SELECT * FROM `tbl_users`  WHERE animal_roulette_room_id = ' . $RoomId);
        return $Query->result();
    }

    public function getActiveGameOnTable($RoomId = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_icon_roulette');
        if (!empty($RoomId)) {
            $this->db->where('room_id', $RoomId);
        }
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GetCards($limit = '')
    {
        $this->db->from('tbl_cards');
        $this->db->where('cards!=', 'JKR1');
        $this->db->where('cards!=', 'JKR2');
        $this->db->limit($limit);
        $this->db->order_by('id', 'RANDOM');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function GetGameCards($game_id)
    {
        $this->db->from('tbl_icon_roulette_map');
        $this->db->where('icon_roulette_id', $game_id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function CreateMap($icon_roulette_id, $card)
    {
        $ander_data = ['icon_roulette_id' => $icon_roulette_id, 'card' => $card, 'added_date' => date('Y-m-d H:i:s')];
        $this->db->insert('tbl_icon_roulette_map', $ander_data);
        return $this->db->insert_id();
    }

    public function PlaceBet($bet_data)
    {
        $this->db->insert('tbl_icon_roulette_bet', $bet_data);
        return $this->db->insert_id();
    }

    public function DeleteBet($user_id, $game_id)
    {
        return $this->db->where('icon_roulette_id', $game_id)->where('user_id', $user_id)->delete('tbl_icon_roulette_bet');
    }

    public function MinusWallet($user_id, $amount)
    {
        $this->db->set('wallet', 'wallet-' . $amount, false);
        $this->db->set('todays_bet', 'todays_bet+' . $amount, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        minus_from_wallets($user_id, $amount);

        return $this->db->affected_rows();
    }

    public function AddWallet($user_id, $amount)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        $this->db->set('winning_wallet', 'winning_wallet+' . $amount, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        return $this->db->affected_rows();
    }

    public function View($id)
    {
        $this->db->from('tbl_icon_roulette');
        $this->db->where('id', $id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->row();
    }

    public function Update($data, $game_id)
    {
        $this->db->where('id', $game_id);
        $this->db->update('tbl_icon_roulette', $data);
        $GameId = $this->db->affected_rows();
        // echo $this->db->last_query();
        return $GameId;
    }

    public function ViewBet($user_id = '', $icon_roulette_id = '', $bet = '', $bet_id = '', $limit = '')
    {
        // echo $bet;
        $this->db->from('tbl_icon_roulette_bet');

        if (!empty($user_id)) {
            $this->db->where('user_id', $user_id);
        }

        if (!empty($icon_roulette_id)) {
            $this->db->where('icon_roulette_id', $icon_roulette_id);
        }

        if ($bet !== '') {
            if (!empty($bet) && is_array($bet)) {
                $this->db->where_in('bet', $bet);
            } else if (is_numeric($bet)) {
                $this->db->where('bet', $bet);
            } else {
                return array();
            }
        }

        // if ($bet !== '') {
        //     $this->db->where('bet', $bet);
        // }

        if ($bet_id != '') {
            $this->db->where('id', $bet_id);
        }

        if ($limit != '') {
            $this->db->limit($limit);
        }

        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function ViewBetOption($user_id, $icon_roulette_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('icon_roulette_id', $icon_roulette_id);
        $this->db->group_by('bet');
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get('tbl_icon_roulette_bet');
        // echo $this->db->last_query();
        return $Query->num_rows();
    }

    public function TotalBetAmount($icon_roulette_id, $bet = '')
    {
        $this->db->select('SUM(amount) as amount', false);
        $this->db->from('tbl_icon_roulette_bet');
        $this->db->where('icon_roulette_id', $icon_roulette_id);
        if ($bet !== '') {
            $this->db->where('bet', $bet);
        }
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->row()->amount;
    }

    public function MakeWinner($user_id, $bet_id, $amount, $comission, $game_id)
    {
        $admin_winning_amt = round($amount * round($comission / 100, 2), 2);
        $user_winning_amt = round($amount - $admin_winning_amt, 2);
        $this->db->set('winning_amount', $amount);
        $this->db->set('user_amount', $user_winning_amt);
        $this->db->set('comission_amount', $admin_winning_amt);
        $this->db->where('id', $bet_id);
        $this->db->update('tbl_icon_roulette_bet');

        $this->db->set('winning_amount', 'winning_amount+' . $amount, false);
        $this->db->set('user_amount', 'user_amount+' . $user_winning_amt, false);
        $this->db->set('comission_amount', 'comission_amount+' . $admin_winning_amt, false);
        $this->db->where('id', $game_id);
        $this->db->update('tbl_icon_roulette');

        $this->db->set('wallet', 'wallet+' . $user_winning_amt, false);
        $this->db->set('winning_wallet', 'winning_wallet+' . $user_winning_amt, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        log_statement(
            $user_id,
            AR,
            $user_winning_amt,
            $bet_id,
            $admin_winning_amt
        );
        return true;
    }

    public function LastWinningBet($room_id, $limit = 10)
    {
        // echo $bet;
        $this->db->from('tbl_icon_roulette');
        $this->db->where('status', 1);
        if (!empty($room_id)) {
            $this->db->where('room_id', $room_id);
        }
        if (!empty($limit)) {
            $this->db->limit($limit);
        }

        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function Create($room_id)
    {
        $ander_data = ['room_id' => $room_id, 'added_date' => date('Y-m-d H:i:s')];
        $this->db->insert('tbl_icon_roulette', $ander_data);
        return $this->db->insert_id();
    }

    public function AllCards()
    {
        $Query = $this->db->select('cards')
            ->from('tbl_cards')
            ->get();
        return $Query->result();
    }

    public function getJackpotWinners($limit = '')
    {
        $que = 'SELECT tbl_icon_roulette.id,tbl_icon_roulette.end_datetime as time,SUM(tbl_icon_roulette_bet.winning_amount) as rewards,(SELECT GROUP_CONCAT(`card`) FROM `tbl_icon_roulette_map` WHERE `icon_roulette_id`=tbl_icon_roulette.id GROUP BY `icon_roulette_id`) as type,COUNT(tbl_icon_roulette_bet.id) as winners FROM `tbl_icon_roulette` JOIN tbl_icon_roulette_bet ON tbl_icon_roulette.id=tbl_icon_roulette_bet.icon_roulette_id WHERE tbl_icon_roulette.`winning`=6 AND tbl_icon_roulette.status=1 GROUP BY tbl_icon_roulette.id ORDER BY tbl_icon_roulette.id DESC';
        if (!empty($limit)) {
            $que .= ' LIMIT ' . $limit;
        }
        $Query = $this->db->query($que);
        return $Query->result();
    }

    public function getJackpotBigWinners($icon_roulette_id)
    {
        $Query = $this->db->query('SELECT tbl_icon_roulette_bet.amount,tbl_icon_roulette_bet.winning_amount,tbl_users.name,tbl_users.profile_pic FROM `tbl_icon_roulette_bet` JOIN tbl_users ON tbl_icon_roulette_bet.user_id=tbl_users.id WHERE tbl_icon_roulette_bet.`icon_roulette_id`=' . $icon_roulette_id . ' ORDER BY winning_amount DESC LIMIT 1');
        return $Query->result();
    }

    public function AllGames()
    {
        $this->db->select('tbl_icon_roulette.*,(select count(id) from tbl_icon_roulette_bet where tbl_icon_roulette.id=tbl_icon_roulette_bet.icon_roulette_id) as total_users');
        $this->db->from('tbl_icon_roulette');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(10);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Comission()
    {
        $this->db->from('tbl_icon_roulette');
        $this->db->where('winning_amount>', 0);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function getRandomFlag($column)
    {
        $this->db->select($column);
        $this->db->from('tbl_setting');
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();
        return $Query->row();
    }
    public function ChangeStatus()
    {
        $return = false;
        $this->db->set('icon_roulette_random', $this->input->post('type')); //value that used to update column
        $return = $this->db->update('tbl_setting');  //table name
        return $return;
    }
    function Gethistory($postData = null)
    {
        // print_r($_GET);die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
        $min = !empty($postData['min']) ? $postData['min'] : date('Y-m-d');
        $max = !empty($postData['max']) ? $postData['max'] : date('Y-m-d');
        $min = $postData['min'];
        $max = $postData['max'];
        $start = $postData['start'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_icon_roulette.*,(select count(id) from tbl_icon_roulette_bet where tbl_icon_roulette.id=tbl_icon_roulette_bet.icon_roulette_id) as total_users');
        $this->db->from('tbl_icon_roulette');
        $this->db->order_by('tbl_icon_roulette.id', 'asc');
        $totalRecords = $this->db->get()->num_rows();


        $this->db->select('tbl_icon_roulette.*,(select count(id) from tbl_icon_roulette_bet where tbl_icon_roulette.id=tbl_icon_roulette_bet.icon_roulette_id) as total_users');
        $this->db->from('tbl_icon_roulette');
        $this->db->order_by('tbl_icon_roulette.id', 'asc');
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_icon_roulette.added_date', $searchValue, 'after');
            $this->db->like('tbl_car_roulette.total_users', $searchValue, 'after');
            // $this->db->like('tbl_icon_roulette.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.random', $searchValue, 'after');
            $this->db->group_end();
        }
        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_icon_roulette.added_date) >=', $min);
            $this->db->where('DATE(tbl_icon_roulette.added_date) <=', $max);
        }

        $totalRecordwithFilter = $this->db->get()->num_rows();
        $this->db->select('tbl_icon_roulette.*,(select count(id) from tbl_icon_roulette_bet where tbl_icon_roulette.id=tbl_icon_roulette_bet.icon_roulette_id) as total_users');
        $this->db->from('tbl_icon_roulette');
        $this->db->order_by('id', 'DESC');
        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_icon_roulette.added_date', $searchValue, 'after');
            // $this->db->like('tbl_icon_roulette.total_users', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_icon_roulette.random', $searchValue, 'after');
            $this->db->group_end();
        }
        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_icon_roulette.added_date) >=', $min);
            $this->db->where('DATE(tbl_icon_roulette.added_date) <=', $max);
        }

        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();

        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {
           
            $data[] = array(
                "id" => $i,
                "total_users" => '<a href="' . base_url('backend/IconRoulette/icon_roulette_bet/' . $record->id) . '">' . $record->total_users . '</a>',
                //  "user_id"=>$record->id,
                "total_amount" => $record->total_amount,
                "admin_profit" => $record->admin_profit,
                "winning_amount" => $record->winning_amount,
                "user_amount" => $record->user_amount,
                "comission_amount" => $record->comission_amount,
                "random" => ($record->random == 1) ? 'random' : (($record->random == 2) ? 'optimization' : 'least'),

                "added_date" => date("d-m-y h:i:s A", strtotime($record->added_date)),
            );
            $i++;
        }

        //echo '<pre>';print_r($data);die;
        ## Response
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data,
        );

        return $response;
    }

    public function updateRandomAmount($game_id, $amount)
    {
        $this->db->set('random_amount', 'random_amount+' . $amount, false);
        $this->db->where('id', $game_id);
        $this->db->update('tbl_icon_roulette');

        return $this->db->affected_rows();
    }

    public function set_withdraw_amount($amount)
    {
        $this->db->set('animal_roullette_min_bet', $amount);
        $this->db->where('id', 1);
        $this->db->update('tbl_setting');
    }
}