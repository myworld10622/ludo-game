<?php

class Gateway_model extends MY_Model
{

    public function ManualGatway()
    {
        $this->db->select('tbl_gateway.*,');
        $this->db->from('tbl_gateway');
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function store($data)
    {
        $this->db->insert('tbl_gateway', $data);
        return $this->db->insert_id();
    }

    public function getManualGatwayById($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_gateway');
        return $Query->row();
    }
    public function updateManual($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_gateway', $data);
    }

    public function update_status($id, $status)
    {
        $data = [
            'status' => $status,
            'updated_date' => date('Y-m-d H:i:s')
        ];
        return $this->db
            ->where('id', $id)
            ->update('tbl_gateway', $data);
    }

    // agent login gatway 

    public function getManualGatwayByRole($role)
    {
        $this->db->select('tbl_gateway.*,');
        $this->db->from('tbl_gateway');
        $this->db->where("FIND_IN_SET($role, tbl_gateway.role) > 0", null, false);
        $this->db->where('status', 1);
        $this->db->where('isDeleted', false);
        $this->db->order_by('id', 'desc');
        $Query = $this->db->get();
        return $Query->result();
    }
    public function agentManualGatway($agent_id)
    {
        $this->db->select('tbl_agent_gatway.*, tbl_gateway.name');
        $this->db->from('tbl_agent_gatway');
        $this->db->join('tbl_gateway', 'tbl_gateway.id = tbl_agent_gatway.gateway_id');
        // $this->db->where('tbl_gateway.role', $this->session->userdata('role'));
        $this->db->where('FIND_IN_SET("'.$this->session->userdata('role').'", tbl_gateway.role)', null, false);
        $this->db->where('tbl_agent_gatway.agent_id', $agent_id);
        $this->db->where('tbl_gateway.isDeleted', false);
        $this->db->where('tbl_agent_gatway.isDeleted', false);
        $this->db->order_by('tbl_agent_gatway.id', 'desc');
        $query = $this->db->get();
        return $query->result();
    }

    public function storeAgentManual($data)
    {
        $this->db->insert('tbl_agent_gatway', $data);
        return $this->db->insert_id();
    }

    public function getAgentManualGatwayById($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_agent_gatway');
        return $Query->row();
    }
    public function updateAgentManual($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_agent_gatway', $data);
    }

    /////////// withdraw chnnel ///////////////

    public function agentManualGatwayWithdraw($agent_id)
    {
        $this->db->select('tbl_agent_gatway_withdraw.*, tbl_gateway.name');
        $this->db->from('tbl_agent_gatway_withdraw');
        $this->db->join('tbl_gateway', 'tbl_gateway.id = tbl_agent_gatway_withdraw.gateway_id');
        // $this->db->where('tbl_gateway.role', $this->session->userdata('role'));
        $this->db->where('FIND_IN_SET("'.$this->session->userdata('role').'", tbl_gateway.role)', null, false);
        $this->db->where('tbl_agent_gatway_withdraw.agent_id', $agent_id);
        $this->db->where('tbl_gateway.isDeleted', false);
        $this->db->where('tbl_agent_gatway_withdraw.isDeleted', false);
        $this->db->order_by('tbl_gateway.id', 'desc');
        $query = $this->db->get();
        return $query->result();
    }
    public function storeAgentManualWithdraw($data)
    {
        $this->db->insert('tbl_agent_gatway_withdraw', $data);
        return $this->db->insert_id();
    }

    public function getAgentManualGatwayWithdrawById($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_agent_gatway_withdraw');
        return $Query->row();
    }
    public function updateAgentManualWithdraw($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_agent_gatway_withdraw', $data);
    }

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////// Distributer /////////////////////////////
    ///////////////////////////////////////////////////////////////////////

    public function getGatwayByRoleForAgentRequest($role,$addedby) // addedby means distributor or admin
    {
        $this->db->select('tbl_gateway.*,tbl_distributor_gatway.number');
        $this->db->from('tbl_gateway');
        $this->db->join('tbl_distributor_gatway', 'tbl_distributor_gatway.gateway_id = tbl_gateway.id', 'inner');
        $this->db->where("FIND_IN_SET($role, tbl_gateway.role) >", 0, false);
        $this->db->where('tbl_gateway.status', 1);
        $this->db->where('tbl_distributor_gatway.distributor_id', $addedby);
        $this->db->where('tbl_gateway.isDeleted', false);
        $this->db->where('tbl_distributor_gatway.isDeleted', false); // only use non-deleted distributor gateway links
        $this->db->order_by('tbl_gateway.id', 'desc');
        $query = $this->db->get();
        // 	echo '<pre>';
		// echo $this->db->last_query();
		// exit();
        return $query->result();
    }
    public function distributorManualGatway($distributor_id)
    {
        $this->db->select('tbl_distributor_gatway.*, tbl_gateway.name');
        $this->db->from('tbl_distributor_gatway');
        $this->db->join('tbl_gateway', 'tbl_gateway.id = tbl_distributor_gatway.gateway_id');
        $this->db->where('FIND_IN_SET("'.$this->session->userdata('role').'", tbl_gateway.role)', null, false);
        $this->db->where('tbl_distributor_gatway.distributor_id', $distributor_id);
        $this->db->where('tbl_gateway.isDeleted', false);
        $this->db->where('tbl_distributor_gatway.isDeleted', false);
        $this->db->order_by('tbl_distributor_gatway.id', 'desc');
        $query = $this->db->get();
        // echo '<pre>';
		// echo $this->db->last_query();
		// exit();
        return $query->result();
    }
    public function storeDistributorManual($data)
    {
        $this->db->insert('tbl_distributor_gatway', $data);
        return $this->db->insert_id();
    }

    public function getDistributorManualGatwayById($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_distributor_gatway');
        return $Query->row();
    }
    public function updateDistributorManual($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_distributor_gatway', $data);
    }

    ///////////////////////// withdraw chnnel /////////////////////

    public function distributorManualGatwayWithdraw($distributor_id)
    {
        $this->db->select('tbl_distributor_gatway_withdraw.*, tbl_gateway.name');
        $this->db->from('tbl_distributor_gatway_withdraw');
        $this->db->join('tbl_gateway', 'tbl_gateway.id = tbl_distributor_gatway_withdraw.gateway_id');
        $this->db->where('FIND_IN_SET("'.$this->session->userdata('role').'", tbl_gateway.role)', null, false);
        $this->db->where('tbl_distributor_gatway_withdraw.distributor_id', $distributor_id);
        $this->db->where('tbl_gateway.isDeleted', false);
        $this->db->where('tbl_distributor_gatway_withdraw.isDeleted', false);
        $this->db->order_by('tbl_distributor_gatway_withdraw.id', 'desc');
        $query = $this->db->get();
        return $query->result();
    }
    public function storeDistributorManualWithdraw($data)
    {
        $this->db->insert('tbl_distributor_gatway_withdraw', $data);
        return $this->db->insert_id();
    }

    public function getDistributorManualGatwayWithdrawById($id)
    {
        $Query = $this->db->where('isDeleted', False)
            ->where('id', $id)
            ->get('tbl_distributor_gatway_withdraw');
        return $Query->row();
    }
    public function updateDistributorManualWithdraw($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbl_distributor_gatway_withdraw', $data);
    }

    ####################### needed to modify ####################################

}