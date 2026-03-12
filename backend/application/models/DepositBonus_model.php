<?php
class DepositBonus_model extends MY_Model
{
    public function All()
    {
        $this->db->select('tbl_deposit_bonus_master.*');
        $this->db->from('tbl_deposit_bonus_master');
        $this->db->where('isDeleted',false);
        $query = $this->db->get();
        return $query->result();
    }

    public function Store($data)
    {
        $this->db->insert('tbl_deposit_bonus_master',$data);
        return true;
    }

    public function ViewFisrtRecharge($id)
    {
        $this->db->select('tbl_deposit_bonus_master.*');
        $this->db->from('tbl_deposit_bonus_master');
        $this->db->where('id',$id);
        $query = $this->db->get();
        return $query->row();
    }

    public function Update($id,$data)
    { 
        $this->db->set($data);
        $this->db->where('id',$id);
        $this->db->update('tbl_deposit_bonus_master');
        return true;
    }

    public function Delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            // 'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_deposit_bonus_master', $data);
        return $this->db->last_query();
    }

   
    public function getBonusCoin($amount,$count)
    {
        $this->db->select('*');
        $this->db->from('tbl_deposit_bonus_master');
        $this->db->where('min<=', $amount);
        $this->db->where('max>=', $amount);
        $this->db->where('deposit_count', $count);
        $this->db->where('isDeleted',0);
        $this->db->order_by('min','asc');
        $this->db->limit(1);
        $Query = $this->db->get();
        return $Query->row();
    }

   

}