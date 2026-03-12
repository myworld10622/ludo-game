<?php
class Deposit_model extends MY_Model
{
    //////////////////////////////////////////////////////////////////////
    /////////////////////// Distributor  to admin ////////////////////////
    //////////////////////////////////////////////////////////////////////
    public function depositHistory($status ='')
    {
        $this->db->select('tbl_distributor_to_admin_request.*,tbl_gateway.name,tbl_admin.first_name as distributor');
        $this->db->from('tbl_distributor_to_admin_request');
        $this->db->join('tbl_gateway', 'tbl_gateway.id = tbl_distributor_to_admin_request.gateway_id');
        $this->db->join('tbl_admin', 'tbl_admin.id = tbl_distributor_to_admin_request.distributor_id');
        $this->db->where('tbl_distributor_to_admin_request.status', $status);
        $this->db->where('tbl_distributor_to_admin_request.isDeleted',false);
        $this->db->where('tbl_gateway.isDeleted',false);
        $this->db->where('tbl_admin.isDeleted',false);
        $query = $this->db->get();
        return $query->result();
    }

    public function store($data)
    {
        $this->db->insert('tbl_distributor_to_admin_request',$data);
        return true;
    }



    public function DepositChangeStatus($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->set('status', $status);
        $this->db->update('tbl_distributor_to_admin_request');

        if ($status == 1) {
            $query = $this->db->where('isDeleted', FALSE)
                ->where('id', $id)
                ->get('tbl_distributor_to_admin_request')
                ->row();

            $this->db->set('wallet', "wallet + $query->amount", FALSE)
                ->set('updated_date', date('Y-m-d H:i:s'))
                ->where('id', $query->distributor_id)
                ->update('tbl_admin');
        }
        return $this->db->last_query();
    }

    //////////////////////////////////////////////////////////////////////
    /////////////////////// agent  to distributor ////////////////////////
    //////////////////////////////////////////////////////////////////////
    public function depositHistoryDistributor($disributor_id,$status ='')
    {
        $this->db->select('tbl_agent_to_distributor_request.*,tbl_gateway.name,tbl_admin.first_name as agent');
        $this->db->from('tbl_agent_to_distributor_request');
        $this->db->join('tbl_gateway', 'tbl_gateway.id = tbl_agent_to_distributor_request.gateway_id');
        $this->db->join('tbl_admin', 'tbl_admin.id = tbl_agent_to_distributor_request.agent_id');
        $this->db->where('tbl_agent_to_distributor_request.distributor_id', $disributor_id);
        $this->db->where('tbl_agent_to_distributor_request.status', $status);
        $this->db->where('tbl_agent_to_distributor_request.isDeleted',false);
        $this->db->where('tbl_gateway.isDeleted',false);
        $this->db->where('tbl_admin.isDeleted',false);
        $query = $this->db->get();
        return $query->result();
    }

    public function getAgentDetailsById($agent_id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('id',$agent_id);
        $query = $this->db->get();
        return $query->row();
    }

    public function storeToDistributer($data)
    {
        $this->db->insert('tbl_agent_to_distributor_request',$data);
        return true;
    }

    public function DepositChangeStatusDistributor($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->set('status', $status);
        $this->db->update('tbl_agent_to_distributor_request');

        if ($status == 1) {
            $query = $this->db->where('isDeleted', FALSE)
                ->where('id', $id)
                ->get('tbl_agent_to_distributor_request')
                ->row();

            $this->db->set('wallet', "wallet + $query->amount", FALSE)
                ->set('updated_date', date('Y-m-d H:i:s'))
                ->where('id', $query->agent_id)
                ->update('tbl_admin');
        }
        return $this->db->last_query();
    }

}