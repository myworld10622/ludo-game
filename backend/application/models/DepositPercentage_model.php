<?php
class DepositPercentage_model extends MY_Model
{
    public function all()
    {
        $this->db->select('tbl_deposit_percentage_master.*');
        $this->db->from('tbl_deposit_percentage_master');
        $this->db->where('isDeleted',false);
        $query = $this->db->get();
        return $query->result();
    }

    public function store($data)
    {
        $this->db->insert('tbl_deposit_percentage_master',$data);
        return true;
    }

    public function getDepositById($id)
    {
        $this->db->select('tbl_deposit_percentage_master.*');
        $this->db->from('tbl_deposit_percentage_master');
        $this->db->where('id',$id);
        $query = $this->db->get();
        return $query->row();
    }
    public function update($id,$data)
    { 
        $this->db->set($data);
        $this->db->where('id',$id);
        $this->db->update('tbl_deposit_percentage_master');
        return true;
    }

    public function delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_deposit_percentage_master', $data);
        return $this->db->last_query();
    }

}