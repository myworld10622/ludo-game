<?php

class DailyAttendenceBonusMaster_model extends MY_Model
{
    public function AllDailyAttendenceBonus()
    {
        $this->db->select('tbl_daily_attendence_bonus_master.*');
        $this->db->from('tbl_daily_attendence_bonus_master');
        $query = $this->db->get();
        return $query->result();
    }

    public function add_bonus($data)
    {
        $this->db->insert('tbl_daily_attendence_bonus_master', $data);
        return true;
    }

    public function bonus_details($id)
    {
        $this->db->select('tbl_daily_attendence_bonus_master.*');
        $this->db->from('tbl_daily_attendence_bonus_master');
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function update_bonus($id, $data)
    {
        // print_r($data);
        // exit;
        $this->db->set('accumulated_amount', $data['accumulated_amount']);
        $this->db->set('attendenece_bonus', $data['attendenece_bonus']);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $id);
        $this->db->update('tbl_daily_attendence_bonus_master');

        // Check if the update was successful
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
}
