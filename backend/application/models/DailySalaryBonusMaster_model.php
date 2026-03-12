<?php

class DailySalaryBonusMaster_model extends MY_Model
{
    public function AllDailySalaryBonus()
    {
        $this->db->select('tbl_daily_salary_bonus_master.*');
        $this->db->from('tbl_daily_salary_bonus_master');
        $query = $this->db->get();
        return $query->result();
    }

    public function add_bonus($data)
    {
        $this->db->insert('tbl_daily_salary_bonus_master', $data);
        return true;
    }

    public function bonus_details($id)
    {
        $this->db->select('tbl_daily_salary_bonus_master.*');
        $this->db->from('tbl_daily_salary_bonus_master');
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function update_bonus($id, $data)
    {
        // print_r($data);
        // exit;
        $this->db->set('active_users', $data['active_users']);
        $this->db->set('daily_salary_bonus', $data['daily_salary_bonus']);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $id);
        $this->db->update('tbl_daily_salary_bonus_master');

        // Check if the update was successful
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
}
