<?php

class Report_model extends MY_Model
{
    public function recharge_entry($startDate = null, $endDate = null)
    {
        $this->db->select('tbl_wallet_log.*, tbl_users.name as user_name');
        $this->db->from('tbl_wallet_log');
        $this->db->join('tbl_users', 'tbl_wallet_log.user_id = tbl_users.id');
        // $this->db->where('v.isDeleted', false);
        $this->db->order_by('tbl_wallet_log.id', 'desc');

        if ($startDate !== null && $endDate !== null) {
            $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
            $endDate = date('Y-m-d 23:59:00', strtotime($endDate));
            $this->db->where('tbl_wallet_log.added_date >=', $startDate); // Prefix 'added_date' with 'tbl_rummy.'
            $this->db->where('tbl_wallet_log.added_date <=', $endDate); // Prefix 'added_date' with 'tbl_rummy.'
        }

        $Query = $this->db->get();
        return $Query->result();
    }
}