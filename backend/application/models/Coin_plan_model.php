<?php

class Coin_plan_model extends MY_Model
{
    public function List()
    {
        $this->db->from('tbl_coin_plan');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function View($id)
    {
        $this->db->from('tbl_coin_plan');
        $this->db->where('isDeleted', false);
        $this->db->where('id', $id);

        $Query = $this->db->get();
        return $Query->row();
    }

    public function GetCoin($user_id, $plan_id, $coin, $price, $extra = 0, $transaction_type = 0)
    {
        $data = [
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'coin' => $coin,
            'extra' => $extra,
            'price' => $price,
            'transaction_type' => $transaction_type
        ];

        if ($this->db->insert('tbl_purchase', $data)) {
            return $this->db->insert_id();
        }
    }

    public function UpdateOrder($user_id, $order_id, $razor_payment_id)
    {
        $this->db->set('razor_payment_id', $razor_payment_id);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('user_id', $user_id);
        $this->db->where('id', $order_id);
        $this->db->update('tbl_purchase');

        return $this->db->affected_rows();
    }

    public function GetUserByOrderId($order_id)
    {
        $this->db->select('tbl_users.name,tbl_users.mobile,tbl_users.profile_pic,tbl_purchase.*');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_users', 'tbl_purchase.user_id=tbl_users.id');
        $this->db->where('tbl_purchase.id', $order_id);
        $Query = $this->db->get();

        return $Query->result();
    }

    public function GetUserByOrderTxnId($txn_id)
    {
        $this->db->select('tbl_users.name,tbl_users.mobile,tbl_users.profile_pic,tbl_purchase.*');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_users', 'tbl_purchase.user_id=tbl_users.id');
        $this->db->where('tbl_purchase.razor_payment_id', $txn_id);
        $Query = $this->db->get();

        return $Query->result();
    }

    public function GetTotalPurchase($status)
    {
        $this->db->select('SUM(coin) as total');
        $this->db->from('tbl_purchase');
        $this->db->where('status', $status);
        $Query = $this->db->get();

        return $Query->row()->total;
    }

    public function GetTodayPurchase()
    {
        $this->db->select('SUM(coin) as total');
        $this->db->from('tbl_purchase');
        $this->db->where('status', 1);
        $this->db->where('DATE(added_date)', date('Y-m-d'));
        $Query = $this->db->get();
        return $Query->row()->total ?? 0;
    }

    public function UpdateOrderPayment($razor_payment_id)
    {
        $this->db->set('isDeleted', 0);
        $this->db->set('payment', 1);
        $this->db->where('razor_payment_id', $razor_payment_id);
        $this->db->update('tbl_purchase');

        return $this->db->affected_rows();
    }

    public function UpdateOrderPaymentStatus($id, $status = 1)
    {
        $this->db->set('isDeleted', 0);
        $this->db->set('payment', $status);
        $this->db->where('id', $id);
        $this->db->update('tbl_purchase');

        return $this->db->affected_rows();
    }

    public function GetTotalAmountByUser($userid)
    {
        $this->db->select('SUM(price) as total');
        $this->db->from('tbl_purchase');
        $this->db->where('isDeleted', 0);
        $this->db->where('payment', 1);
        $this->db->where('user_id', $userid);
        $Query = $this->db->get();
        return $Query->row()->total;
    }

    public function GetUserCategoryByAmount($amount)
    {
        $this->db->select('*');
        $this->db->from('tbl_user_category');
        $this->db->where('isDeleted', 0);
        $this->db->where('amount<', $amount);
        $this->db->order_by('amount', 'desc');
        $Query = $this->db->get();
        return $Query->row();
    }
    public function GetAllPendingPayment()
    {
        $this->db->select('tbl_purchase.*');
        $this->db->from('tbl_purchase');
        $this->db->where('tbl_purchase.payment', 0);
        $this->db->where('tbl_purchase.isDeleted', 0);
        $this->db->order_by('id', 'desc');
        $this->db->limit(3);
        $Query = $this->db->get();
        return $Query->result();
    }
    public function GetAllPendingPaymentFromPayformee()
    {
        $this->db->select('tbl_purchase.*');
        $this->db->from('tbl_purchase');
        $this->db->where('tbl_purchase.payment', 0);
        $this->db->where('tbl_purchase.isDeleted', 0);
        $this->db->where('tbl_purchase.transaction_type', 3);//transaction_type == 3 for getting only payformee transactions 
        $this->db->order_by('id', 'desc');
        $this->db->limit(3);
        $Query = $this->db->get();
        return $Query->result();
    }

}