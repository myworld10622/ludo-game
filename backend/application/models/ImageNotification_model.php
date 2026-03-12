<?php
class ImageNotification_model extends MY_Model
{
    public function ImageList()
    {
        $this->db->from('tbl_image_notification');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }


    public function insert($insert_data)
    {
        $this->db->insert('tbl_image_notification', $insert_data);
        return $this->db->insert_id();
    }

    public function get_latest_image()
    {
       
        $this->db->select('image');
        $this->db->where('isDeleted', false);
        $this->db->order_by('added_date', 'DESC');
        $this->db->where('isDeleted', false);
        $this->db->limit(1);
        $query = $this->db->get('tbl_image_notification');
        
        $result = $query->row_array();
        return !empty($result) ? $result['image'] : ''; 
        
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_image_notification', $data);
        return $this->db->affected_rows();
    }
    
}