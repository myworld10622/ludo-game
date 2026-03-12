<?php
class AgentUserPaymentLog extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['AgentUser_model', 'Users_model', 'Agent_model']);
    }



    public function index()
    {
        $id = $this->session->userdata('admin_id');
        $data = [
            'title' => 'Payment View Logs',     
            'AllAgentWalletLog' => $this->Agent_model->View_AgentWalletLog($id),
            'AllUserWalletLog' => $this->Agent_model->View_AgentUserWalletLog($id),
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/agentuserpaymentlog', $data);
    }
    
}
