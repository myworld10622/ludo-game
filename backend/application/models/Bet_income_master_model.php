<?php

class Bet_income_master_model extends MY_Model
{
    public function AllBetIncomeBonus()
    {
        $this->db->select('tbl_bet_income_master.*');
        $this->db->from('tbl_bet_income_master');
        $query = $this->db->get();
        return $query->result();
    }

    public function add_bonus($bonus)
    {
        $data = [
            'bonus' => $bonus,
        ];
        $this->db->insert('tbl_bet_income_master', $data);
        return true;
    }

    public function bonus_details($id)
    {
        $this->db->select('tbl_bet_income_master.*');
        $this->db->from('tbl_bet_income_master');
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function update_bonus($id, $bonus)
    {
        $data = [
            'bonus' => $bonus,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        // $this->db->set('bonus',$bonus);
        $this->db->where('id', $id);
        $this->db->update('tbl_bet_income_master', $data);
        return true;
    }
}
