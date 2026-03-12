<?php

class Distributor extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Distributor_model','Users_model','Agent_model']);
    }

    public function index()
    {
        
        $data = [
            'title' => 'Distributor Management',
            'AllAgent' => $this->Distributor_model->DistributorList()
        ];
        if ($_ENV['ENVIRONMENT']!= 'demo') {
           
        $data['SideBarbutton'] = ['backend/Distributor/add', 'Add Distributor'];
        }
        template('distributor/index', $data);
    }

    public function users($id)
    {
        $data = [
            'title' => 'Agent Management',
            'AllAgent' => $this->Agent_model->AllAgentList($id)
        ];
        // $data['SideBarbutton'] = ['backend/Agent/add', 'Add Agent'];
        // print_r($data);
        template('distributor/users', $data);
    }



    public function add()
    {
        $data = [
            'title' => 'Add Distributor'
        ];

        template('distributor/add', $data);
    }

   

    public function edit($id)
    {
        $data = [
            'title' => 'Edit Distributor',
            'agent' => $this->Distributor_model->AgentDetails($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('distributor/edit', $data);
    }

    public function edit_wallet($id)
    {
       $data = [
            'title' => 'Add Wallet Amount',
            'User' => $this->Distributor_model->UserAgentProfile($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('distributor/agentadd_wallet', $data); 
    }

    public function deduct_wallet($id)
    {
        $data = [
            'title' => 'Deduct Wallet Amount',
            'User' => $this->Distributor_model->UserAgentProfile($id)
        ];

        template('distributor/deduct_wallet', $data);
    }

    public function insert()
    {
       $email = $this->input->post('email');
       // Check if email already exists
       $email_exists = $this->Distributor_model->checkEmailExists($email);

       if ($email_exists) {
       $this->session->set_flashdata('msg', array('message' => 'Email ID already exists', 'class' => 'error', 'position' => 'top-right'));
       redirect('backend/Distributor/add');
       } else {
    // Email doesn't exist, proceed with adding agent
         $data = [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'email_id' => $this->input->post('email'),
            'password' => $this->input->post('password'),
            'sw_password' => md5($this->input->post('password')),
            'mobile' => $this->input->post('mobile'),
            'role' => 3,
            'created_date' => date('Y-m-d H:i:s')
        ];
        $agent = $this->Distributor_model->Addagent($data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Distributor Added Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Distributor');
    }
}

    public function update_wallet()
    {
        $user = $this->Distributor_model->UpdateWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            direct_admin_profit_statement(ADMIN_AGENT_TRANSFER,-$this->input->post('amount'),$this->input->post('user_id'));
            log_statement ($this->input->post('user_id'), ADMIN_AGENT_TRANSFER, $this->input->post('amount'),0,0,1);
            $user = $this->Distributor_model->WalletLog($this->input->post('amount'), $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'Distributor Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Distributor');
    }

    public function PaymentHistory()
    {
        $id = $this->session->userdata('admin_id');
        $data = [
            'title' => 'Payment View Logs',     
            'AllAgentWalletLog' => $this->Distributor_model->View_DistributorWalletLog($id),
            'AllUserWalletLog' => $this->Distributor_model->View_AgentWalletLog($id),
        ];
        // echo '<pre>';print_r($data);die;
        template('distributor/payment_history', $data);
    }


    public function update_deduct_wallet()
    {
        
        $user = $this->Distributor_model->DeductWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            direct_admin_profit_statement(ADMIN_AGENT_TRANSFER,$this->input->post('amount'),$this->input->post('user_id'));
            log_statement ($this->input->post('user_id'), ADMIN_AGENT_TRANSFER, $this->input->post('amount'),0,0,1);
            $user = $this->Distributor_model->WalletLog('-'.$this->input->post('amount'), $this->input->post('user_id'));
            $this->session->set_flashdata('msg', array('message' => 'Distributor Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Distributor');
    }


  

    public function update_Agent()
    {
        $password = $this->input->post('password');
        $md5Password = md5($password); // Convert password to MD5 hash
        $data = [
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'password' => $password, // Store original password in 'password' column
            'sw_password' => $md5Password, // Store MD5 hashed password in 'sw_password' column
            'email_id' => $this->input->post('email_id'),
            'mobile' => $this->input->post('mobile'),
        ];
        // print_r($data);die;
        $agent = $this->Distributor_model->Updateagent($this->input->post('agent_id'), $data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Distributor Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Distributor');
    }

    public function delete($id)
    {
        if ($this->Distributor_model->Delete($id)) {
            $this->session->set_flashdata('msg', array('message' => 'Distributor Removed Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Distributor');
    }
    public function view($user_id)
    {
        $data = [
            'title' => 'View Logs',     
            'AllWalletLog' => $this->Distributor_model->View_WalletLog($user_id),
        ];
        // echo '<pre>';print_r($data);die;
        template('distributor/view', $data);
    }

}