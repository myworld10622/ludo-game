<?php

class Chat_model extends MY_Model
{

    public function AgentDetails($id)
    {
        $this->db->select('tbl_admin.*');
        $this->db->from('tbl_admin');
        $this->db->where('tbl_admin.isDeleted',false);
        $this->db->where('tbl_admin.id',$id);
        $query = $this->db->get();
        return $query->row();
    }

    public function AllUserChatList()
    {
        $this->db->select('tbl_conversations.*,tbl_users.name as first_name,tbl_users.mobile,profile_pic,tbl_users.id as tbl_user_id');
        $this->db->from('tbl_conversations');
        $this->db->join('tbl_users', 'tbl_conversations.user_id = tbl_users.id');
        $this->db->where('tbl_conversations.isDeleted',false);
        $this->db->where('tbl_users.isDeleted',false);
        $this->db->order_by('tbl_conversations.updated_date','DESC');
        if($this->session->role==2){
            $this->db->where('tbl_conversations.agent_id',$this->session->admin_id);  
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function AllChatById($conversation_id)
    {
        $this->db->select('tbl_messages.*,tbl_users.name as first_name,tbl_users.mobile,profile_pic');
        $this->db->from('tbl_messages');
        $this->db->join('tbl_users', 'tbl_messages.sender_id = tbl_users.id','left');
        $this->db->where('tbl_messages.conversation_id',$conversation_id);
        $query = $this->db->get();
        return $query->result();
    }

    public function Addagent($data)
    {
       $this->db->insert('tbl_admin',$data);
       return $this->db->insert_id();
    }

    public function Updateagent($id,$data)
    {
        $this->db->where('tbl_admin.id',$id);
        return$this->db->update('tbl_admin',$data);
    }
    
    public function checkEmailExists($email) {
        $this->db->where('email_id', $email);
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

    public function WalletLog($amount,$user_id)
    {
        $data = [
            'user_id' => $user_id,
            'coin' => $amount,
            //'added_date' => date('Y-m-d H:i:s')
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

    public function updateConversation($data, $id)
    {
        $this->db->where('tbl_conversations.id',$id);
        $this->db->update('tbl_conversations', $data);
    }

}