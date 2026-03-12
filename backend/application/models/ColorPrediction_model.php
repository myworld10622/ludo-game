<?php

class ColorPrediction_model extends MY_Model
{
    public function getRoom($RoomId = '', $user_id = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_color_prediction_room');
        $this->db->where('isDeleted', false);
        if (!empty($RoomId)) {
            $this->db->where('id', $RoomId);
        }
        $this->db->order_by('id', 'asc');
        $Query = $this->db->get();

        $this->db->set('color_prediction_room_id', $RoomId); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $Query->result();
    }

    public function leave_room($user_id = '')
    {
        $this->db->set('color_prediction_room_id', ''); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $this->db->last_query();
    }

    public function getRoomOnline($RoomId)
    {
        $Query = $this->db->query('SELECT COUNT(`id`) as online FROM `tbl_color_prediction_bet` WHERE `color_prediction_id` = (SELECT `id` FROM `tbl_color_prediction` WHERE `room_id`=' . $RoomId . ' ORDER BY `id` DESC LIMIT 1)');
        return $Query->row()->online;
    }

    public function getRoomOnlineUser($RoomId)
    {
        $Query = $this->db->query('SELECT * FROM `tbl_users`  WHERE color_prediction_room_id = ' . $RoomId);
        return $Query->result();
    }

    public function getActiveGameOnTable($RoomId = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_color_prediction');
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
        $this->db->from('tbl_color_prediction_map');
        $this->db->where('color_prediction_id', $game_id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function CreateMap($color_prediction_id, $card)
    {
        $ander_data = ['color_prediction_id' => $color_prediction_id, 'card' => $card, 'added_date' => date('Y-m-d H:i:s')];
        $this->db->insert('tbl_color_prediction_map', $ander_data);
        return $this->db->insert_id();
    }

    public function PlaceBet($bet_data)
    {
        $this->db->insert('tbl_color_prediction_bet', $bet_data);
        return $this->db->insert_id();
    }

    public function DeleteBet($user_id, $game_id)
    {
        return $this->db->where('color_prediction_id', $game_id)->where('user_id', $user_id)->delete('tbl_color_prediction_bet');
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
        $this->db->from('tbl_color_prediction');
        $this->db->where('id', $id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->row();
    }

    public function Update($data, $game_id)
    {
        $this->db->where('id', $game_id);
        $this->db->update('tbl_color_prediction', $data);
        $GameId =  $this->db->affected_rows();
        // echo $this->db->last_query();
        return $GameId;
    }

    public function ViewBet($user_id = '', $color_prediction_id = '', $bet = '', $bet_id = '', $limit = '')
    {
        // echo $bet;
        $this->db->from('tbl_color_prediction_bet');

        if (!empty($user_id)) {
            $this->db->where('user_id', $user_id);
        }

        if (!empty($color_prediction_id)) {
            $this->db->where('color_prediction_id', $color_prediction_id);
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

    public function TotalBetAmount($color_prediction_id, $bet = '')
    {
        $this->db->select('SUM(amount) as amount', false);
        $this->db->from('tbl_color_prediction_bet');
        $this->db->where('color_prediction_id', $color_prediction_id);
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
        $this->db->update('tbl_color_prediction_bet');

        $this->db->set('winning_amount', 'winning_amount+' . $amount, false);
        $this->db->set('user_amount', 'user_amount+' . $user_winning_amt, false);
        $this->db->set('comission_amount', 'comission_amount+' . $admin_winning_amt, false);
        $this->db->where('id', $game_id);
        $this->db->update('tbl_color_prediction');

        $this->db->set('wallet', 'wallet+' . $user_winning_amt, false);
        $this->db->set('winning_wallet', 'winning_wallet+' . $user_winning_amt, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        // $this->db->set('admin_coin', 'admin_coin+' . $admin_winning_amt, false);
        // $this->db->set('updated_date', date('Y-m-d H:i:s'));
        // $this->db->update('tbl_setting');

        log_statement(
            $user_id,
            CP,
            $user_winning_amt,
            $bet_id,
            $admin_winning_amt
        );
        return true;
    }

    public function LastWinningBet($room_id, $limit = 10)
    {
        // echo $bet;
        $this->db->from('tbl_color_prediction');
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

    public function myHistory($user_id, $limit)
    {
        $this->db->select('tbl_color_prediction_bet.*,tbl_color_prediction.status');
        $this->db->from('tbl_color_prediction_bet');
        $this->db->join('tbl_color_prediction', 'tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id');
        $this->db->where('tbl_color_prediction_bet.user_id', $user_id);
        // $this->db->where('tbl_color_prediction_3_min.status', 1);
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
        $this->db->insert('tbl_color_prediction', $ander_data);
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
        $que = 'SELECT tbl_color_prediction.id,tbl_color_prediction.end_datetime as time,SUM(tbl_color_prediction_bet.winning_amount) as rewards,(SELECT GROUP_CONCAT(`card`) FROM `tbl_color_prediction_map` WHERE `color_prediction_id`=tbl_color_prediction.id GROUP BY `color_prediction_id`) as type,COUNT(tbl_color_prediction_bet.id) as winners FROM `tbl_color_prediction` JOIN tbl_color_prediction_bet ON tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id WHERE tbl_color_prediction.`winning`=6 AND tbl_color_prediction.status=1 GROUP BY tbl_color_prediction.id ORDER BY tbl_color_prediction.id DESC';
        if (!empty($limit)) {
            $que .= ' LIMIT ' . $limit;
        }
        $Query = $this->db->query($que);
        return $Query->result();
    }

    public function getJackpotBigWinners($color_prediction_id)
    {
        $Query = $this->db->query('SELECT tbl_color_prediction_bet.amount,tbl_color_prediction_bet.winning_amount,tbl_users.name,tbl_users.profile_pic FROM `tbl_color_prediction_bet` JOIN tbl_users ON tbl_color_prediction_bet.user_id=tbl_users.id WHERE tbl_color_prediction_bet.`color_prediction_id`=' . $color_prediction_id . ' ORDER BY winning_amount DESC LIMIT 1');
        return $Query->result();
    }

    public function AllGames($startDate = null, $endDate = null)
    {
        $this->db->select('tbl_color_prediction.*,(select count(id) from tbl_color_prediction_bet where tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id) as total_users');
        $this->db->from('tbl_color_prediction');

        // Add date filtering conditions if start date and end date are provided
        if ($startDate !== null && $endDate !== null) {
            $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
            $endDate = date('Y-m-d 23:59:00', strtotime($endDate));
            $this->db->where('added_date >=', $startDate);
            $this->db->where('added_date <=', $endDate);
        }
        $this->db->order_by('tbl_color_prediction.id', 'desc');
        $this->db->limit(10);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Comission()
    {
        $this->db->from('tbl_color_prediction');
        // $this->db->where('isDeleted', false);
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
        $this->db->set('color_prediction_random', $this->input->post('type')); //value that used to update column
        // $this->db->where('id', $id); //which row want to upgrade
        $return = $this->db->update('tbl_setting');  //table name
        return $return;
    }

    public function set_withdraw_amount($amount)
    {
        $this->db->set('color_prediction_min_bet', $amount);
        $this->db->where('id', 1);
        $this->db->update('tbl_setting');
    }

     function Gethistory($postData = null)
    {
        // print_r($_GET);die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
         $min = !empty($postData['min'])?$postData['min']:date('Y-m-d');
        $max = !empty($postData['max'])?$postData['max']:date('Y-m-d');
        $start = $postData['start'];
        // $min = $postData['min'];
        // $max = $postData['max'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_color_prediction.*,(select count(id) from tbl_color_prediction_bet where tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id) as total_users');
        $this->db->from('tbl_color_prediction');
        // $this->db->join('tbl_users', 'tbl_users.id=tbl_game.winner_id', 'left');
        // $this->db->where('tbl_seven_up.isDeleted', false);
        if(!empty($min)) {
            $this->db->where('DATE(tbl_color_prediction.added_date) >=', $min);
            $this->db->where('DATE(tbl_color_prediction.added_date) <=', $max);
        }
        // $this->db->order_by('tbl_color_prediction.id', 'asc');
        $totalRecords = $this->db->get()->num_rows();

        $this->db->select('tbl_color_prediction.*,(select count(id) from tbl_color_prediction_bet where tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id) as total_users');
        $this->db->from('tbl_color_prediction');
        // $this->db->join('tbl_users', 'tbl_users.id=tbl_game.winner_id', 'left');
        //$this->db->where('tbl_seven_up.isDeleted', false);
        if(!empty($min)) {
            $this->db->where('DATE(tbl_color_prediction.added_date) >=', $min);
            $this->db->where('DATE(tbl_color_prediction.added_date) <=', $max);
        }
        // $this->db->order_by('tbl_color_prediction.id', 'asc');
        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_color_prediction.added_date', $searchValue, 'after');
            $this->db->like('tbl_color_prediction.total_users', $searchValue, 'after');
            //$this->db->like('tbl_dragon_tiger.user_id', $searchValue, 'after');
            $this->db->like('tbl_color_prediction.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.random', $searchValue, 'after');

            $this->db->group_end();
        }

        $totalRecordwithFilter = $this->db->get()->num_rows();
        $this->db->select('tbl_color_prediction.*,(select count(id) from tbl_color_prediction_bet where tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id) as total_users');
        $this->db->from('tbl_color_prediction');
         if(!empty($min)) {
            $this->db->where('DATE(tbl_color_prediction.added_date) >=', $min);
            $this->db->where('DATE(tbl_color_prediction.added_date) <=', $max);
        }
        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_color_prediction.added_date', $searchValue, 'after');
            $this->db->like('tbl_color_prediction.total_users', $searchValue, 'after');
            // $this->db->like('tbl_dragon_tiger.user_id', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_color_prediction.random', $searchValue, 'after');

            $this->db->group_end();
        }


        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {

            $data[] = array(
    "sr" => $i,
    "id" => $record->id,
    "total_users" => '<a href="' . base_url('backend/ColorPrediction/color_prediction_bet/' . $record->id) . '">' . $record->total_users . '</a>',
    "total_amount" => $record->total_amount,
    "admin_profit" => $record->admin_profit,
    "winning_amount" => $record->winning_amount,
    "user_amount" => $record->user_amount,
    "comission_amount" => $record->comission_amount,
    "random" => in_array($record->random, [0,1,2,3,4,5,6,7,8,9]) ? $record->random :
                ($record->random == 10 ? 'Green' :
                ($record->random == 11 ? 'Violet' :
                ($record->random == 12 ? 'Red' :
                ($record->random == 20 ? 'Optimized' :
                ($record->random == 18 ? 'Random' :
                ($record->random == 15 ? 'Big' :
                ($record->random == 16 ? 'Small' :
                ($record->random == 17 ? 'Least' : 'Unknown')))))))),
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

    public function get_bet_details($bet_id,$user_id)
    {
        $this->db->select('tbl_color_prediction.id,tbl_color_prediction.winning,tbl_color_prediction.status,tbl_color_prediction_bet.bet,tbl_color_prediction_bet.amount,tbl_color_prediction_bet.winning_amount,tbl_color_prediction_bet.user_amount,tbl_color_prediction_bet.comission_amount,tbl_color_prediction_bet.added_date,tbl_users.name');
        $this->db->from('tbl_color_prediction');
        $this->db->join('tbl_color_prediction_bet','tbl_color_prediction.id=tbl_color_prediction_bet.color_prediction_id');
        $this->db->join('tbl_users','tbl_color_prediction_bet.user_id=tbl_users.id');
        $this->db->where('tbl_color_prediction_bet.id',$bet_id);
        $this->db->where('tbl_color_prediction_bet.user_id',$user_id);
        $query = $this->db->get();
        return $query->row();

    }
}