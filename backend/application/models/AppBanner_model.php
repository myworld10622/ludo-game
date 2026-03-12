<?php
class AppBanner_model extends MY_Model
{

    public function view($id='')
    {
        if($id!=''){
            $this->db->where('id', $id);
        }
        $this->db->where('isDeleted', false);
        $Query = $this->db->get('tbl_appbanner');

        if($id!=''){
            return $Query->row();
        }
        return $Query->result();
    }

    public function insert($data)
    {
        $this->db->insert('tbl_appbanner', $data);
        return $this->db->insert_id();
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tbl_appbanner', $data);
        return $this->db->affected_rows();
    }

}
