<?php

use phpDocumentor\Reflection\DocBlock\Tags\Return_;

class WithdrawalLog_model extends MY_Model
{

    public function AllRedeemList()
    {
        $this->db->select('*');
        $this->db->from('tbl_redeem');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function Insert($data)
    {
        if ($this->db->insert('tbl_redeem', $data))
            return $this->db->last_query();
        else
            return false;
    }

    public function getRedeem($id)
    {
        $Query = $this->db->where('id', $id)
            ->get('tbl_redeem');
        if ($Query)
            return $Query->row();
        else
            return false;
    }

    public function checkRecharge($user_id)
    {
        $Query = $this->db->where('user_id', $user_id)
            ->where('payment', 1)
            ->get('tbl_purchase');
        if ($Query)
            return $Query->row();
        else
            return false;
    }


    public function update($Redeem_id, $data)
    {
        $this->db->where('id', $Redeem_id);
        if ($this->db->update('tbl_redeem', $data))
            return $this->db->last_query();
        else
            return false;
    }

    public function Delete($id)
    {
        $Query = $this->db->set('isDeleted', 1)
            ->where('id', $id)
            ->update('tbl_redeem');
        if ($Query)
            return $this->db->last_query();
        else
            return false;
    }

    public function WithDrawCrypto($user_id, $Redeem_id, $coin, $mobile, $crypto_address)
    {
        $Deducted = $this->db->set('wallet', "wallet-$coin", FALSE)
            ->set('winning_wallet', "winning_wallet-$coin", FALSE)
            ->set('updated_date', date('Y-m-d H:i:s'))
            ->where('id', $user_id)
            ->update('tbl_users');
        if ($Deducted) {
            $data = [
                'user_id' => $user_id,
                'redeem_id' => $Redeem_id ?? 0,
                'mobile' => $mobile,
                'coin' => $coin,
                'crypto_address' => $crypto_address,
            ];
            $this->db->insert('tbl_withdrawal_log', $data);
            return $this->db->insert_id();
        }
    }

    public function WithDraw($user_id, $Redeem_id, $coin, $user_bank, $type)
    {
        $Deducted = $this->db->set('wallet', "wallet-$coin", FALSE)
            ->set('winning_wallet', "winning_wallet-$coin", FALSE)
            ->set('updated_date', date('Y-m-d H:i:s'))
            ->where('id', $user_id)
            ->update('tbl_users');
        if ($Deducted) {
            $data = [
                'user_id' => $user_id,
                'redeem_id' => $Redeem_id,
                'bank_name' => $user_bank[0]->bank_name,
                'ifsc_code' => $user_bank[0]->ifsc_code,
                'acc_holder_name' => $user_bank[0]->acc_holder_name,
                'acc_no' => $user_bank[0]->acc_no,
                'passbook_img' => $user_bank[0]->passbook_img,
                'crypto_wallet_type' => $user_bank[0]->crypto_wallet_type,
                'crypto_qr' => $user_bank[0]->crypto_qr,
                'crypto_address' => $user_bank[0]->crypto_address,
                'type' => $type,
                'coin' => $coin,
            ];
            $this->db->insert('tbl_withdrawal_log', $data);
            return $this->db->insert_id();
        }
    }

    public function withdrawRequestForAgent($user_id, $Redeem_id, $coin, $user_bank, $price, $agent_id, $type)
    {
        $Deducted = $this->db->set('wallet', "wallet-$coin", FALSE)
            ->set('winning_wallet', "winning_wallet-$coin", FALSE)
            ->set('updated_date', date('Y-m-d H:i:s'))
            ->where('id', $user_id)
            ->update('tbl_users');
        if ($Deducted) {
            $data = [
                'user_id' => $user_id,
                'redeem_id' => $Redeem_id,
                'bank_name' => $user_bank[0]->bank_name,
                'ifsc_code' => $user_bank[0]->ifsc_code,
                'acc_holder_name' => $user_bank[0]->acc_holder_name,
                'acc_no' => $user_bank[0]->acc_no,
                'passbook_img' => $user_bank[0]->passbook_img,
                'crypto_wallet_type' => $user_bank[0]->crypto_wallet_type,
                'crypto_qr' => $user_bank[0]->crypto_qr,
                'crypto_address' => $user_bank[0]->crypto_address,
                'type' => $type,
                'agent_id' => $agent_id,
                'price' => $price,
                'coin' => $coin,
            ];
            $this->db->insert('tbl_withdrawal_log', $data);
            return $this->db->insert_id();
        }
    }

    public function WithDrawal_log($user_id)
    {
        return $Query = $this->db->select('tbl_withdrawal_log.*,tbl_users.name as user_name,tbl_users.mobile as user_mobile,tbl_users.bank_detail,tbl_users.adhar_card,tbl_users.upi')
            ->from('tbl_withdrawal_log')
            ->join('tbl_users', 'tbl_users.id=tbl_withdrawal_log.user_id')
            ->where('tbl_withdrawal_log.isDeleted', FALSE)
            ->where('tbl_withdrawal_log.user_id', $user_id)
            ->order_by('tbl_withdrawal_log.id', 'DESC') // Order by id in descending order
            ->get()
            ->result();
    }

    public function WithDrawal_log_by_id($id)
    {
        return $Query = $this->db->select('tbl_withdrawal_log.*,tbl_users.name as user_name,tbl_users.mobile as user_mobile,tbl_users.bank_detail,tbl_users.adhar_card,tbl_users.upi')
            ->from('tbl_withdrawal_log')
            ->join('tbl_users', 'tbl_users.id=tbl_withdrawal_log.user_id')
            ->where('tbl_withdrawal_log.isDeleted', FALSE)
            ->where('tbl_withdrawal_log.id', $id)
            ->order_by('tbl_withdrawal_log.id', 'DESC') // Order by id in descending order
            ->get()
            ->row();
    }

    public function WithDrawal_list($status, $startDate = "", $endDate = "")
    {
        $this->db->select('tbl_withdrawal_log.*,tbl_users.name as user_name');
        $this->db->from('tbl_withdrawal_log');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_withdrawal_log.user_id');
        // $this->db->join('tbl_users_bank_details','tbl_users.id=tbl_users_bank_details.user_id','LEFT');
        // filter
        if (!empty($startDate)) {
            $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
            $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
            $this->db->where('created_date >=', $startDate);
            $this->db->where('created_date <=', $endDate);
        } else {
            // $startDate = date('Y-m-d 00:00:00');
            // $endDate = date('Y-m-d 23:59:59');
            // $this->db->where('created_date >=', $startDate);
            // $this->db->where('created_date <=', $endDate);
        }
        if ($this->session->role == 2) {
            $this->db->where('tbl_withdrawal_log.agent_id', $this->session->admin_id);
        }
        $this->db->where('tbl_withdrawal_log.isDeleted', FALSE);
        $this->db->where('tbl_withdrawal_log.status', $status);
        $Query = $this->db->get();
        return $Query->result();
    }

    public function ChangeStatus($id, $status, $response = '')
    {
        if ($this->session->role == 2) {
            $this->db->where('tbl_withdrawal_log.agent_id', $this->session->admin_id);
        }
        $this->db->where('id', $id);
        $this->db->set('status', $status);
        $this->db->set('payout_response', $response);
        $this->db->update('tbl_withdrawal_log');

        // echo 'status - '.$status;
        if ($status == 2) {
            $this->db->where('isDeleted', FALSE);
            if ($this->session->role == 2) {
                $this->db->where('tbl_withdrawal_log.agent_id', $this->session->admin_id);
            }
            $this->db->where('id', $id);
            $Query = $this->db->get('tbl_withdrawal_log')->row();

            $this->db->set('wallet', "wallet+$Query->coin", FALSE)
                ->set('winning_wallet', "winning_wallet+$Query->coin", FALSE)
                ->set('updated_date', date('Y-m-d H:i:s'))
                ->where('id', $Query->user_id)
                ->update('tbl_users');

            // echo $this->db->last_query();

            log_statement($Query->user_id, WITHDRAW_REJECTED, $Query->coin, $id);
        }
        return $this->db->last_query();
    }

    public function edit_wallet($id)
    {
        $data = [
            'title' => 'Deduct Wallet Amount',
            'User' => $this->Users_model->UserProfile($id)
        ];

        template('user/edit_wallet', $data);
    }

    public function update_wallet()
    {
        $user = $this->Users_model->deductWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            $user = $this->Users_model->WalletLog($this->input->post('amount'), $this->input->post('bonus'), $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'User Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/user');
    }

    public function GetTotalWithdraw($status)
    {
        $this->db->select('SUM(coin) as totalWithdraw');
        $this->db->from('tbl_withdrawal_log');
        // $this->db->join('tbl_users', 'tbl_users.id=tbl_withdrawal_log.user_id');
        if ($this->session->role == 2) {
            $this->db->where('tbl_withdrawal_log.agent_id', $this->session->admin_id);
        }
        $this->db->where('tbl_withdrawal_log.isDeleted', FALSE);
        $this->db->where('tbl_withdrawal_log.status', $status); // 1
        $Query = $this->db->get();
        return $Query->row()->totalWithdraw;
    }

    public function GetTodayWithdraw()
    {
        $this->db->select('SUM(coin) as todayWithdraw');
        $this->db->from('tbl_withdrawal_log');
        if ($this->session->role == 2) {
            $this->db->where('tbl_withdrawal_log.agent_id', $this->session->admin_id);
        }

        $this->db->where('DATE(tbl_withdrawal_log.created_date)', date('Y-m-d'));
        $this->db->where('tbl_withdrawal_log.status', 1);
        $this->db->where('tbl_withdrawal_log.isDeleted', FALSE);
        $query = $this->db->get();
        return $query->row()->todayWithdraw ?? 0;
    }
}