<?php

use phpDocumentor\Reflection\DocBlock\Tags\Return_;

class AddCash_model extends MY_Model
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

    public function WithDraw($user_id, $Redeem_id, $coin, $mobile)
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
                'mobile' => $mobile,
                'coin' => $coin,
            ];
            $this->db->insert('tbl_purchase', $data);
            return $this->db->insert_id();
        }
    }
    
	    public function WithDrawal_log($user_id)
    {
        return $Query = $this->db->select('tbl_purchase.*,tbl_users.name as user_name,tbl_users.mobile as user_mobile,tbl_users.bank_detail,tbl_users.adhar_card,tbl_users.upi')
            ->from('tbl_purchase')
            ->join('tbl_users', 'tbl_users.id=tbl_purchase.user_id')
            ->where('tbl_purchase.isDeleted', FALSE)
            ->where('tbl_purchase.user_id', $user_id)
            ->get()
            ->result();
    }

    public function WithDrawal_list($status,$startDate="", $endDate="")
    {
        $this->db->select('tbl_purchase.*,tbl_users.name as user_name,tbl_users.mobile as user_mobile,tbl_users.bank_detail,tbl_users.adhar_card,tbl_users.upi');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_users', 'tbl_users.id=tbl_purchase.user_id');
            // filter
        if(!empty($startDate)) {
            $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
            $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
            $this->db->where('tbl_purchase.added_date >=', $startDate);
            $this->db->where('tbl_purchase.added_date <=', $endDate);
        }else {
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            $this->db->where('tbl_purchase.added_date >=', $startDate);
            $this->db->where('tbl_purchase.added_date <=', $endDate);
        }
        $this->db->where('tbl_purchase.isDeleted', FALSE);
        $this->db->where('tbl_purchase.status', $status);
        $Query= $this->db->get();
        return $Query->result();
    }

    public function ChangeStatus($id, $status)
    {
        $this->db->where('id', $id)
            ->set('status', $status)
            ->update('tbl_purchase');
            
        if($status==2)
        {
            $Query = $this->db->where('isDeleted', FALSE)
            ->where('id', $id)
            ->get('tbl_purchase')->row();

            $this->db->set('wallet', "wallet+$Query->coin", FALSE)
            ->set('winning_wallet', "winning_wallet+$Query->coin", FALSE)
            ->set('updated_date', date('Y-m-d H:i:s'))
            ->where('id', $Query->user_id)
            ->update('tbl_users');
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

    public function ChangeStatusAddCash($id, $status)
    {
        $updated_date = date('Y-m-d H:i:s');

        $this->db->where('id', $id)
            ->set('status', $status)
            ->set('updated_date', $updated_date)
            ->update('tbl_purchase');
        
        if($status==1)
        {
            $Query = $this->db->where('isDeleted', FALSE)
            ->where('id', $id)
            ->get('tbl_purchase')->row();

            $this->db->set('wallet', "wallet+".$Query->price, FALSE)
            ->set('winning_wallet', "winning_wallet+".$Query->price, FALSE)
            ->set('updated_date', date('Y-m-d H:i:s'))
            ->where('id', $Query->user_id)
            ->update('tbl_users');
        }

        return $this->db->last_query();
    }
}