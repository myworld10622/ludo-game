<?php

class Agent extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Agent_model', 'Users_model', 'AgentUser_model', 'Setting_model', 'Distributor_model']);
    }

    public function index()
    {
        $role = $this->session->userdata("role");
        $data = [
            'title' => 'Agent Management',
            'AllAgent' => $this->Agent_model->AllAgentList(),
            // 'AllUsers' => $this->AgentUser_model->AllAgentUserList($id)

        ];
        if ($_ENV['ENVIRONMENT'] != 'demo' && $role != SUPERADMIN) {
            $data['SideBarbutton'] = ['backend/Agent/add', 'Add Agent'];
        }
        // echo '<pre>';
        // print_r($data);
        // exit();
        template('agent/index', $data);
    }

    public function users($id)
    {
        $data = [
            'title' => 'Agent Management',
            'AllAgent' => $this->AgentUser_model->AllAgentUserList($id)
        ];
        // $data['SideBarbutton'] = ['backend/Agent/add', 'Add Agent'];
        template('agent/users', $data);
    }

    public function Settings()
    {
        $data = [
            'title' => 'Setting Management',
            'setting' => $this->Agent_model->AgentDetails($this->session->admin_id),
        ];
        // $data['SideBarbutton'] = ['backend/Agent/add', 'Add Agent'];
        template('agent/setting', $data);
    }



    public function add()
    {
        $data = [
            'title' => 'Add Agent'
        ];

        template('agent/add', $data);
    }

    public function insert()
    {
        $email = $this->input->post('email');
        // Check if email already exists
        $email_exists = $this->Agent_model->checkEmailExists($email);

        if ($email_exists) {
            $this->session->set_flashdata('msg', array('message' => 'Email ID already exists', 'class' => 'error', 'position' => 'top-right'));
            redirect('backend/Agent/add');
        } else {
            // Email doesn't exist, proceed with adding agent
            $data = [
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name'),
                'email_id' => $this->input->post('email'),
                'password' => $this->input->post('password'),
                'sw_password' => md5($this->input->post('password')),
                'mobile' => $this->input->post('mobile'),
                'role' => 2,
                'addedby' => $this->session->admin_id,
                'created_date' => date('Y-m-d H:i:s')
            ];
            $agent = $this->Agent_model->Addagent($data);
            if ($agent) {
                $this->session->set_flashdata('msg', array('message' => 'Agent Added Successfully', 'class' => 'success', 'position' => 'top-right'));
            } else {
                $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
            }
            redirect('backend/Agent');
        }
    }

    public function edit_Agent($id)
    {
        $data = [
            'title' => 'Edit Agent',
            'agent' => $this->Agent_model->AgentDetails($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/edit', $data);
    }

    public function edit_wallet($id)
    {
        $data = [
            'title' => 'Add Wallet Amount',
            'User' => $this->Agent_model->UserAgentProfile($id)
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/agentadd_wallet', $data);
    }

    public function deduct_wallet($id)
    {
        $data = [
            'title' => 'Deduct Wallet Amount',
            'User' => $this->Agent_model->UserAgentProfile($id)
        ];

        template('agent/deduct_wallet', $data);
    }

    public function update_wallet()
    {
        $check_balance = $this->Distributor_model->checkDistributorBalance($this->session->admin_id);
        if (!empty($check_balance)) {
            if ($this->input->post('amount') <= $check_balance) {
                $user = $this->Agent_model->UpdateWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
                if ($user) {
                    // direct_admin_profit_statement(ADMIN_AGENT_TRANSFER,-$this->input->post('amount'),$this->input->post('user_id'));
                    log_statement($this->input->post('user_id'), ADMIN_AGENT_TRANSFER, $this->input->post('amount'), 0, 0, 1);
                    $user = $this->Agent_model->WalletLog($this->input->post('amount'), $this->input->post('user_id'), $this->session->admin_id);
                    $this->Distributor_model->DeductWalletOrder($this->input->post('amount'), $this->session->admin_id);
                    $this->session->set_flashdata('msg', array('message' => 'Agent Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
                } else {
                    $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
                }
            } else {
                $this->session->set_flashdata('msg', array('message' => 'You do not have sufficient amount', 'class' => 'error', 'position' => 'top-right'));
            }

        } else {
            $this->session->set_flashdata('msg', array('message' => 'You do not have balance', 'class' => 'error', 'position' => 'top-right'));
        }

        redirect('backend/Agent');
    }



    public function update_deduct_wallet()
    {

        $user = $this->Agent_model->DeductWalletOrder($this->input->post('amount'), $this->input->post('user_id'));
        if ($user) {
            // direct_admin_profit_statement(ADMIN_AGENT_TRANSFER,$this->input->post('amount'),$this->input->post('user_id'));
            log_statement($this->input->post('user_id'), ADMIN_AGENT_TRANSFER, $this->input->post('amount'), 0, 0, 1);
            $user = $this->Agent_model->WalletLog('-' . $this->input->post('amount'), $this->input->post('user_id'), $this->session->admin_id);
            $this->Distributor_model->UpdateWalletOrder($this->input->post('amount'), $this->session->admin_id);
            $this->session->set_flashdata('msg', array('message' => 'Agent Wallet Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }


    public function setting_update()
    {
        $data = [
            'agent_withdraw_rate' => $this->input->post('agent_withdraw_rate'),
            'agent_deposite_rate' => $this->input->post('agent_deposite_rate'),
            'agent_acc_details' => $this->input->post('agent_acc_details'),
        ];
        // print_r($data);die;
        $agent = $this->Agent_model->Updateagent($this->session->admin_id, $data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Setting Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/agent/Settings');
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
        $agent = $this->Agent_model->Updateagent($this->input->post('agent_id'), $data);
        if ($agent) {
            $this->session->set_flashdata('msg', array('message' => 'Agent Updated Successfully', 'class' => 'success', 'position' => 'top-right'));
        } else {
            $this->session->set_flashdata('msg', array('message' => 'Somthing Went Wrong', 'class' => 'error', 'position' => 'top-right'));
        }
        redirect('backend/Agent');
    }
    // #######################commented for future use of delete agent with user#######################
    // public function delete($id)
    // {
    //     // First delete the agent
    //     $delAgent = $this->Agent_model->Delete($id);

    //     if ($delAgent) {

    //         // If agent deletion is successful, delete all of agent users
    //         $deletedAllUsers = $this->Agent_model->deleteAgentUsers($id);

    //         if ($deletedAllUsers) {
    //             $this->session->set_flashdata('msg', array('message' => 'Agent and associated users removed successfully!', 'class' => 'success', 'position' => 'top-right'));
    //         } else {
    //             $this->session->set_flashdata('msg', array('message' => 'Agent deleted, but something went wrong while deleting associated users!', 'class' => 'warning', 'position' => 'top-right'));
    //         }
    //     } else {
    //         $this->session->set_flashdata('msg', array('message' => 'Something went wrong while deleting the agent!', 'class' => 'error', 'position' => 'top-right'));
    //     }

    //     redirect('backend/Agent');
    // }

    public function view($user_id)
    {
        $data = [
            'title' => 'View Logs',
            'AllWalletLog' => $this->Agent_model->View_WalletLog($user_id),
        ];
        // echo '<pre>';print_r($data);die;
        template('agent/view', $data);
    }

    // public function payment_method($user_id)
    // {
    //     $data = [
    //         'title' => 'View Payment Methods',
    //         'AllPaymentMethod' => $this->Agent_model->View_Payment_Method_Details($user_id),
    //     ];
    //     // echo '<pre>';print_r($data);die;
    //     template('agent/payment_method', $data);
    // }

    public function payment_method($user_id)
    {
        $data = [
            'title' => 'View Payment Methods',
            'AllPaymentMethod' => $this->Agent_model->View_Payment_Method_Details($user_id),
            'user_id' => $user_id // required for Add button link

        ];

        if ($_ENV['ENVIRONMENT'] == 'demo' || $_ENV['ENVIRONMENT'] == 'fame') {
        } else {
            $data['SideBarbutton'] = ['backend/Agent/add_payment_method/' . $user_id, 'Add Payment Method'];
        }

        template('agent/payment_method', $data);
    }

 
    public function add_payment_method($user_id = null)
    {
        if (!$user_id) {
            show_error('User ID is required.', 400); // Or redirect with error
        }
    
        $data = [
            'title' => 'Add Payment Method',
            'user_id' => $user_id
        ];
        template('agent/add_payment_method', $data);
    }

    public function insert_payment_method()
    {
        $user_id = $this->input->post('user_id');
        $name = $this->input->post('name');
    
        $data = [
            'user_id' => $user_id,
            'name' => $name
        ];
    
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $upload = upload_image($_FILES['image'], BANNER_URL);
    
            if ($upload) {
                $data['image'] = $upload;
            } else {
                $this->session->set_flashdata('msg', ['message' => 'Image upload failed', 'class' => 'error', 'position' => 'top-right']);
                redirect('backend/Agent/payment_method/' . $user_id);
                return;
            }
        }
    
        // Insert into database
        $inserted_id = $this->Agent_model->insert_payment_methods($data);
    
        // Set flash message and redirect
        if ($inserted_id) {
            $this->session->set_flashdata('msg', ['message' => 'Payment Method Added Successfully', 'class' => 'success', 'position' => 'top-right']);
        } else {
            $this->session->set_flashdata('msg', ['message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right']);
        }
    
        redirect('backend/Agent/payment_method/' . $user_id);
    }


    public function delete_payment_method($id, $user_id)
    {
        $deleted = $this->Agent_model->delete_payment_method($id);
    
        if ($deleted) {
            $this->session->set_flashdata('msg', ['message' => 'Payment Method Deleted Successfully', 'class' => 'success', 'position' => 'top-right']);
        } else {
            $this->session->set_flashdata('msg', ['message' => 'Something Went Wrong', 'class' => 'error', 'position' => 'top-right']);
        }
    
        redirect('backend/Agent/payment_method/' . $user_id);
    }


}