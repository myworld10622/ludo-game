<?php

class Agent_model extends MY_Model
{

    public function AgentDetails($id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.isDeleted', false);
        $this->db->where('tbl_admin.id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function AllAgentList($distributor_id = '')
    {
        // distributor is used as alis of tbl_admin for self join
        $this->db->select('tbl_admin.*, distributor.first_name AS distributor_fname, distributor.last_name AS distributor_lname');
        $this->db->from('tbl_admin');
        $this->db->join(
            'tbl_admin AS distributor',
            'tbl_admin.addedby = distributor.id',
            'left'
        );
        $this->db->where('tbl_admin.isDeleted', false);
        $this->db->where('tbl_admin.role', 2); // Agents
        if ($this->session->role == DISTRIBUTOR) {
            $this->db->where('tbl_admin.addedby', $this->session->admin_id);
        }
        if (!empty($distributor_id)) {
            $this->db->where('tbl_admin.addedby', $distributor_id);
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function Addagent($data)
    {
        $this->db->insert('tbl_admin', $data);
        return $this->db->insert_id();
    }

    public function Updateagent($id, $data)
    {
        $this->db->where('tbl_admin.id', $id);
        return $this->db->update('tbl_admin', $data);
    }

    public function checkEmailExists($email)
    {
        $this->db->where('email_id', $email);
        $this->db->where('isDeleted', 0);
        $query = $this->db->get('tbl_admin'); // Assuming your table name is 'agents'

        return $query->num_rows() > 0;

    }

    /**
     * @param mixed $amount
     * @param mixed $user_id
     * @param int $bonus
     * 
     * @return [type]
     */
    public function UpdateWalletOrder($amount, $user_id, $bonus = 0)
    {
        $this->db->set('wallet', 'wallet+' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_admin');

        return true;
    }

    public function DeductWalletOrder($amount, $user_id, $bonus = 0)
    {
        $this->db->set('wallet', 'wallet-' . $amount, false);
        $this->db->set('updated_date', date('Y-m-d H:i:s'));
        $this->db->where('id', $user_id);
        $this->db->update('tbl_admin');

        return true;
    }

    public function WalletLog($amount, $user_id, $addedby = '')
    {
        $data = [
            'user_id' => $user_id,
            'coin' => $amount,
            'added_by' => $addedby
        ];
        $this->db->insert('tbl_agentwallet_log', $data);
        return $this->db->insert_id();
    }

    public function UserAgentProfile($id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.id', $id);
        $this->db->where('tbl_admin.isDeleted', false);
        $Query = $this->db->get();
        // echo $this->db->last_query();
        // die();
        return $Query->result();
    }

    // ############################# comment for delete agent with user ############################
    // public function Delete($id)
    // {
    //     $data = [
    //         'isDeleted' => TRUE,
    //         'updated_date' => date('Y-m-d H:i:s')
    //     ];
    //     $this->db->where('id', $id);
    //     $this->db->update('tbl_admin', $data);
    //     return $this->db->last_query();
    // }
    // public function deleteAgentUsers($agentId)
    // {
    //     $data = [
    //         'isDeleted' => TRUE,
    //         'updated_date' => date('Y-m-d H:i:s')
    //     ];
    //     $this->db->where('created_by', $agentId);
    //     return $this->db->update('tbl_users', $data); // Returns true on success
    // }

    public function View_WalletLog($user_id)
    {
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get('tbl_agentwallet_log');
        return $Query->result();
    }
    public function View_AgentWalletLog($user_id)
    {
        $this->db->where('user_id', $user_id);
        $Query = $this->db->get('tbl_agentwallet_log');
        return $Query->result();
    }
    public function View_AgentUserWalletLog($added_by)
    {
        $this->db->select('tbl_wallet_log.*, tbl_users.name as Username');
        $this->db->from('tbl_wallet_log');
        $this->db->join('tbl_users', 'tbl_wallet_log.user_id = tbl_users.id');
        $this->db->where('tbl_wallet_log.added_by', $added_by);
        $query = $this->db->get();
        return $query->result();
    }

    public function getAgentBalance($user_id)
    {
        $this->db->select('wallet');
        $this->db->where('id', $user_id);
        $query = $this->db->get('tbl_admin');

        // Check if the query returned a result
        if ($query->num_rows() > 0) {
            // Return the user's wallet balance
            return $query->row()->wallet;
        } else {
            // If user is not found, return false or handle accordingly
            return false;
        }
    }

    public function getDistributprBalance($user_id)
    {
        $this->db->select('wallet');
        $this->db->where('id', $user_id);
        $query = $this->db->get('tbl_admin');
        // Check if the query returned a result
        if ($query->num_rows() > 0) {
            // Return the user's wallet balance
            return $query->row()->wallet;
        } else {
            // If user is not found, return false or handle accordingly
            return false;
        }
    }

    public function View_Payment_Method_Details($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'desc');
        $Query = $this->db->get('tbl_payment_method');
        return $Query->result();
    }

    
    public function insert_payment_methods($data)
    {
        return $this->db->insert('tbl_payment_method', $data);

    }
    public function delete_payment_method($id)
    {
        return $this->db->delete('tbl_payment_method', ['id' => $id]);
    }

}