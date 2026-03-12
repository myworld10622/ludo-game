<?php

class SubAdmin_model extends MY_Model
{

    public function SubadminDetails($id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.isDeleted',false);
        $this->db->where('tbl_admin.id',$id);
        $query = $this->db->get();
        return $query->row();
    }

    public function AllSubAdminList()
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.isDeleted',false);
        $this->db->where('tbl_admin.role',1);
        $query = $this->db->get();
        return $query->result();
    }

    public function Addsubadmin($data)
    {
       $this->db->insert('tbl_admin',$data);
       return $this->db->insert_id();
    }


    public function checkEmailExists($email) {
        $this->db->where('email_id', $email);
        $query = $this->db->get('tbl_admin'); // Assuming your table name is 'agents'
        
        return $query->num_rows() > 0;
    
    }

    public function Updatesubadmin($id,$data)
    {
        $this->db->where('tbl_admin.id',$id);
        $this->db->update('tbl_admin',$data);
        return $this->db->affected_rows();
    }

    public function Delete($id)
    {
        $data = [
            'isDeleted' => TRUE,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        $this->db->update('tbl_admin', $data);
        return $this->db->last_query();
    }

}