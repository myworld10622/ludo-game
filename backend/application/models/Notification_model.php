<?php
class Notification_model extends MY_Model
{

    public function List()
    {
        $this->db->from('tbl_notification');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }

    public function Add($data)
    {
        $this->db->insert('tbl_notification', $data);
        return $this->db->insert_id();
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_notification', $data);
        $this->db->last_query();
        return $this->db->affected_rows();
    }

    
    
}
