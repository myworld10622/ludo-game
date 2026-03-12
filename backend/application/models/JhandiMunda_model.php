<?php

class JhandiMunda_model extends MY_Model
{
    private $maxHistoryLimit = 1000;
    public function getRoom($RoomId = '', $user_id = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_jhandi_munda_room');
        $this->db->where('isDeleted', false);
        if (!empty($RoomId)) {
            $this->db->where('id', $RoomId);
        }
        $this->db->order_by('id', 'asc');
        $Query = $this->db->get();

        $this->db->set('jhandi_munda_id', $RoomId); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $Query->result();
    }

    public function leave_room($user_id = '')
    {
        $this->db->set('jhandi_munda_id', ''); //value that used to update column
        $this->db->where('id', $user_id); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $this->db->last_query();
    }

    public function getRoomOnline($RoomId)
    {
        $Query = $this->db->query('SELECT COUNT(`id`) as online FROM `tbl_jhandi_munda_bet` WHERE `jhandi_munda_id` = (SELECT `id` FROM `tbl_jhandi_munda` WHERE `room_id`=' . $RoomId . ' ORDER BY `id` DESC LIMIT 1)');
        return $Query->row()->online;
    }

    public function getRoomOnlineUser($RoomId)
    {
        $Query = $this->db->query('SELECT * FROM `tbl_users`  WHERE jhandi_munda_id = ' . $RoomId);
        return $Query->result();
    }

    public function getActiveGameOnTable($RoomId = '')
    {
        // $this->db->select('id,main_card,status,added_date');
        $this->db->from('tbl_jhandi_munda');
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
        $this->db->from('tbl_jhandi_munda_map');
        $this->db->where('jhandi_munda_id', $game_id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function CreateMap($jhandi_munda_id, $card)
    {
        $ander_data = ['jhandi_munda_id' => $jhandi_munda_id, 'card' => $card, 'added_date' => date('Y-m-d H:i:s')];
        $this->db->insert('tbl_jhandi_munda_map', $ander_data);
        return $this->db->insert_id();
    }

    public function MapCount($jhandi_munda_id, $card)
    {
        $this->db->from('tbl_jhandi_munda_map');
        $this->db->where('jhandi_munda_id', $jhandi_munda_id);
        $this->db->where('card', $card);
        $Query = $this->db->get();
        return $Query->num_rows();
    }

    public function PlaceBet($bet_data)
    {
        $this->db->insert('tbl_jhandi_munda_bet', $bet_data);
        return $this->db->insert_id();
    }

    public function DeleteBet($user_id, $game_id)
    {
        return $this->db->where('jhandi_munda_id', $game_id)->where('user_id', $user_id)->delete('tbl_jhandi_munda_bet');
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
        $this->db->from('tbl_jhandi_munda');
        $this->db->where('id', $id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->row();
    }

    public function Update($data, $game_id)
    {
        $this->db->where('id', $game_id);
        $this->db->update('tbl_jhandi_munda', $data);
        $GameId =  $this->db->affected_rows();
        // echo $this->db->last_query();
        return $GameId;
    }

    public function ViewBet($user_id = '', $jhandi_munda_id = '', $bet = '', $bet_id = '', $limit = '')
    {
        // echo $bet;
        $this->db->from('tbl_jhandi_munda_bet');

        if (!empty($user_id)) {
            $this->db->where('user_id', $user_id);
        }

        if (!empty($jhandi_munda_id)) {
            $this->db->where('jhandi_munda_id', $jhandi_munda_id);
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

    public function TotalBetAmount($jhandi_munda_id, $bet = '')
    {
        $this->db->select('SUM(amount) as amount', false);
        $this->db->from('tbl_jhandi_munda_bet');
        $this->db->where('jhandi_munda_id', $jhandi_munda_id);
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
        $this->db->update('tbl_jhandi_munda_bet');

        $this->db->set('winning_amount', 'winning_amount+' . $amount, false);
        $this->db->set('user_amount', 'user_amount+' . $user_winning_amt, false);
        $this->db->set('comission_amount', 'comission_amount+' . $admin_winning_amt, false);
        $this->db->where('id', $game_id);
        $this->db->update('tbl_jhandi_munda');

        $this->db->set('wallet', 'wallet+' . $user_winning_amt, false);
        $this->db->set('winning_wallet', 'winning_wallet+' . $user_winning_amt, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        // $this->db->set('admin_coin', 'admin_coin+' . $admin_winning_amt, false);
        // $this->db->set('updated_date', date('Y-m-d H:i:s'));
        // $this->db->update('tbl_setting');
        log_statement(
            $user_id,
            JM,
            $user_winning_amt,
            $bet_id,
            $admin_winning_amt
        );
        return true;
    }

    public function LastWinningBet($room_id, $limit = 10)
    {
        // echo $bet;
        $this->db->from('tbl_jhandi_munda');
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
        $this->db->insert('tbl_jhandi_munda', $ander_data);
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
        $que = 'SELECT tbl_jhandi_munda.id,tbl_jhandi_munda.end_datetime as time,SUM(tbl_jhandi_munda_bet.winning_amount) as rewards,(SELECT GROUP_CONCAT(`card`) FROM `tbl_jhandi_munda_map` WHERE `jhandi_munda_id`=tbl_jhandi_munda.id GROUP BY `jhandi_munda_id`) as type,COUNT(tbl_jhandi_munda_bet.id) as winners FROM `tbl_jhandi_munda` JOIN tbl_jhandi_munda_bet ON tbl_jhandi_munda.id=tbl_jhandi_munda_bet.jhandi_munda_id WHERE tbl_jhandi_munda.`winning`=6 AND tbl_jhandi_munda.status=1 GROUP BY tbl_jhandi_munda.id ORDER BY tbl_jhandi_munda.id DESC';
        if (!empty($limit)) {
            $que .= ' LIMIT ' . $limit;
        }
        $Query = $this->db->query($que);
        return $Query->result();
    }

    public function getJackpotBigWinners($jhandi_munda_id)
    {
        $Query = $this->db->query('SELECT tbl_jhandi_munda_bet.amount,tbl_jhandi_munda_bet.winning_amount,tbl_users.name,tbl_users.profile_pic FROM `tbl_jhandi_munda_bet` JOIN tbl_users ON tbl_jhandi_munda_bet.user_id=tbl_users.id WHERE tbl_jhandi_munda_bet.`jhandi_munda_id`=' . $jhandi_munda_id . ' ORDER BY winning_amount DESC LIMIT 1');
        return $Query->result();
    }

    public function AllGames()
    {
        $this->db->select('tbl_jhandi_munda.*,(select count(id) from tbl_jhandi_munda_bet where tbl_jhandi_munda.id=tbl_jhandi_munda_bet.jhandi_munda_id) as total_users');
        $this->db->from('tbl_jhandi_munda');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(10);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Comission()
    {
        $this->db->from('tbl_jhandi_munda');
        // $this->db->where('isDeleted', false);
        $this->db->where('winning_amount>', 0);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function CardValue($card1, $card2, $card3)
    {
        $rule = 1;
        $value = 0;
        $value2 = 0;
        $value3 = 0;

        $card1_color = substr($card1, 0, 2);
        $card1_num = substr($card1, 2);

        $card2_color = substr($card2, 0, 2);
        $card2_num = substr($card2, 2);

        $card3_color = substr($card3, 0, 2);
        $card3_num = substr($card3, 2);

        if (($card1_num == $card2_num) && ($card2_num == $card3_num)) {
            $card1_num = str_replace(
                array("J", "Q", "K", "A"),
                array(11, 12, 13, 14),
                $card1_num
            );
            $card1_num = (int) $card1_num;
            $rule = 6;
            $value = $card1_num;
        } else {
            $card1_num = str_replace(
                array("J", "Q", "K", "A"),
                array(11, 12, 13, 14),
                $card1_num
            );
            $card2_num = str_replace(
                array("J", "Q", "K", "A"),
                array(11, 12, 13, 14),
                $card2_num
            );
            $card3_num = str_replace(
                array("J", "Q", "K", "A"),
                array(11, 12, 13, 14),
                $card3_num
            );

            $card1_num = (int) $card1_num;
            $card2_num = (int) $card2_num;
            $card3_num = (int) $card3_num;

            $arr = [$card1_num, $card2_num, $card3_num];
            sort($arr);

            $sequence = false;
            if (($arr[0] == $arr[1] - 1) && ($arr[1] == $arr[2] - 1)) {
                $sequence = true;
            }

            //Exception for A23
            if ($arr[0] == 2 && $arr[1] == 3 && $arr[2] == 14) {
                $sequence = true;
                $arr[2] = 3;
            }

            $color = false;
            if (($card1_color == $card2_color) && ($card2_color == $card3_color)) {
                $color = true;
            }

            if ($sequence && $color) {
                $rule = 5;
                $value = $arr[2];
            } elseif ($sequence) {
                $rule = 4;
                $value = $arr[2];
            } elseif ($color) {
                $rule = 3;
                $value = $arr[2];
            } else {
                if (($card1_num == $card2_num) || ($card2_num == $card3_num) ||
                    ($card1_num == $card3_num)
                ) {
                    $rule = 2;
                    if ($card1_num == $card2_num) {
                        $value = $card1_num;
                        $value2 = $card3_num;
                    } elseif ($card2_num == $card3_num) {
                        $value = $card2_num;
                        $value2 = $card1_num;
                    } elseif ($card1_num == $card3_num) {
                        $value = $card3_num;
                        $value2 = $card2_num;
                    }
                } else {
                    $rule = 1;
                    $value = $arr[2];
                    $value2 = $arr[1];
                    $value3 = $arr[0];
                }
            }
        }
        return array($rule, $value, $value2, $value3);
    }

    public function getWinnerPosition($user1, $user2)
    {
        $winner = '';

        if ($user1[0] == $user2[0]) {
            switch ($user1[0]) {
                case 6:
                    $winner = ($user1[1] > $user2[1]) ? 0 : 1;
                    break;

                case 5:
                case 4:
                    if ($user1[1] == $user2[1]) {
                        $winner = 2;
                    } else {
                        //Exception for A23
                        $user1[1] = ($user1[1] == 14) ? 15 : $user1[1];
                        $user2[1] = ($user2[1] == 14) ? 15 : $user2[1];

                        $user1[1] = ($user1[1] == 3) ? 14 : $user1[1];
                        $user2[1] = ($user2[1] == 3) ? 14 : $user2[1];

                        $winner = ($user1[1] > $user2[1]) ? 0 : 1;
                    }
                    break;
                case 3:
                    if ($user1[1] == $user2[1]) {
                        $winner = 2;
                    } else {
                        $winner = ($user1[1] > $user2[1]) ? 0 : 1;
                    }
                    break;

                case 2:
                    if ($user1[1] == $user2[1]) {
                        if ($user1[2] == $user2[2]) {
                            $winner = 2;
                        } else {
                            $winner = ($user1[2] > $user2[2]) ? 0 : 1;
                        }
                    } else {
                        $winner = ($user1[1] > $user2[1]) ? 0 : 1;
                    }
                    break;

                case 1:

                    if ($user1[1] == $user2[1]) {
                        if ($user1[2] == $user2[2]) {
                            if ($user1[3] == $user2[3]) {
                                $winner = 2;
                            } else {
                                $winner = ($user1[3] > $user2[3]) ? 0 : 1;
                            }
                        } else {
                            $winner = ($user1[2] > $user2[2]) ? 0 : 1;
                        }
                    } else {
                        $winner = ($user1[1] > $user2[1]) ? 0 : 1;
                    }
                    break;
            }
        } else {
            $winner = ($user1[0] > $user2[0]) ? 0 : 1;
        }

        return $winner;
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
        $this->db->set('jhandi_munda_random', $this->input->post('type')); //value that used to update column
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
        $min = $postData['min'];
        $max = $postData['max'];
        $start = $postData['start'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_jhandi_munda.*,(select count(id) from tbl_jhandi_munda_bet where tbl_jhandi_munda.id=tbl_jhandi_munda_bet.jhandi_munda_id) as total_users');
        $this->db->from('tbl_jhandi_munda');
        // $this->db->join('tbl_users', 'tbl_users.id=tbl_game.winner_id', 'left');
        // $this->db->where('tbl_seven_up.isDeleted', false);
        $this->db->order_by('tbl_jhandi_munda.id', 'asc');
        $totalRecords = $this->db->limit($this->maxHistoryLimit)->get()->num_rows();

         if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_jhandi_munda.added_date) >=', $min);
            $this->db->where('DATE(tbl_jhandi_munda.added_date) <=', $max);
        }

        $this->db->select('tbl_jhandi_munda.*,(select count(id) from tbl_jhandi_munda_bet where tbl_jhandi_munda.id=tbl_jhandi_munda_bet.jhandi_munda_id) as total_users');
        $this->db->from('tbl_jhandi_munda');
        // $this->db->join('tbl_users', 'tbl_users.id=tbl_game.winner_id', 'left');
        //$this->db->where('tbl_seven_up.isDeleted', false);
        $this->db->order_by('tbl_jhandi_munda.id', 'asc');
        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_jhandi_munda.added_date', $searchValue, 'after');
            $this->db->like('tbl_jhandi_munda.total_users', $searchValue, 'after');
            // $this->db->like('tbl_jhandi_munda.id', $searchValue, 'after');
            $this->db->like('tbl_jhandi_munda.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.random', $searchValue, 'after');

            // $this->db->or_like('tbl_game.comission_amount', $searchValue, 'after');
            // $this->db->or_like('tbl_seven_up.email', $searchValue, 'after');
            //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.wallet', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        $totalRecordwithFilter = $this->db->limit($this->maxHistoryLimit)->get()->num_rows();
        $this->db->select('tbl_jhandi_munda.*,(select count(id) from tbl_jhandi_munda_bet where tbl_jhandi_munda.id=tbl_jhandi_munda_bet.jhandi_munda_id) as total_users');
        $this->db->from('tbl_jhandi_munda');
        $this->db->order_by('id', 'DESC');
        // $this->db->join('tbl_users', 'tbl_users.id=tbl_game.winner_id', 'left');
        // $this->db->where('tbl_seven_up.isDeleted', false);
        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_jhandi_munda.added_date', $searchValue, 'after');
            $this->db->like('tbl_jhandi_munda.total_users', $searchValue, 'after');
            // $this->db->like('tbl_jhandi_munda.id', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.total_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.admin_profit', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.winning_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.user_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.comission_amount', $searchValue, 'after');
            $this->db->or_like('tbl_jhandi_munda.random', $searchValue, 'after');

            //$this->db->or_like('tbl_game.comission_amount', $searchValue, 'after');
            //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.wallet', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.added_date', $searchValue, 'after');
            $this->db->group_end();
        }
        if ($min != "" && $max != "") {
            $this->db->where('DATE(tbl_jhandi_munda.added_date) >=', $min);
            $this->db->where('DATE(tbl_jhandi_munda.added_date) <=', $max);
        }

        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        // echo '<pre>';print_r($records);die;
        foreach ($records as $record) {
            // $status = '<select class="form-control" onchange="ChangeStatus('.$record->id.',this.value)">
            //     <option value="0"'.(($record->status == 0) ? 'selected' : '').'>Active</option>
            //     <option value="1" '.(($record->status == 1) ? 'selected' : '').'>Block</option>
            // </select>';
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
    "total_users" => '<a href="' . base_url('backend/JhandiMunda/JhandiMundaBet/' . $record->id) . '">' . $record->total_users . '</a>',
    //"user_id"=>$record->id,
    "total_amount" => $record->total_amount,
    "admin_profit" => $record->admin_profit,
    "winning_amount" => $record->winning_amount,
    "user_amount" => $record->user_amount,
    "comission_amount" => $record->comission_amount,
    "random" => ($record->random == 1) ? 'Random' : (($record->random == 2) ? 'Optimization' : 'Least'),
                // "comission_amount"=>$record->comission_amount,
                //"mobile"=>($record->mobile=='') ? $record->email : $record->mobile,
                //   "user_type"=>$record->user_type==1 ? 'BOT' : 'REAL',
                //   "user_category"=>$record->user_category,
                //   "wallet"=>$record->wallet,
                //   "winning_wallet"=>$record->winning_wallet,
                //"on_table"=>($record->table_id > 0) ? 'Yes' : 'No',
                // "status"=>$status,
                "added_date" => date("d-m-y h:i:s A", strtotime($record->added_date)),
                //"action"=>$action,
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
        $this->db->update('tbl_jhandi_munda');

        return $this->db->affected_rows();
    }
}