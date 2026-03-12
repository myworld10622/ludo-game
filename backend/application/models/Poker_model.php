<?php

class Poker_model extends MY_Model
{
    public function getActiveTable()
    {
        $this->db->select('tbl_users.poker_table_id,COUNT(tbl_users.id) AS members,tbl_poker_table.private,tbl_poker_table.boot_value');
        $this->db->from('tbl_users');
        $this->db->join('tbl_poker_table', 'tbl_users.poker_table_id=tbl_poker_table.id');
        $this->db->where('tbl_users.isDeleted', false);
        // $this->db->where('tbl_poker_table.private', false);
        $this->db->where('tbl_users.poker_table_id!=', 0);
        $this->db->group_by('tbl_users.poker_table_id');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function getPublicActiveTable()
    {
        $this->db->select('tbl_users.poker_table_id,COUNT(tbl_users.id) AS members');
        $this->db->from('tbl_users');
        $this->db->join('tbl_poker_table', 'tbl_users.poker_table_id=tbl_poker_table.id');
        $this->db->where('tbl_users.isDeleted', false);
        $this->db->where('tbl_poker_table.private', false);
        $this->db->where('tbl_users.poker_table_id!=', 0);
        $this->db->group_by('tbl_users.poker_table_id');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function getCustomizeActiveTable($boot_value)
    {
        $this->db->select('tbl_users.poker_table_id,COUNT(tbl_users.id) AS members');
        $this->db->from('tbl_users');
        $this->db->join('tbl_poker_table', 'tbl_users.poker_table_id=tbl_poker_table.id');
        $this->db->where('tbl_users.isDeleted', false);
        // $this->db->where('tbl_poker_table.private', 2);
        $this->db->where('tbl_poker_table.boot_value', $boot_value);
        $this->db->where('tbl_users.poker_table_id!=', 0);
        $this->db->group_by('tbl_users.poker_table_id');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function getTableMaster($blind_1 = '')
    {
        $this->db->select('tbl_poker_table_master.*,COUNT(tbl_users.id) AS online_members');
        $this->db->from('tbl_poker_table_master');
        $this->db->join('tbl_poker_table', 'tbl_poker_table_master.blind_1=tbl_poker_table.boot_value AND tbl_poker_table.isDeleted=0', 'left');
        $this->db->join('tbl_users', 'tbl_users.poker_table_id=tbl_poker_table.id AND tbl_users.isDeleted=0', 'left');
        $this->db->where('tbl_poker_table_master.isDeleted', false);
        // $this->db->where('tbl_users.poker_table_id!=', 0);
        if (!empty($blind_1)) {
            $this->db->where('tbl_poker_table_master.blind_1', $blind_1);
        }
        $this->db->group_by('tbl_poker_table_master.blind_1');
        $this->db->order_by('tbl_poker_table_master.blind_1');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function isTable($TableId)
    {
        $this->db->select('poker_table_id');
        $this->db->from('tbl_users');
        $this->db->where('isDeleted', false);
        $this->db->where('poker_table_id', $TableId);
        $Query = $this->db->get();
        return $Query->row();
    }

    public function isTableAvail($TableId)
    {
        $this->db->from('tbl_poker_table');
        $this->db->where('isDeleted', false);
        $this->db->where('id', $TableId);
        $Query = $this->db->get();
        return $Query->row();
    }

    public function GetRamdomGameCard($game_id)
    {
        $this->db->select('cards');
        $this->db->from('tbl_cards');
        $this->db->where('`cards` NOT IN (SELECT `card1` FROM `tbl_poker_card` WHERE `id`=' . $game_id . ')', null, false);
        // $this->db->where('`cards` NOT IN (SELECT `card2` FROM `tbl_poker_card` WHERE `id`='.$game_id.')', null, false);
        $this->db->where('`cards` NOT IN (SELECT `card` FROM `tbl_poker_middle_card` WHERE `game_id`=' . $game_id . ' AND isDeleted=0)', null, false);
        $this->db->order_by('RAND()');
        $this->db->limit(1);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->result();
    }

    public function GetSeatOnTable($TableId)
    {
        $sql = "SELECT * FROM ( SELECT 1 AS mycolumn UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 ) a WHERE mycolumn NOT in ( SELECT seat_position FROM `tbl_poker_table_user` WHERE poker_table_id=" . $TableId . " AND isDeleted=0 ) LIMIT 1";
        $Query = $this->db->query($sql, false);
        return ($Query->num_rows() > 0) ? $Query->row()->mycolumn : 0;
    }

    public function TableCards($where)
    {
        $where['added_date'] = date('Y-m-d H:i:s');
        $where['updated_date'] = date('Y-m-d H:i:s');
        $this->db->insert('tbl_poker_middle_card', $where);
        $inserted_id =  $this->db->insert_id();

        return $inserted_id;
    }

    public function getTableCards($game_id)
    {
        $this->db->select('card,round,pot_amount,added_date');
        $this->db->from('tbl_poker_middle_card');
        $this->db->where('game_id', $game_id);
        // $this->db->limit(1);
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->result();
    }

    public function getSummary($game_id)
    {
        $this->db->select('tbl_poker_log.id,tbl_poker_log.user_id,sum(tbl_poker_log.amount) as invest_amount,tbl_users.name');
        $this->db->from('tbl_poker_log');
        $this->db->join('tbl_users', 'tbl_poker_log.user_id=tbl_users.id');
        $this->db->where('tbl_poker_log.game_id', $game_id);
        $this->db->order_by('tbl_poker_log.id', 'DESC');
        $this->db->group_by('tbl_poker_log.user_id', $game_id);
        // $this->db->limit(1);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->result();
    }

    public function TableUser($TableId, $user_id = '')
    {
        $this->db->select('tbl_poker_table_user.*,tbl_users.user_type,tbl_users.name,tbl_users.mobile,tbl_users.profile_pic,tbl_users.wallet,tbl_poker_table_master.boot_value as master_boot_value');
        $this->db->from('tbl_poker_table_user');
        $this->db->join('tbl_users', 'tbl_poker_table_user.user_id=tbl_users.id');
        $this->db->join('tbl_poker_table', 'tbl_poker_table.id=tbl_users.poker_table_id');
        $this->db->join('tbl_poker_table_master', 'tbl_poker_table_master.id=tbl_poker_table.master_table_id');
        $this->db->where('tbl_poker_table_user.isDeleted', false);
        $this->db->where('tbl_poker_table_user.poker_table_id', $TableId);
        if (!empty($user_id)) {
            $this->db->where('tbl_poker_table_user.user_id', $user_id);
        }
        $array_of_ordered_ids = array(2, 3, 0, 1);
        $order = sprintf('FIELD(role, %s)', implode(', ', $array_of_ordered_ids));
        $this->db->order_by($order);
        // $this->db->order_by('tbl_poker_table_user.seat_position', 'asc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function TableUserRound($TableId)
    {
        $this->db->select('tbl_poker_table_user.*,tbl_users.user_type,tbl_users.name,tbl_users.mobile,tbl_users.profile_pic,tbl_users.wallet');
        $this->db->from('tbl_poker_table_user');
        $this->db->join('tbl_users', 'tbl_poker_table_user.user_id=tbl_users.id');
        $this->db->where('tbl_poker_table_user.isDeleted', false);
        $this->db->where('tbl_poker_table_user.poker_table_id', $TableId);
        $array_of_ordered_ids = array(3, 0, 1, 2);
        $order = sprintf('FIELD(role, %s)', implode(', ', $array_of_ordered_ids));
        $this->db->order_by($order);
        // $this->db->order_by('tbl_poker_table_user.seat_position', 'asc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GameUser($game_id, $user_id_not_in = [])
    {
        $this->db->from('tbl_poker_card');
        $this->db->where('packed', false);
        $this->db->where('game_id', $game_id);
        if (!empty($user_id_not_in)) {
            $this->db->where_not_in('user_id', $user_id_not_in);
        }
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GameUserNotAllIn($game_id)
    {
        $this->db->from('tbl_poker_card');
        $this->db->where('packed', false);
        $this->db->where('all_in', false);
        $this->db->where('game_id', $game_id);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GameUserCard($game_id, $user_id)
    {
        $this->db->from('tbl_poker_card');
        $this->db->where('packed', false);
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get();
        return $Query->row();
    }

    public function getGameBot($game_id)
    {
        $this->db->select('tbl_users.*');
        $this->db->from('tbl_users');
        $this->db->join('tbl_poker_card', 'tbl_poker_card.user_id=tbl_users.id');
        $this->db->where('tbl_users.user_type', 1);
        $this->db->where('tbl_poker_card.packed', false);
        $this->db->where('tbl_poker_card.game_id', $game_id);
        $Query = $this->db->get();
        return $Query->row()->id;
    }

    public function isLeaveTable($user_id)
    {
        $return = false;
        $this->db->from('tbl_poker_log');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();

        $last_log = $Query->row();

        if ($last_log->action == 1 && $last_log->timeout == 1) {
            $return = true;
        }

        return $return;
    }

    public function GameAllUser($game_id)
    {
        $this->db->select('tbl_poker_card.*,tbl_users.name');
        $this->db->from('tbl_poker_card');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_poker_card.user_id');
        $this->db->where('tbl_poker_card.game_id', $game_id);
        $array_of_ordered_ids = array(2, 3, 0, 1);
        $order = sprintf('FIELD(role, %s)', implode(', ', $array_of_ordered_ids));
        $this->db->order_by($order);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function GameOnlyUser($game_id)
    {
        $this->db->select('tbl_poker_card.user_id,tbl_poker_card.packed,tbl_poker_card.all_in,tbl_poker_card.seen,tbl_users.name,tbl_poker_card.total_amount');
        $this->db->from('tbl_poker_card');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_poker_card.user_id');
        $this->db->where('tbl_poker_card.game_id', $game_id);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GameLog($game_id, $limit = '', $user_id = '', $round = '')
    {
        $this->db->select('tbl_poker_log.*,tbl_users.name,tbl_poker_card.role');
        $this->db->from('tbl_poker_log');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_poker_log.user_id');
        $this->db->join('tbl_poker_card', 'tbl_poker_log.game_id=tbl_poker_card.game_id AND tbl_poker_log.user_id=tbl_poker_card.user_id');
        $this->db->where('tbl_poker_log.game_id', $game_id);
        if (!empty($user_id)) {
            $this->db->where('tbl_poker_log.user_id', $user_id);
        }
        if (!empty($round)) {
            $this->db->where('tbl_poker_log.round', $round);
        }
        $this->db->order_by('id', 'DESC');
        if (!empty($limit)) {
            $this->db->limit($limit);
        }
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GameTotalAmount($game_id, $user_id)
    {
        $this->db->select('sum(tbl_poker_log.amount) as total', false);
        $this->db->from('tbl_poker_log');
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->row()->total;
    }

    public function LastChaalAmount($game_id)
    {
        $this->db->from('tbl_poker_log');
        $this->db->where('game_id', $game_id);
        $this->db->where('amount>', 0);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $Query = $this->db->get();
        return $Query->row()->amount;
    }

    public function LastChaal($game_id)
    {
        $this->db->from('tbl_poker_log');
        $this->db->where('game_id', $game_id);
        $this->db->where_in('action', [0, 2]);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $Query = $this->db->get();
        return $Query->row();
    }

    public function ChaalCount($game_id, $user_id)
    {
        $this->db->from('tbl_poker_log');
        $this->db->where('game_id', $game_id);
        $this->db->where('action', 2);
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get();
        return $Query->num_rows();
    }

    public function getActiveGameOnTable($TableId)
    {
        $this->db->from('tbl_poker');
        $this->db->where('isDeleted', false);
        $this->db->where('winner_id', 0);
        $this->db->where('poker_table_id', $TableId);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->row();
    }

    public function getAllGameOnTable($TableId)
    {
        $this->db->from('tbl_poker');
        $this->db->where('isDeleted', false);
        $this->db->where('poker_table_id', $TableId);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        // echo $this->db->last_query();
        return $Query->result();
    }

    public function getMyCards($game_id, $user_id)
    {
        $this->db->set('seen', 1); //value that used to update column
        $this->db->where('user_id', $user_id); //which row want to upgrade
        $this->db->where('game_id', $game_id); //which row want to upgrade
        $this->db->update('tbl_poker_card');  //table name

        $this->db->select('card1,card2');
        $this->db->from('tbl_poker_card');
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function GetCards($limit)
    {
        $this->db->from('tbl_cards');
        $this->db->order_by('id', 'RANDOM');
        $this->db->limit($limit);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ChatList($game_id)
    {
        $this->db->from('tbl_chat');
        $this->db->where('game_id', $game_id);
        $this->db->order_by('id', 'DESC');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function Create($data)
    {
        $this->db->insert('tbl_poker', $data);
        $GameId =  $this->db->insert_id();

        return $GameId;
    }

    public function Chat($data)
    {
        $this->db->insert('tbl_chat', $data);
        return $this->db->insert_id();
    }

    public function CreateTable($data)
    {
        $this->db->insert('tbl_poker_table', $data);
        $TableId =  $this->db->insert_id();

        return $TableId;
    }

    public function AddTableUser($data)
    {
        $this->db->insert('tbl_poker_table_user', $data);
        $TableId =  $this->db->insert_id();

        $this->db->set('poker_table_id', $data['poker_table_id']); //value that used to update column
        $this->db->where('id', $data['user_id']); //which row want to upgrade
        $this->db->update('tbl_users');  //table name

        return $TableId;
    }

    public function RemoveTableUser($data)
    {
        // $this->db->where('isDeleted', 0);
        // $this->db->where('user_id', $data['user_id']);
        // $this->db->where('poker_table_id', $data['poker_table_id']);
        // $Query = $this->db->get('tbl_poker_table_user');
        // $table_user =  $Query->row();

        $this->db->set('isDeleted', 1); //value that used to update column
        $this->db->where('user_id', $data['user_id']); //which row want to upgrade
        $this->db->where('poker_table_id', $data['poker_table_id']); //which row want to upgrade
        $this->db->update('tbl_poker_table_user');  //table name

        // $this->db->set('poker_table_id', 0); //value that used to update column
        // $this->db->set('wallet', 'wallet+' . $table_user->game_wallet, false); //value that used to update column
        // $this->db->where('id', $data['user_id']); //which row want to upgrade
        // $this->db->update('tbl_users');  //table name

        return true;
    }

    public function UpdateTableUser($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_poker_table_user', $data);  //table name
    }

    public function PackGame($user_id, $game_id, $timeout = 0, $left_amount = 0)
    {
        $this->db->set('packed', 1); //value that used to update column
        $this->db->where('user_id', $user_id); //which row want to upgrade
        $this->db->where('game_id', $game_id); //which row want to upgrade
        $this->db->update('tbl_poker_card');  //table name

        // $this->db->select('seen');
        // $this->db->from('tbl_poker_card');
        // $this->db->where('game_id', $game_id);
        // $this->db->where('user_id', $user_id);
        // $Query = $this->db->get();
        // $seen = $Query->row()->seen;

        $lastChal = $this->LastChaal($game_id);
        $round = ($lastChal) ? $lastChal->round : 0;

        $data = [
            'user_id' => $user_id,
            'game_id' => $game_id,
            'seen' => 0,
            'timeout' => $timeout,
            // 'left_amount' => $left_amount,
            'round' => $round,
            'action' => 1,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_poker_log', $data);
        return true;
    }

    public function MakeWinner($game_id, $win_amount, $user_id, $comission, $poker_table_id, $winner_id_col = 'winner_id', $winning_amt_col = 'user_winning_amt')
    {
        $admin_winning_amt = round($win_amount * round($comission / 100, 2), 2);
        $user_winning_amt = round($win_amount - $admin_winning_amt, 2);

        $this->db->set($winner_id_col, $user_id);
        $this->db->set($winning_amt_col, $user_winning_amt);
        $this->db->set('admin_winning_amt', $admin_winning_amt);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $game_id);
        $this->db->update('tbl_poker');


        $this->db->set('wallet', 'wallet+' . $user_winning_amt, false);
        $this->db->set('winning_wallet', 'winning_wallet+' . $user_winning_amt, false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');
        // $this->db->set('game_wallet', 'game_wallet+' . $user_winning_amt, false);
        // $this->db->where('user_id', $user_id);
        // $this->db->where('poker_table_id', $poker_table_id);
        // $this->db->update('tbl_poker_table_user');
        // return true;


        // $this->db->set('admin_coin', 'admin_coin+' . $admin_winning_amt, false);
        // $this->db->set('updated_date', date('Y-m-d H:i:s'));
        // $this->db->update('tbl_setting');
        log_statement($user_id, PR, $user_winning_amt, $game_id, $admin_winning_amt);
        return true;
    }

    public function Chaal($game_id, $amount, $user_id, $round, $rule, $value, $chaal_type, $poker_table_id, $game_wallet, $total_amount)
    {
        if ($chaal_type != 1) {
            // $this->MinusGameWallet($poker_table_id, $user_id, $amount);
            $this->MinusWallet($user_id, $amount);
            // minus_from_wallets($user_id, $amount, 1);
            // $this->db->set('wallet', 'wallet-' . $amount, false);
            // $this->db->where('id', $user_id);
            // $this->db->update('tbl_users');

            // $this->db->set('winning_wallet', 'winning_wallet-' . $amount, false);
            // $this->db->where('id', $user_id);
            // $this->db->where('winning_wallet>', 0);
            // $this->db->update('tbl_users');

            $this->db->set('amount', 'amount+' . $amount, false);
            $this->db->where('id', $game_id);
            $this->db->update('tbl_poker');

            log_statement($user_id,PR,-round($amount,2),$game_id,0);
        } else {
            $amount = 0;
        }

        $this->db->set('rule', $rule);
        $this->db->set('value', $value);
        $this->db->set('total_amount', $total_amount + $amount);
        if ($chaal_type == 5) {
            $this->db->set('all_in', 1);
        }
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $this->db->update('tbl_poker_card');

        // $this->db->select('seen');
        // $this->db->from('tbl_poker_card');
        // $this->db->where('game_id', $game_id);
        // $this->db->where('user_id', $user_id);
        // $Query = $this->db->get();
        // $seen = $Query->row()->seen;

        $data = [
            'user_id' => $user_id,
            'game_id' => $game_id,
            'action' => 2,
            'chaal_type' => $chaal_type,
            'amount' => $amount,
            // 'left_amount' => $game_wallet-$amount,
            'round' => $round,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_poker_log', $data);

        return true;
    }

    public function MinusGameWallet($poker_table_id, $user_id, $amount)
    {
        $this->db->set('game_wallet', 'game_wallet-' . $amount, false);
        $this->db->where('user_id', $user_id);
        $this->db->where('poker_table_id', $poker_table_id);
        $this->db->update('tbl_poker_table_user');
        return true;
    }

    public function UpdateRule($user_id, $game_id, $rule, $value)
    {
        $this->db->set('rule', $rule);
        $this->db->set('value', $value);
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $this->db->update('tbl_poker_card');
        return true;
    }

    public function AddGameWallet($poker_table_id, $user_id, $amount)
    {
        $this->db->set('game_wallet', 'game_wallet+' . $amount, false);
        $this->db->where('user_id', $user_id);
        $this->db->where('poker_table_id', $poker_table_id);
        $this->db->update('tbl_poker_table_user');
        return true;
    }

    public function isCardSeen($game_id, $user_id)
    {
        $this->db->select('seen');
        $this->db->from('tbl_poker_card');
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get();
        return ($Query->num_rows()) ? $Query->row()->seen : 0;
    }

    public function Show($game_id, $amount, $user_id, $round, $rule, $value, $chaal_type, $poker_table_id, $game_wallet)
    {
        // minus_from_wallets($user_id, $amount, 1);
        $this->MinusGameWallet($poker_table_id, $user_id, $amount);
        $this->MinusWallet($user_id, $amount);
        // $this->db->set('wallet', 'wallet-' . $amount, false);
        // $this->db->where('id', $user_id);
        // $this->db->update('tbl_users');

        // $this->db->set('winning_wallet', 'winning_wallet-' . $amount, false);
        // $this->db->where('id', $user_id);
        // $this->db->where('winning_wallet>', 0);
        // $this->db->update('tbl_users');

        $this->db->set('amount', 'amount+' . $amount, false);
        $this->db->where('id', $game_id);
        $this->db->update('tbl_poker');

        $this->db->select('seen');
        $this->db->from('tbl_poker_card');
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get();
        $seen = $Query->row()->seen;

        $data = [
            'user_id' => $user_id,
            'game_id' => $game_id,
            'action' => 3,
            'amount' => $amount,
            'chaal_type' => $chaal_type,
            // 'left_amount' => $game_wallet-$amount,
            'round' => $round,
            'added_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_poker_log', $data);

        return true;
    }

    public function SlideShow($game_id, $user_id, $prev_id)
    {
        $data = [
            'user_id' => $user_id,
            'prev_id' => $prev_id,
            'game_id' => $game_id,
            'status' => 0,
            'added_date' => date('Y-m-d H:i:s'),
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tbl_slide_show', $data);

        return $this->db->insert_id();
    }

    public function GetSlideShow($game_id)
    {
        $this->db->select('tbl_slide_show.*,tbl_users.name');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_slide_show.user_id');
        $this->db->where('tbl_slide_show.game_id', $game_id);
        $this->db->where('tbl_slide_show.status', 0);
        $query = $this->db->get('tbl_slide_show');
        return $query->result_array();
    }

    public function GetSlideShowById($slide_id)
    {
        $this->db->where('id', $slide_id);
        $this->db->where('status', 0);
        $query = $this->db->get('tbl_slide_show');
        return $query->row();
    }

    public function UpdateSlideShow($id, $status)
    {
        $this->db->set('status', $status);
        $this->db->where('id', $id);
        $this->db->update('tbl_slide_show');

        return $this->db->affected_rows();
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

    public function AddGameCount($user_id)
    {
        $this->db->set('game_played', 'game_played+1', false);
        $this->db->where('id', $user_id);
        $this->db->update('tbl_users');

        return $this->db->affected_rows();
    }

    public function GiveGameCards($data)
    {
        $this->db->insert('tbl_poker_card', $data);
        $TableId =  $this->db->insert_id();

        return $TableId;
    }

    public function AddPot($data)
    {
        $this->db->insert('tbl_poker_pot', $data);
        $potId =  $this->db->insert_id();

        return $potId;
    }

    public function ViewPot($game_id, $user_id = "")
    {
        $this->db->where('game_id', $game_id);
        if (!empty($user_id)) {
            $this->db->where('user_id', $user_id);
        }
        $this->db->order_by('amount', 'ASC');
        $Query = $this->db->get('tbl_poker_pot');
        return $Query->result();
    }

    public function AddGameLog($data)
    {
        $this->db->insert('tbl_poker_log', $data);
        $TableId =  $this->db->insert_id();

        return $TableId;
    }

    public function Update($data, $game_id)
    {
        $this->db->where('id', $game_id);
        $this->db->update('tbl_poker', $data);
        $GameId =  $this->db->affected_rows();
        // echo $this->db->last_query();
        return $GameId;
    }

    public function View($id)
    {
        $this->db->select('tbl_poker.*');
        $this->db->from('tbl_poker');
        $this->db->where('isDeleted', false);
        $this->db->where('tbl_poker.id', $id);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->row();
    }

    public function Delete($id)
    {
        $return = false;
        $this->db->set('isDeleted', true); //value that used to update column
        $this->db->where('id', $id); //which row want to upgrade
        $return = $this->db->update('tbl_poker');  //table name

        return $return;
    }

    public function DeleteTable($id)
    {
        $return = false;
        $this->db->set('isDeleted', true); //value that used to update column
        $this->db->where('id', $id); //which row want to upgrade
        $return = $this->db->update('tbl_poker_table');  //table name

        $this->db->set('poker_table_id', 0); //value that used to update column
        $this->db->where('poker_table_id', $id); //which row want to upgrade
        $return = $this->db->update('tbl_users');  //table name

        return $return;
    }

    public function CardValue($card1, $card2, $card3 = '', $card4 = '', $card5 = '')
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

    public function getPotWinnerPosition($user1, $user2)
    {
        $winner = '';

        if ($user1[0] == $user2[0]) {
            // switch ($user1[0]) {
            // case 6:
            //     $winner = ($user1[1] > $user2[1]) ? 0 : 1;
            //     break;

            // case 5:
            // case 4:
            // case 3:
            //     if ($user1[1] == $user2[1]) {
            //         $winner = 2;
            //     } else {
            //         $winner = ($user1[1] > $user2[1]) ? 0 : 1;
            //     }
            //     break;

            // case 2:
            //     if ($user1[1] == $user2[1]) {
            //         if ($user1[2] == $user2[2]) {
            //             $winner = 0;
            //         } else {
            //             $winner = ($user1[2] > $user2[2]) ? 0 : 1;
            //         }
            //     } else {
            //         $winner = ($user1[1] > $user2[1]) ? 0 : 1;
            //     }
            //     break;

            // case 1:

            //     if ($user1[1] == $user2[1]) {
            //         if ($user1[2] == $user2[2]) {
            //             if ($user1[3] == $user2[3]) {
            //                 $winner = 2;
            //             } else {
            //                 $winner = ($user1[3] > $user2[3]) ? 0 : 1;
            //             }
            //         } else {
            //             $winner = ($user1[2] > $user2[2]) ? 0 : 1;
            //         }
            //     } else {
            //         $winner = ($user1[1] > $user2[1]) ? 0 : 1;
            //     }
            //     break;
            // }
            $winner = ($user1[1] > $user2[1]) ? 0 : 1;
        } else {
            $winner = ($user1[0] < $user2[0]) ? 0 : 1;
        }

        return $winner;
    }

    public function Leaderboard()
    {
        $Query = $this->db->select('SUM(tbl_poker.amount) as Total_Win,tbl_poker.winner_id,tbl_users.name,tbl_users.profile_pic')
            ->from('tbl_poker')
            ->join('tbl_users', 'tbl_users.id=tbl_poker.winner_id')
            ->where('tbl_poker.winner_id!=', 0)
            ->group_by('tbl_poker.winner_id')
            ->order_by('SUM(tbl_poker.amount)', 'desc')
            ->limit(50)
            ->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->result();
    }

    public function AllCards()
    {
        $Query = $this->db->select('cards')
            ->from('tbl_cards')
            ->get();
        return $Query->result();
    }

    public function GameCard($game_id)
    {
        $this->db->select('card1,card2,card3');
        $this->db->from('tbl_poker_card');
        $this->db->where('game_id', $game_id);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // exit;
        return $Query->result();
    }

    public function ChangeCard($game_id, $User_id, $Position, $Card)
    {
        $data = [
            $Position => $Card
        ];
        $this->db->where('game_id', $game_id);
        $this->db->where('user_id', $User_id);
        $Update = $this->db->update('tbl_poker_card', $data);
        if ($Update) {
            return $this->db->last_query();
        } else {
            return false;
        }
    }

    public function AllGames()
    {
        $this->db->select('tbl_poker.*,tbl_users.name');
        $this->db->from('tbl_poker');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_poker.winner_id', 'left');
        $this->db->order_by('tbl_poker.id', 'DESC');
        $this->db->limit(10);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function UserLastGames($user_id)
    {
        $this->db->distinct();
        $this->db->select('game_id');
        $this->db->from('tbl_poker_log');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(20);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function UserLastGamesWithDetails($user_id)
    {
        // $this->db->distinct();
        $this->db->select('tbl_poker_log.game_id,tbl_poker_card.card1,tbl_poker_card.card2,tbl_poker_card.added_date,sum(tbl_poker_log.amount) as invested_amount,IFNULL(tbl_poker.user_winning_amt, 0) as winning_amount');
        $this->db->from('tbl_poker_log');
        $this->db->join('tbl_poker_card', 'tbl_poker_log.game_id=tbl_poker_card.game_id AND tbl_poker_log.user_id=tbl_poker_card.user_id');
        $this->db->join('tbl_poker', 'tbl_poker_log.game_id=tbl_poker.id AND tbl_poker.winner_id=tbl_poker_log.user_id', 'left');
        $this->db->where('tbl_poker_log.user_id', $user_id);
        $this->db->order_by('tbl_poker_log.id', 'DESC');
        $this->db->group_by('tbl_poker_log.game_id');
        $this->db->limit(20);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Comission()
    {
        $this->db->from('tbl_poker');
        // $this->db->where('isDeleted', false);
        $this->db->where('amount>', 0);

        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    public function Gethistory($postData = null)
    {
        // print_r($_GET);die;
        $response = array();

        ## Read value
        $draw = $postData['draw'];
        $start = $postData['start'];
        $rowperpage = $postData['length']; // Rows display per page
        $columnIndex = $postData['order'][0]['column']; // Column index
        $columnName = $postData['columns'][$columnIndex]['data']; // Column name
        $columnSortOrder = $postData['order'][0]['dir']; // asc or desc
        $searchValue = $postData['search']['value']; // Search value

        ## Total number of records without filtering
        $this->db->select('tbl_poker.*, tbl_users.name, tbl_poker_log.game_id');
        $this->db->from('tbl_poker');
        $this->db->join('tbl_users', 'tbl_users.id = tbl_poker.winner_id', 'left');
        $this->db->join('tbl_poker_log', 'tbl_poker_log.game_id = tbl_poker.id', 'left');
        $this->db->group_by('tbl_poker_log.game_id');
        // $this->db->where('tbl_seven_up.isDeleted', false);
        // $this->db->order_by('tbl_poker_log.game_id', 'desc');
        $totalRecords = $this->db->get()->num_rows();




        $this->db->select('tbl_poker.*, tbl_users.name, tbl_poker_log.game_id');
        $this->db->from('tbl_poker');
        $this->db->join('tbl_users', 'tbl_users.id = tbl_poker.winner_id', 'left');
        $this->db->join('tbl_poker_log', 'tbl_poker_log.game_id = tbl_poker.id', 'left');
        $this->db->group_by('tbl_poker_log.game_id');
        //$this->db->where('tbl_seven_up.isDeleted', false);
        // $this->db->order_by('tbl_poker_log.game_id', 'desc');
        // $this->db->where($defaultWhere);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_poker.added_date', $searchValue, 'after');
            $this->db->like('tbl_users.name', $searchValue, 'after');
            $this->db->or_like('tbl_poker.winner_id', $searchValue, 'after');
            $this->db->or_like('tbl_poker_log.game_id', $searchValue, 'after');
            $this->db->or_like('tbl_poker.amount', $searchValue, 'after');
            $this->db->or_like('tbl_poker.user_winning_amt', $searchValue, 'after');
            $this->db->or_like('tbl_poker.admin_winning_amt', $searchValue, 'after');

            // $this->db->or_like('tbl_poker.comission_amount', $searchValue, 'after');
            // $this->db->or_like('tbl_seven_up.email', $searchValue, 'after');
            //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.wallet', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.added_date', $searchValue, 'after');
            $this->db->group_end();
        }

        $totalRecordwithFilter = $this->db->get()->num_rows();

        $this->db->select('tbl_poker.*, tbl_users.name, tbl_poker_log.game_id');
        $this->db->from('tbl_poker');
        $this->db->join('tbl_users', 'tbl_users.id = tbl_poker.winner_id', 'left');
        $this->db->join('tbl_poker_log', 'tbl_poker_log.game_id = tbl_poker.id', 'left');
        $this->db->group_by('tbl_poker_log.game_id');
        $this->db->order_by('tbl_poker.added_date', 'DESC');
        // $this->db->where('tbl_seven_up.isDeleted', false);
        $this->db->order_by($columnName, $columnSortOrder);
        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('tbl_poker.added_date', $searchValue, 'after');
            $this->db->or_like('tbl_users.name', $searchValue, 'after');
            $this->db->or_like('tbl_poker.winner_id', $searchValue, 'after');
            $this->db->or_like('tbl_poker_log.game_id', $searchValue, 'after');
            $this->db->or_like('tbl_poker.amount', $searchValue, 'after');
            $this->db->or_like('tbl_poker.user_winning_amt', $searchValue, 'after');
            $this->db->or_like('tbl_poker.admin_winning_amt', $searchValue, 'after');

            //$this->db->or_like('tbl_poker.comission_amount', $searchValue, 'after');
            //$this->db->or_like('tbl_user_category.name', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.wallet', $searchValue, 'after');
            //$this->db->or_like('tbl_seven_up.added_date', $searchValue, 'after');
            $this->db->group_end();
        }
        $this->db->limit($rowperpage, $start);
        $records = $this->db->get()->result();
        $data = array();

        $i = $start + 1;
        //  echo '<pre>';print_r($records);die;
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
                "game_id" => $record->game_id,
                "name" => $record->name,
                "winner_id" => $record->winner_id,
                "amount" => $record->amount,
                "user_winning_amt" => $record->user_winning_amt,
                "admin_winning_amt" => $record->admin_winning_amt,

                // "comission_amount"=>$record->comission_amount,
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

    public function UserTotalGames($user_id)
    {
        // $this->db->distinct();
        $this->db->select('game_id');
        $this->db->from('tbl_poker_card');
        $this->db->where('user_id', $user_id);
        // $this->db->limit(20);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->num_rows();
    }

    public function UserTotalGamesWin($user_id)
    {
        $this->db->from('tbl_poker');
        $this->db->where('winner_id', $user_id);
        // $this->db->limit(20);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->num_rows();
    }

    public function UserTotalGamesFold($user_id)
    {
        $this->db->from('tbl_poker_card');
        $this->db->where('user_id', $user_id);
        $this->db->where('packed', 1);
        // $this->db->limit(20);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->num_rows();
    }

    public function UserTotalGamesInRound($user_id, $round = '', $chaal_type = '')
    {
        $this->db->from('tbl_poker_log');
        $this->db->where('user_id', $user_id);
        if (!empty($round)) {
            $this->db->where('round', $round);
        }
        if (!empty($chaal_type)) {
            $this->db->where('chaal_type', $chaal_type);
        }
        // $this->db->limit(20);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->num_rows();
    }
}
