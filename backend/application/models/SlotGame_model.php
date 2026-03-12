<?php

class SlotGame_model extends MY_Model
{
    public function getBet($BetId = '', $user_id = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_slot_game_bet');
        $this->db->where('isDeleted', false);
        if (!empty($BetId)) {
            $this->db->where('id', $BetId);
        }
        $this->db->order_by('id', 'asc');
        $Query = $this->db->get();

        $this->db->set('slot_game_bet_id', $BetId); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $Query->result();
    }

    public function leave_bet($user_id = '')
    {
        $this->db->set('slot_game_bet_id', ''); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $this->db->last_query();
    }

    public function getBetOnline($BetId)
    {
        $Query = $this->db->query('SELECT COUNT(`id`) as online FROM `tbl_slot_game_bet` WHERE `slot_game_id` = (SELECT `id` FROM `tbl_slot_game` WHERE `bet_id`=' . $BetId . ' ORDER BY `id` DESC LIMIT 1)');
        return $Query->row()->online;
    }

    public function getBetOnlineUser($BetId)
    {
        $Query = $this->db->query('SELECT * FROM `tbl_users`  WHERE slot_game_bet_id = ' . $BetId);
        return $Query->result();
    }

    public function getActiveGameOnTable($BetId = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_slot_game');
        if (!empty($BetId)) {
            $this->db->where('bet_id', $BetId);
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
        $this->db->from('tbl_slot_game_map');
        $this->db->where('slot_game_id', $game_id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function CreateMap($slot_game_id, $card)
    {
        $ander_data = ['slot_game_id' => $slot_game_id, 'card' => $card, 'added_date' => date('Y-m-d H:i:s')];
        $this->db->insert('tbl_slot_game_map', $ander_data);
        return $this->db->insert_id();
    }

    public function PlaceBet($bet_data)
    {
        $this->db->insert('tbl_slot_game_bet', $bet_data);
        return $this->db->insert_id();
    }

    public function DeleteBet($bet_id, $user_id, $game_id)
    {
        return $this->db->where('slot_game_id', $game_id)->where('user_id', $user_id)->delete('tbl_slot_game_bet');
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
        $this->db->from('tbl_slot_game');
        $this->db->where('id', $id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->row();
    }

    public function Update($data, $game_id)
    {
        $this->db->where('id', $game_id);
        $this->db->update('tbl_slot_game', $data);
        $GameId =  $this->db->affected_rows();
        // echo $this->db->last_query();
        return $GameId;
    }

    public function ViewBet($user_id = '', $slot_game_id = '', $bet = '', $bet_id = '', $limit = '')
    {
        // echo $bet;
        $this->db->from('tbl_slot_game_bet');

        if (!empty($user_id)) {
            $this->db->where('user_id', $user_id);
        }

        if (!empty($slot_game_id)) {
            $this->db->where('slot_game_id', $slot_game_id);
        }

        if ($bet !== '') {
            $this->db->where('bet', $bet);
        }

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

    public function LastWinningBet($bet_id, $limit = 10)
    {
        // echo $bet;
        $this->db->from('tbl_slot_game');
        $this->db->where('status', 1);
        if (!empty($bet_id)) {
            $this->db->where('bet_id', $bet_id);
        }
        if (!empty($limit)) {
            $this->db->limit($limit);
        }
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }


    public function TotalBetAmount($slot_game_id, $bet = '', $user_id = '')
    {
        $this->db->select('SUM(amount) as amount', false);
        $this->db->from('tbl_slot_game_bet');
        $this->db->where('slot_game_id', $slot_game_id);
        if ($bet !== '') {
            $this->db->where('bet', $bet);
        }
        if ($user_id !== '') {
            $this->db->where('user_id', $user_id);
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
        $this->db->update('tbl_slot_game_bet');

        $this->db->set('winning_amount', 'winning_amount+' . $amount, false);
        $this->db->set('user_amount', 'user_amount+' . $user_winning_amt, false);
        $this->db->set('comission_amount', 'comission_amount+' . $admin_winning_amt, false);
        $this->db->where('id', $game_id);
        $this->db->update('tbl_slot_game');

        $this->db->set('wallet', 'wallet+' . $user_winning_amt, false);
        $this->db->set('winning_wallet', 'winning_wallet+' . $user_winning_amt, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        // $this->db->set('admin_coin', 'admin_coin+' . $admin_winning_amt, false);
        // $this->db->set('updated_date', date('Y-m-d H:i:s'));
        // $this->db->update('tbl_setting');
        log_statement(
            $user_id,
            SU,
            $user_winning_amt,
            $bet_id,
            $admin_winning_amt
        );
        return true;
    }

    public function Create($bet_id)
    {
        $ander_data = ['bet_id' => $bet_id, 'added_date' => date('Y-m-d H:i:s')];
        $this->db->insert('tbl_slot_game', $ander_data);
        return $this->db->insert_id();
    }

    public function AllCards()
    {
        $Query = $this->db->select('cards')
            ->from('tbl_cards')
            ->get();
        return $Query->result();
    }

    public function AllGames()
    {
        $this->db->select('tbl_slot_game.*,(select count(id) from tbl_slot_game_bet where tbl_slot_game.id=tbl_slot_game_bet.slot_game_id) as total_users');
        $this->db->from('tbl_slot_game');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(100);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Comission()
    {
        $this->db->select('tbl_slot_game.*');
        $this->db->from('tbl_slot_game');
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
        $this->db->set('up_down_random', $this->input->post('type')); //value that used to update column
        // $this->db->where('id', $id); //which row want to upgrade
        $return = $this->db->update('tbl_setting');  //table name
        return $return;
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
        $min = $postData['min'];
        $max = $postData['max'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_slot_game.*,(select count(id) from tbl_slot_game_bet where tbl_slot_game.id=tbl_slot_game_bet.slot_game_id) as total_users');
        $this->db->from('tbl_slot_game');
        // $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->where('tbl_slot_game.isDeleted', false);
        $this->db->order_by('tbl_slot_game.id', 'asc');
        $totalRecords = $this->db->get()->num_rows();

         if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_slot_game.added_date) >=', $min);
            $this->db->where('DATE(tbl_slot_game.added_date) <=', $max);
        }

        $this->db->select('tbl_slot_game.*,(select count(id) from tbl_slot_game_bet where tbl_slot_game.id=tbl_slot_game_bet.slot_game_id) as total_users');
        $this->db->from('tbl_slot_game');
        // $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        //$this->db->where('tbl_slot_game.isDeleted', false);
        $this->db->order_by('tbl_slot_game.id', 'asc');
        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_slot_game.added_date', $searchValue, 'after');
            $this->db->like('tbl_slot_game.total_users', $searchValue, 'after');
            // $this->db->like('tbl_slot_game.id', $searchValue, 'after');
            $this->db->like('tbl_slot_game.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.random', $searchValue, 'after');

            // $this->db->or_like('tbl_slot_game.email', $searchValue, 'after');
            //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            //$this->db->or_like('tbl_slot_game.wallet', $searchValue, 'after');
            //$this->db->or_like('tbl_slot_game.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        $totalRecordwithFilter = $this->db->get()->num_rows();
        $this->db->select('tbl_slot_game.*,(select count(id) from tbl_slot_game_bet where tbl_slot_game.id=tbl_slot_game_bet.slot_game_id) as total_users');
        $this->db->from('tbl_slot_game');
        $this->db->order_by('id', 'DESC');
        // $this->db->join('tbl_user_category', 'tbl_users.user_category_id=tbl_user_category.id', 'LEFT');
        // $this->db->where('tbl_slot_game.isDeleted', false);
        //  $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_slot_game.added_date', $searchValue, 'after');
            $this->db->like('tbl_slot_game.total_users', $searchValue, 'after');
            // $this->db->like('tbl_slot_game.id', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_slot_game.random', $searchValue, 'after');
            //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            //$this->db->or_like('tbl_slot_game.wallet', $searchValue, 'after');
            //$this->db->or_like('tbl_slot_game.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_slot_game.added_date) >=', $min);
            $this->db->where('DATE(tbl_slot_game.added_date) <=', $max);
        }
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        //  echo '<pre>';print_r($records);die;
        foreach ($records as $record) {
            $status = '<select class="form-control" onchange="ChangeStatus(' . $record->id . ',this.value)">
            <option value="0"' . (($record->status == 0) ? 'selected' : '') . '>Active</option>
            <option value="1" ' . (($record->status == 1) ? 'selected' : '') . '>Block</option>
        </select>';
            //     $action = '<a href="'.base_url('backend/user/view/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="View Wins"><span
            //         class="fa fa-eye"></span></a>
            //         | <a href="'.base_url('backend/user/LadgerReports/' . $record->id).'" class="btn btn-info"
            //         data-toggle="tooltip" data-placement="top" title="View Ladger Report"><span class="ti-wallet"></span></a>
            // | <a href="'.base_url('backend/user/edit/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="Edit"><span
            //         class="fa fa-credit-card" ></span></a>

            // | <a href="'.base_url('backend/user/edit_wallet/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="Deduct Wallet"><span
            //         class="fa fa-credit-card" ></span></a>

            //         | <a href="'.base_url('backend/user/edit_user/' . $record->id).'" class="btn btn-info"
            //     data-toggle="tooltip" data-placement="top" title="Edit"><span
            //         class="fa fa-edit" ></span></a>


            // | <a href="'.base_url('backend/user/delete/' . $record->id).'" class="btn btn-danger"
            //     data-toggle="tooltip" data-placement="top" title="Delete" onclick="return confirm(\'Are You Sure Want To Delete '.$record->name.'?\')"><span
            //         class="fa fa-trash" ></span></a>';
            $data[] = array(
    "id" => $i,
    "total_users" => '<a href="' . base_url('backend/SlotGame/slot_game_bet/' . $record->id) . '">' . $record->total_users . '</a>',
    // "user_id"=>$record->id,
    "total_amount" => $record->total_amount,
    "admin_profit" => $record->admin_profit,
    "winning_amount" => $record->winning_amount,
    "user_amount" => $record->user_amount,
    "comission_amount" => $record->comission_amount,
    "random" => ($record->random == 1) ? 'Random' : (($record->random == 2) ? 'Optimization' : 'Least'),
                //"mobile"=>($record->mobile=='') ? $record->email : $record->mobile,
                //   "user_type"=>$record->user_type==1 ? 'BOT' : 'REAL',
                //   "user_category"=>$record->user_category,
                //   "wallet"=>$record->wallet,
                //   "winning_wallet"=>$record->winning_wallet,
                //"on_table"=>($record->table_id > 0) ? 'Yes' : 'No',
                // "status"=>$status,
                "added_date" => date("d-m-Y h:i:s A", strtotime($record->added_date)),
                //"action"=>$action,
            );
            $i++;
        }

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
        $this->db->update('tbl_slot_game');

        return $this->db->affected_rows();
    }

    public function set_withdraw_amount($amount)
    {
        $this->db->set('slot_game_min_bet', $amount);
        $this->db->where('id', 1);
        $this->db->update('tbl_setting');
    }
}